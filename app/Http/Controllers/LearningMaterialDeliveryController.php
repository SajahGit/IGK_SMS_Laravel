<?php

namespace App\Http\Controllers;

use App\Models\LearningMaterial;
use App\Models\MaterialShareLink;
use App\Models\VideoProgress;
use App\Services\LearningMaterialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LearningMaterialDeliveryController extends Controller
{
    public function __construct(
        private readonly LearningMaterialService $service,
    ) {}

    public function stream(Request $request, LearningMaterial $material): Response
    {
        $user = $request->user();
        abort_unless($this->service->canViewMaterial($user, $material), 403, 'You are not authorized to access this material.');

        if ($material->isVideo()) {
            // Log stream start (first range request at bytes=0 or non-range)
            $range = (string) $request->header('Range', '');
            if ($range === '' || str_starts_with($range, 'bytes=0-')) {
                $this->service->logAccess($material, $user, 'stream', $request);
            }

            return $this->service->streamVideo($request, $material);
        }

        $this->service->logAccess($material, $user, 'view', $request);

        return $this->service->streamPdfInline($material);
    }

    public function thumbnail(Request $request, LearningMaterial $material): Response
    {
        abort_unless($this->service->canViewMaterial($request->user(), $material), 403);
        abort_unless($material->thumbnail_path && Storage::disk('local')->exists($material->thumbnail_path), 404);

        return response()->file(Storage::disk('local')->path($material->thumbnail_path), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function download(Request $request, LearningMaterial $material): Response
    {
        $user = $request->user();
        if (! $this->service->canDownload($user, $material)) {
            $this->service->logAccess($material, $user, 'denied', $request, null, ['reason' => 'download_disabled_or_expired']);
            abort(403, 'Downloading is disabled for this material or it has expired.');
        }

        $this->service->logAccess($material, $user, 'download', $request);

        return $this->service->downloadFile($material);
    }

    public function saveProgress(Request $request, LearningMaterial $material): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->service->canViewMaterial($user, $material) && $material->isVideo(), 403);

        $data = $request->validate([
            'last_position' => ['required', 'integer', 'min:0', 'max:864000'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:864000'],
            'completed' => ['nullable', 'boolean'],
        ]);

        $duration = (int) ($data['duration'] ?? 0);
        $position = (int) $data['last_position'];
        $completed = (bool) ($data['completed'] ?? ($duration > 0 && $position >= max(1, $duration - 5)));

        $progress = VideoProgress::updateOrCreate(
            ['material_id' => $material->id, 'user_id' => $user->id],
            [
                'last_position' => $position,
                'duration' => $duration,
                'completed' => $completed,
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'saved' => true,
            'last_position' => $progress->last_position,
            'completed' => $progress->completed,
        ]);
    }

    public function openShareLink(Request $request, string $token): Response|View
    {
        $user = $request->user();
        $link = MaterialShareLink::query()
            ->where('token_hash', hash('sha256', $token))
            ->with(['material.category', 'material.uploader'])
            ->first();

        if (! $link) {
            return response()->view('LearningMaterials.Blocked', [
                'reason' => 'invalid_token',
                'material' => null,
            ], 404);
        }

        $check = $this->service->evaluateShareLinkAccess($user, $link);
        if (! $check['allowed']) {
            if ($link->material) {
                $this->service->logAccess($link->material, $user, 'denied', $request, $link->id, ['reason' => $check['reason'] ?? 'denied']);
            }

            $status = in_array($check['reason'] ?? '', ['expired_material', 'expired_link', 'revoked_link'], true) ? 410 : 403;

            return response()->view('LearningMaterials.Blocked', [
                'reason' => $check['reason'] ?? 'no_permission',
                'material' => $link->material,
            ], $status);
        }

        $link->forceFill([
            'last_used_at' => now(),
            'use_count' => $link->use_count + 1,
        ])->save();

        $material = $link->material;
        $this->service->logAccess($material, $user, 'share_open', $request, $link->id);

        $progress = $material->isVideo()
            ? VideoProgress::where('material_id', $material->id)->where('user_id', $user->id)->first()
            : null;
        $canDownload = $this->service->canDownload($user, $material);

        return view('LearningMaterials.Viewer', compact('material', 'progress', 'canDownload', 'link'));
    }
}
