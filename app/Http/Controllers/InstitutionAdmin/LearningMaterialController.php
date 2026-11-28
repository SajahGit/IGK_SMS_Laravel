<?php

namespace App\Http\Controllers\InstitutionAdmin;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\MaterialAccessLog;
use App\Models\MaterialCategory;
use App\Models\MaterialShareLink;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Services\LearningMaterialService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LearningMaterialController extends Controller
{
    public function __construct(
        private readonly LearningMaterialService $service,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $typeFilter = $request->query('material_type');
        $categoryFilter = $request->query('category_id');
        $teacherFilter = $request->query('uploaded_by');
        $statusFilter = $request->query('status_filter');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $search = trim((string) $request->query('search', ''));

        $materials = LearningMaterial::query()
            ->with(['category', 'uploader', 'shareLinks' => fn ($q) => $q->latest('id')])
            ->withCount(['accessLogs'])
            ->when($typeFilter, fn ($q) => $q->where('material_type', $typeFilter))
            ->when($categoryFilter, fn ($q) => $q->where('category_id', $categoryFilter))
            ->when($teacherFilter, fn ($q) => $q->where('uploaded_by', $teacherFilter))
            ->when($statusFilter === 'active', fn ($q) => $q->libraryVisible())
            ->when($statusFilter === 'inactive', fn ($q) => $q->where('status', LearningMaterial::STATUS_INACTIVE))
            ->when($statusFilter === 'expired', fn ($q) => $q->expiredOnly())
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('course_subject_label', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total_videos' => LearningMaterial::where('material_type', LearningMaterial::TYPE_VIDEO)->count(),
            'total_pdfs' => LearningMaterial::where('material_type', LearningMaterial::TYPE_PDF)->count(),
            'active_materials' => LearningMaterial::libraryVisible()->count(),
            'expired_materials' => LearningMaterial::expiredOnly()->count(),
            'users_with_access' => User::where('is_active', true)->where('lms_material_access', true)->count(),
            'total_video_plays' => MaterialAccessLog::whereHas('material', fn ($q) => $q->where('material_type', LearningMaterial::TYPE_VIDEO))
                ->whereIn('access_type', ['view', 'stream', 'share_open'])->count(),
            'total_pdf_views' => MaterialAccessLog::whereHas('material', fn ($q) => $q->where('material_type', LearningMaterial::TYPE_PDF))
                ->whereIn('access_type', ['view', 'share_open'])->count(),
        ];

        $mostViewed = LearningMaterial::query()
            ->withCount(['accessLogs' => fn ($q) => $q->whereIn('access_type', ['view', 'stream', 'share_open'])])
            ->orderByDesc('access_logs_count')
            ->take(5)
            ->get();

        $recentLogs = MaterialAccessLog::query()
            ->with(['material', 'user'])
            ->latest('accessed_at')
            ->take(20)
            ->get();

        $teacherStats = User::query()
            ->where('role', 'teacher')
            ->withCount('uploadedLearningMaterials')
            ->orderByDesc('uploaded_learning_materials_count')
            ->get();

        $categories = MaterialCategory::orderBy('name')->get();
        $uploaders = User::whereIn('role', ['super-admin', 'super admin', 'admin', 'institution admin', 'teacher'])->orderBy('name')->get();
        $settings = $this->service->getGlobalSettings();

        $userSearch = trim((string) $request->query('user_search', ''));
        $userRole = trim((string) $request->query('user_role', ''));
        $userClassId = $request->query('user_class_id');
        $userRoles = User::query()->select('role')->distinct()->orderBy('role')->pluck('role');
        $classes = SchoolClass::orderBy('name')->orderBy('section')->get();

        $managedUsers = User::query()
            ->when($userSearch !== '', function ($q) use ($userSearch) {
                $q->where(function ($sub) use ($userSearch) {
                    $sub->where('name', 'like', "%{$userSearch}%")
                        ->orWhere('email', 'like', "%{$userSearch}%")
                        ->orWhere('role', 'like', "%{$userSearch}%");
                });
            })
            ->when($userRole !== '', fn ($q) => $q->where('role', $userRole))
            ->when($userClassId, function ($q) use ($userClassId) {
                $userIds = Student::where('class_id', $userClassId)->whereNotNull('user_id')->pluck('user_id');
                $q->whereIn('id', $userIds);
            })
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(15, ['*'], 'users_page')
            ->withQueryString();

        $studentClassMap = Student::with('class')
            ->whereIn('user_id', $managedUsers->pluck('id'))
            ->get()
            ->keyBy('user_id');

        return view('Admin.LearningMaterialsIndex', compact(
            'materials',
            'stats',
            'mostViewed',
            'recentLogs',
            'teacherStats',
            'categories',
            'uploaders',
            'settings',
            'managedUsers',
            'userRoles',
            'classes',
            'studentClassMap',
            'userSearch',
            'userRole',
            'userClassId',
            'typeFilter',
            'categoryFilter',
            'teacherFilter',
            'statusFilter',
            'dateFrom',
            'dateTo',
            'search'
        ));
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $categories = MaterialCategory::where('is_active', true)->orderBy('name')->get();
        $material = null;

        return view('Admin.LearningMaterialForm', compact('categories', 'material'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validateMaterial($request, true);
        $fileMeta = $this->service->storeUploadedFile($request->file('file'), $data['material_type']);
        $thumbPath = $request->hasFile('thumbnail')
            ? $this->service->storeThumbnail($request->file('thumbnail'))
            : null;

        try {
            $material = DB::transaction(function () use ($data, $fileMeta, $thumbPath, $request): LearningMaterial {
                return LearningMaterial::create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'material_type' => $data['material_type'],
                    'storage_path' => $fileMeta['storage_path'],
                    'original_name' => $fileMeta['original_name'],
                    'mime_type' => $fileMeta['mime_type'],
                    'file_size' => $fileMeta['file_size'],
                    'thumbnail_path' => $thumbPath,
                    'category_id' => $data['category_id'] ?? null,
                    'course_subject_label' => $data['course_subject_label'] ?? null,
                    'uploaded_by' => $request->user()->id,
                    'visibility' => $data['visibility'],
                    'status' => $data['status'],
                    'allow_download' => (bool) ($data['allow_download'] ?? false),
                    'expiry_enabled' => (bool) ($data['expiry_enabled'] ?? false),
                    'expires_at' => $this->resolveExpiresAt($data),
                ]);
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete(array_filter([$fileMeta['storage_path'], $thumbPath]));
            throw $e;
        }

        AuditLogger::log('lms_material_created', $material, null, $material->only(['title', 'material_type', 'status', 'allow_download']));
        $this->service->notifyAuthorizedUsersOfNewMaterial($material);

        return redirect()->route('admin.learning-materials.index')
            ->with('success', 'Learning material uploaded securely.');
    }

    public function edit(Request $request, LearningMaterial $material): View
    {
        abort_unless($request->user()->isAdmin(), 403);
        $categories = MaterialCategory::where('is_active', true)->orderBy('name')->get();

        return view('Admin.LearningMaterialForm', compact('categories', 'material'));
    }

    public function update(Request $request, LearningMaterial $material): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $this->validateMaterial($request, false, $material);
        $before = $material->toArray();

        $updates = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'course_subject_label' => $data['course_subject_label'] ?? null,
            'visibility' => $data['visibility'],
            'status' => $data['status'],
            'allow_download' => (bool) ($data['allow_download'] ?? false),
            'expiry_enabled' => (bool) ($data['expiry_enabled'] ?? false),
            'expires_at' => $this->resolveExpiresAt($data),
        ];

        $oldPaths = [];
        if ($request->hasFile('file')) {
            $oldPaths[] = $material->storage_path;
            $fileMeta = $this->service->storeUploadedFile($request->file('file'), $material->material_type);
            $updates = array_merge($updates, $fileMeta);
        }

        if ($request->hasFile('thumbnail')) {
            if ($material->thumbnail_path) {
                $oldPaths[] = $material->thumbnail_path;
            }
            $updates['thumbnail_path'] = $this->service->storeThumbnail($request->file('thumbnail'));
        }

        $material->update($updates);
        if ($oldPaths !== []) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete(array_filter($oldPaths));
        }

        AuditLogger::log('lms_material_updated', $material, $before, $material->fresh()->toArray());

        return redirect()->route('admin.learning-materials.index')
            ->with('success', 'Learning material updated.');
    }

    public function toggleStatus(Request $request, LearningMaterial $material): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $nextStatus = $material->status === LearningMaterial::STATUS_ACTIVE
            ? LearningMaterial::STATUS_INACTIVE
            : LearningMaterial::STATUS_ACTIVE;
        $material->update(['status' => $nextStatus]);
        AuditLogger::log('lms_material_status_toggled', $material, null, ['status' => $nextStatus]);

        return back()->with('success', "Material marked as {$nextStatus}.");
    }

    public function toggleDownload(Request $request, LearningMaterial $material): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $next = ! $material->allow_download;
        $material->update(['allow_download' => $next]);
        AuditLogger::log('lms_material_download_toggled', $material, null, ['allow_download' => $next]);

        return back()->with('success', 'Download permission updated to '.($next ? 'ON' : 'OFF').'.');
    }

    public function expireNow(Request $request, LearningMaterial $material): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $material->update([
            'expiry_enabled' => true,
            'expires_at' => now()->subSecond(),
        ]);
        AuditLogger::log('lms_material_expired_manually', $material);

        return back()->with('success', 'Material expired immediately.');
    }

    public function destroy(Request $request, LearningMaterial $material): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $before = $material->toArray();
        $this->service->deleteStoredFiles($material);
        $material->delete();
        AuditLogger::log('lms_material_deleted', LearningMaterial::class, $before, null);

        return redirect()->route('admin.learning-materials.index')
            ->with('success', 'Learning material deleted permanently.');
    }

    public function generateShareLink(Request $request, LearningMaterial $material): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:120'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $created = $this->service->createShareLink(
            $material,
            $request->user(),
            $data['label'] ?? null,
            $data['expires_at'] ?? null
        );

        AuditLogger::log('lms_share_link_created', $material, null, ['share_link_id' => $created['link']->id]);

        return back()
            ->with('success', 'Secure share link generated (login required for recipients).')
            ->with('generated_share_url', $created['url']);
    }

    public function revokeShareLink(Request $request, MaterialShareLink $shareLink): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        $shareLink->update(['revoked_at' => now()]);
        AuditLogger::log('lms_share_link_revoked', $shareLink->material, null, ['share_link_id' => $shareLink->id]);

        return back()->with('success', 'Share link revoked immediately.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $this->service->updateGlobalSettings([
            'enabled' => $request->boolean('enabled'),
            'student_access' => $request->boolean('student_access'),
            'teacher_uploads' => $request->boolean('teacher_uploads'),
            'teacher_video_uploads' => $request->boolean('teacher_video_uploads'),
            'teacher_pdf_uploads' => $request->boolean('teacher_pdf_uploads'),
        ]);

        AuditLogger::log('lms_material_settings_updated', $request->user());

        return back()->with('success', 'Learning material global permissions updated.');
    }

    public function toggleUserAccess(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $allowed = ['lms_material_access', 'lms_video_access', 'lms_pdf_access', 'lms_can_upload_materials'];
        $field = in_array($request->input('permission_field'), $allowed, true)
            ? (string) $request->input('permission_field')
            : 'lms_material_access';

        $newValue = ! $user->{$field};
        $updates = [$field => $newValue];

        if ($field === 'lms_material_access') {
            $updates['lms_video_access'] = $newValue;
            $updates['lms_pdf_access'] = $newValue;
        } elseif ($field === 'lms_video_access') {
            $updates['lms_material_access'] = $newValue || (bool) $user->lms_pdf_access;
        } elseif ($field === 'lms_pdf_access') {
            $updates['lms_material_access'] = $newValue || (bool) $user->lms_video_access;
        }

        $user->forceFill($updates)->save();
        AuditLogger::log('lms_user_permission_toggled', $user, null, $updates);
        $this->service->notifyUserPermissionChange($user, $field, (bool) $newValue);

        return back()->with('success', "Updated {$user->name}'s LMS permission.");
    }

    public function bulkUserAccess(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'scope' => ['required', Rule::in(['selected', 'role', 'class', 'all'])],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'target_role' => ['nullable', 'string', 'max:100'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'permission_field' => ['required', Rule::in(['lms_material_access', 'lms_video_access', 'lms_pdf_access', 'lms_can_upload_materials'])],
            'grant' => ['required', 'boolean'],
        ]);

        $query = match ($data['scope']) {
            'selected' => User::query()->whereIn('id', $data['user_ids'] ?? []),
            'role' => User::query()->where('role', (string) ($data['target_role'] ?? '')),
            'class' => User::query()->whereIn(
                'id',
                Student::where('class_id', $data['class_id'] ?? 0)->whereNotNull('user_id')->pluck('user_id')
            ),
            'all' => User::query(),
        };

        if ($data['scope'] === 'selected' && empty($data['user_ids'])) {
            return back()->withErrors(['bulk' => 'Please select at least one member.']);
        }
        if ($data['scope'] === 'role' && empty($data['target_role'])) {
            return back()->withErrors(['bulk' => 'Please choose a role for bulk update.']);
        }
        if ($data['scope'] === 'class' && empty($data['class_id'])) {
            return back()->withErrors(['bulk' => 'Please choose a class for bulk update.']);
        }

        $field = $data['permission_field'];
        $grant = (bool) $data['grant'];

        $updated = 0;
        if ($field === 'lms_material_access') {
            $updated = $query->update([
                'lms_material_access' => $grant,
                'lms_video_access' => $grant,
                'lms_pdf_access' => $grant,
            ]);
        } elseif ($field === 'lms_can_upload_materials') {
            $updated = $query->update(['lms_can_upload_materials' => $grant]);
        } else {
            $users = $query->get();
            foreach ($users as $target) {
                $video = $field === 'lms_video_access' ? $grant : (bool) $target->lms_video_access;
                $pdf = $field === 'lms_pdf_access' ? $grant : (bool) $target->lms_pdf_access;
                $target->forceFill([
                    'lms_video_access' => $video,
                    'lms_pdf_access' => $pdf,
                    'lms_material_access' => $video || $pdf,
                ])->save();
                $updated++;
            }
        }

        AuditLogger::log('lms_user_bulk_permission', $request->user(), null, [
            'scope' => $data['scope'],
            'field' => $field,
            'grant' => $grant,
            'updated_count' => $updated,
        ]);

        $actionLabel = $grant ? 'Granted' : 'Revoked';
        $permLabel = match ($field) {
            'lms_video_access' => 'Video Access',
            'lms_pdf_access' => 'PDF Access',
            'lms_can_upload_materials' => 'Upload Permission',
            default => 'All Materials Access',
        };

        return back()->with('success', "{$actionLabel} {$permLabel} for {$updated} member(s).");
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:material_categories,name'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        MaterialCategory::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::lower(Str::random(4)),
            'description' => $data['description'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Material category created.');
    }

    private function validateMaterial(Request $request, bool $isCreate, ?LearningMaterial $existing = null): array
    {
        $type = $isCreate
            ? (string) $request->input('material_type')
            : ($existing?->material_type ?? 'pdf');

        $fileRules = $isCreate ? ['required', 'file'] : ['nullable', 'file'];
        if ($type === LearningMaterial::TYPE_VIDEO) {
            $fileRules[] = 'mimes:'.implode(',', LearningMaterialService::VIDEO_MIMES);
            $fileRules[] = 'max:65536';
        } else {
            $fileRules[] = 'mimes:'.implode(',', LearningMaterialService::PDF_MIMES);
            $fileRules[] = 'max:25600';
        }

        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'material_type' => $isCreate ? ['required', Rule::in([LearningMaterial::TYPE_VIDEO, LearningMaterial::TYPE_PDF])] : ['nullable'],
            'file' => $fileRules,
            'thumbnail' => ['nullable', 'image', 'mimes:'.implode(',', LearningMaterialService::THUMB_MIMES), 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:material_categories,id'],
            'course_subject_label' => ['nullable', 'string', 'max:160'],
            'visibility' => ['required', Rule::in([LearningMaterial::VISIBILITY_LIBRARY, LearningMaterial::VISIBILITY_PRIVATE])],
            'status' => ['required', Rule::in([LearningMaterial::STATUS_ACTIVE, LearningMaterial::STATUS_INACTIVE])],
            'allow_download' => ['nullable', 'boolean'],
            'expiry_enabled' => ['nullable', 'boolean'],
            'expires_at' => [
                'nullable',
                Rule::requiredIf(fn () => $request->boolean('expiry_enabled') && ! $request->filled('expiry_date')),
                'date',
            ],
            'expiry_date' => ['nullable', 'date'],
            'expiry_time' => ['nullable', 'date_format:H:i'],
        ]);
    }

    private function resolveExpiresAt(array $data): ?string
    {
        if (empty($data['expiry_enabled'])) {
            return null;
        }

        if (! empty($data['expires_at'])) {
            return \Carbon\Carbon::parse($data['expires_at'], config('app.timezone'))->toDateTimeString();
        }

        if (empty($data['expiry_date'])) {
            return null;
        }

        $time = ! empty($data['expiry_time']) ? $data['expiry_time'].':00' : '23:59:59';

        return \Carbon\Carbon::parse($data['expiry_date'].' '.$time, config('app.timezone'))->toDateTimeString();
    }
}
