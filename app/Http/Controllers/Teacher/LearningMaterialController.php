<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\MaterialCategory;
use App\Models\MaterialShareLink;
use App\Services\LearningMaterialService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LearningMaterialController extends Controller
{
    public function __construct(
        private readonly LearningMaterialService $service,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isTeacher() && $user->is_active, 403);

        $materials = LearningMaterial::query()
            ->where('uploaded_by', $user->id)
            ->with(['category', 'shareLinks' => fn ($q) => $q->latest('id')])
            ->withCount(['accessLogs'])
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $settings = $this->service->getGlobalSettings();
        $canUploadVideo = $this->service->canUpload($user, LearningMaterial::TYPE_VIDEO);
        $canUploadPdf = $this->service->canUpload($user, LearningMaterial::TYPE_PDF);

        $stats = [
            'total_uploads' => LearningMaterial::where('uploaded_by', $user->id)->count(),
            'active_uploads' => LearningMaterial::where('uploaded_by', $user->id)->libraryVisible()->count(),
            'total_views' => \App\Models\MaterialAccessLog::whereHas('material', fn ($q) => $q->where('uploaded_by', $user->id))
                ->whereIn('access_type', ['view', 'stream', 'share_open'])->count(),
        ];

        return view('Teacher.LearningMaterialsIndex', compact(
            'materials',
            'settings',
            'canUploadVideo',
            'canUploadPdf',
            'stats'
        ));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($this->service->canUpload($user), 403, 'Your teacher upload permission is currently disabled by the Administrator.');

        $categories = MaterialCategory::where('is_active', true)->orderBy('name')->get();
        $canUploadVideo = $this->service->canUpload($user, LearningMaterial::TYPE_VIDEO);
        $canUploadPdf = $this->service->canUpload($user, LearningMaterial::TYPE_PDF);
        $material = null;

        return view('Teacher.LearningMaterialForm', compact('categories', 'canUploadVideo', 'canUploadPdf', 'material'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $type = (string) $request->input('material_type');
        abort_unless(in_array($type, [LearningMaterial::TYPE_VIDEO, LearningMaterial::TYPE_PDF], true), 422);
        abort_unless($this->service->canUpload($user, $type), 403, 'Uploading this material type is disabled for your account.');

        $fileRules = ['required', 'file'];
        if ($type === LearningMaterial::TYPE_VIDEO) {
            $fileRules[] = 'mimes:'.implode(',', LearningMaterialService::VIDEO_MIMES);
            $fileRules[] = 'max:65536';
        } else {
            $fileRules[] = 'mimes:'.implode(',', LearningMaterialService::PDF_MIMES);
            $fileRules[] = 'max:25600';
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'material_type' => ['required', Rule::in([LearningMaterial::TYPE_VIDEO, LearningMaterial::TYPE_PDF])],
            'file' => $fileRules,
            'thumbnail' => ['nullable', 'image', 'mimes:'.implode(',', LearningMaterialService::THUMB_MIMES), 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:material_categories,id'],
            'course_subject_label' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::in([LearningMaterial::STATUS_ACTIVE, LearningMaterial::STATUS_INACTIVE])],
        ]);

        $fileMeta = $this->service->storeUploadedFile($request->file('file'), $type);
        $thumbPath = $request->hasFile('thumbnail')
            ? $this->service->storeThumbnail($request->file('thumbnail'))
            : null;

        try {
            $material = DB::transaction(function () use ($data, $fileMeta, $thumbPath, $user): LearningMaterial {
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
                    'uploaded_by' => $user->id,
                    'visibility' => LearningMaterial::VISIBILITY_LIBRARY,
                    'status' => $data['status'],
                    'allow_download' => false, // Only Admin can enable downloads
                    'expiry_enabled' => false, // Only Admin controls expiry policy
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(array_filter([$fileMeta['storage_path'], $thumbPath]));
            throw $e;
        }

        AuditLogger::log('lms_teacher_material_created', $material, null, $material->only(['title', 'material_type', 'status']));
        $this->service->notifyAuthorizedUsersOfNewMaterial($material);

        return redirect()->route('teacher.learning-materials.index')
            ->with('success', 'Material uploaded and published to the Learning Materials Library.');
    }

    public function edit(Request $request, LearningMaterial $material): View
    {
        $user = $request->user();
        abort_unless($this->service->canManageMaterial($user, $material), 403);

        $categories = MaterialCategory::where('is_active', true)->orderBy('name')->get();
        $canUploadVideo = $this->service->canUpload($user, LearningMaterial::TYPE_VIDEO);
        $canUploadPdf = $this->service->canUpload($user, LearningMaterial::TYPE_PDF);

        return view('Teacher.LearningMaterialForm', compact('categories', 'canUploadVideo', 'canUploadPdf', 'material'));
    }

    public function update(Request $request, LearningMaterial $material): RedirectResponse
    {
        $user = $request->user();
        abort_unless($this->service->canManageMaterial($user, $material), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'thumbnail' => ['nullable', 'image', 'mimes:'.implode(',', LearningMaterialService::THUMB_MIMES), 'max:2048'],
            'category_id' => ['nullable', 'integer', 'exists:material_categories,id'],
            'course_subject_label' => ['nullable', 'string', 'max:160'],
            'status' => ['required', Rule::in([LearningMaterial::STATUS_ACTIVE, LearningMaterial::STATUS_INACTIVE])],
        ]);

        // Teachers cannot override Admin's download or expiry restrictions
        $updates = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'course_subject_label' => $data['course_subject_label'] ?? null,
            'status' => $data['status'],
        ];

        $oldThumb = null;
        if ($request->hasFile('thumbnail')) {
            $oldThumb = $material->thumbnail_path;
            $updates['thumbnail_path'] = $this->service->storeThumbnail($request->file('thumbnail'));
        }

        $material->update($updates);
        if ($oldThumb) {
            Storage::disk('local')->delete($oldThumb);
        }

        AuditLogger::log('lms_teacher_material_updated', $material);

        return redirect()->route('teacher.learning-materials.index')
            ->with('success', 'Your material has been updated.');
    }

    public function destroy(Request $request, LearningMaterial $material): RedirectResponse
    {
        abort_unless($this->service->canManageMaterial($request->user(), $material), 403);

        $this->service->deleteStoredFiles($material);
        $material->delete();
        AuditLogger::log('lms_teacher_material_deleted', LearningMaterial::class, ['id' => $material->id, 'title' => $material->title], null);

        return redirect()->route('teacher.learning-materials.index')
            ->with('success', 'Material deleted.');
    }

    public function generateShareLink(Request $request, LearningMaterial $material): RedirectResponse
    {
        abort_unless($this->service->canManageMaterial($request->user(), $material), 403);

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

        return back()
            ->with('success', 'Secure share link created (recipients must log in with an authorized account).')
            ->with('generated_share_url', $created['url']);
    }

    public function revokeShareLink(Request $request, MaterialShareLink $shareLink): RedirectResponse
    {
        abort_unless($this->service->canManageMaterial($request->user(), $shareLink->material), 403);
        $shareLink->update(['revoked_at' => now()]);

        return back()->with('success', 'Share link revoked.');
    }
}
