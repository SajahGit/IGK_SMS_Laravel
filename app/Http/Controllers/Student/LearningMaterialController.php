<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\MaterialAccessLog;
use App\Models\MaterialCategory;
use App\Models\VideoProgress;
use App\Services\LearningMaterialService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LearningMaterialController extends Controller
{
    public function __construct(
        private readonly LearningMaterialService $service,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($this->service->canAccessLibrary($user), 403, 'Learning materials access is currently disabled for your account.');

        $typeFilter = $request->query('type', $request->query('material_type'));
        $categoryFilter = $request->query('category_id');
        $search = trim((string) $request->query('search', ''));

        $allowedTypes = [];
        if ($user->isAdmin() || $user->lms_video_access) {
            $allowedTypes[] = LearningMaterial::TYPE_VIDEO;
        }
        if ($user->isAdmin() || $user->lms_pdf_access) {
            $allowedTypes[] = LearningMaterial::TYPE_PDF;
        }

        $materials = LearningMaterial::query()
            ->libraryVisible()
            ->whereIn('material_type', $allowedTypes ?: ['__none__'])
            ->with(['category', 'uploader'])
            ->when($typeFilter, fn ($q) => $q->where('material_type', $typeFilter))
            ->when($categoryFilter, fn ($q) => $q->where('category_id', $categoryFilter))
            ->when($search !== '', fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('course_subject_label', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $continueWatching = VideoProgress::query()
            ->where('user_id', $user->id)
            ->where('completed', false)
            ->where('last_position', '>', 0)
            ->whereHas('material', fn ($q) => $q->libraryVisible()->where('material_type', LearningMaterial::TYPE_VIDEO))
            ->with(['material.category'])
            ->latest('updated_at')
            ->take(4)
            ->get();

        $recentlyViewedIds = MaterialAccessLog::query()
            ->where('user_id', $user->id)
            ->whereIn('access_type', ['view', 'stream', 'share_open'])
            ->latest('accessed_at')
            ->limit(20)
            ->pluck('material_id')
            ->unique()
            ->take(4);

        $recentlyViewed = $recentlyViewedIds->isNotEmpty()
            ? LearningMaterial::query()->libraryVisible()->whereIn('id', $recentlyViewedIds)->with('category')->get()
            : collect();

        $categories = MaterialCategory::where('is_active', true)->orderBy('name')->get();

        return view('Student.LearningMaterialsIndex', compact(
            'materials',
            'continueWatching',
            'recentlyViewed',
            'categories',
            'typeFilter',
            'categoryFilter',
            'search'
        ));
    }

    public function show(Request $request, LearningMaterial $material): View|Response
    {
        $user = $request->user();

        if (! $this->service->canViewMaterial($user, $material)) {
            $reason = $material->isExpired() ? 'expired_material' : ($material->isActive() ? 'no_permission' : 'inactive_material');
            $this->service->logAccess($material, $user, 'denied', $request, null, ['reason' => $reason]);
            $status = $reason === 'expired_material' ? 410 : 403;

            return response()->view('LearningMaterials.Blocked', compact('reason', 'material'), $status);
        }

        $this->service->logAccess($material, $user, 'view', $request);

        $progress = $material->isVideo()
            ? VideoProgress::where('material_id', $material->id)->where('user_id', $user->id)->first()
            : null;
        $canDownload = $this->service->canDownload($user, $material);

        return view('LearningMaterials.Viewer', compact('material', 'progress', 'canDownload'));
    }
}
