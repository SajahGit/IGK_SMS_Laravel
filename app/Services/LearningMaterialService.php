<?php

namespace App\Services;

use App\Models\LearningMaterial;
use App\Models\MaterialAccessLog;
use App\Models\MaterialShareLink;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\LearningMaterialAccessNotification;
use App\Notifications\LearningMaterialPublishedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LearningMaterialService
{
    public const VIDEO_MIMES = ['mp4', 'webm', 'ogg', 'mov'];
    public const PDF_MIMES = ['pdf'];
    public const THUMB_MIMES = ['jpg', 'jpeg', 'png', 'webp'];

    public function getGlobalSettings(): array
    {
        return [
            'enabled' => (bool) Setting::get('lms_materials.enabled', true),
            'student_access' => (bool) Setting::get('lms_materials.student_access', true),
            'teacher_uploads' => (bool) Setting::get('lms_materials.teacher_uploads', true),
            'teacher_video_uploads' => (bool) Setting::get('lms_materials.teacher_video_uploads', true),
            'teacher_pdf_uploads' => (bool) Setting::get('lms_materials.teacher_pdf_uploads', true),
        ];
    }

    public function updateGlobalSettings(array $settings): void
    {
        Setting::set('lms_materials.enabled', (bool) ($settings['enabled'] ?? false), 'lms');
        Setting::set('lms_materials.student_access', (bool) ($settings['student_access'] ?? false), 'lms');
        Setting::set('lms_materials.teacher_uploads', (bool) ($settings['teacher_uploads'] ?? false), 'lms');
        Setting::set('lms_materials.teacher_video_uploads', (bool) ($settings['teacher_video_uploads'] ?? false), 'lms');
        Setting::set('lms_materials.teacher_pdf_uploads', (bool) ($settings['teacher_pdf_uploads'] ?? false), 'lms');
    }

    public function canAccessLibrary(User $user): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $settings = $this->getGlobalSettings();
        if (! $settings['enabled']) {
            return false;
        }

        if (! $user->lms_material_access || (! $user->lms_video_access && ! $user->lms_pdf_access)) {
            return false;
        }

        if ($user->isStudent() && ! $settings['student_access']) {
            return false;
        }

        return true;
    }

    public function canUpload(User $user, ?string $materialType = null): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isTeacher() || ! $user->lms_can_upload_materials) {
            return false;
        }

        $settings = $this->getGlobalSettings();
        if (! $settings['enabled'] || ! $settings['teacher_uploads']) {
            return false;
        }

        if ($materialType === LearningMaterial::TYPE_VIDEO && ! $settings['teacher_video_uploads']) {
            return false;
        }

        if ($materialType === LearningMaterial::TYPE_PDF && ! $settings['teacher_pdf_uploads']) {
            return false;
        }

        return true;
    }

    public function canManageMaterial(User $user, LearningMaterial $material): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isTeacher()
            && $user->lms_can_upload_materials
            && (int) $material->uploaded_by === (int) $user->id;
    }

    public function canViewMaterial(User $user, LearningMaterial $material): bool
    {
        if (! $user->is_active) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $this->canAccessLibrary($user)) {
            return false;
        }

        if ($user->isTeacher() && (int) $material->uploaded_by === (int) $user->id) {
            return true;
        }

        $typeAllowed = $material->isVideo() ? (bool) $user->lms_video_access : (bool) $user->lms_pdf_access;
        if (! $typeAllowed) {
            return false;
        }

        if ($material->isAccessible() && $material->visibility === LearningMaterial::VISIBILITY_LIBRARY) {
            return true;
        }

        return false;
    }

    /**
     * Validate share link access strictly after authentication.
     *
     * @return array{allowed: bool, reason?: string}
     */
    public function evaluateShareLinkAccess(User $user, MaterialShareLink $link): array
    {
        if (! $user->is_active || ! $this->canAccessLibrary($user)) {
            return ['allowed' => false, 'reason' => 'no_permission'];
        }

        if ($link->isRevoked()) {
            return ['allowed' => false, 'reason' => 'revoked_link'];
        }

        if ($link->isExpired()) {
            return ['allowed' => false, 'reason' => 'expired_link'];
        }

        $material = $link->material;
        if (! $material) {
            return ['allowed' => false, 'reason' => 'missing_material'];
        }

        if (! $user->isAdmin()) {
            $typeAllowed = $material->isVideo() ? (bool) $user->lms_video_access : (bool) $user->lms_pdf_access;
            if (! $typeAllowed) {
                return ['allowed' => false, 'reason' => 'no_permission'];
            }
        }

        if (! $material->isActive() && ! $user->isAdmin()) {
            return ['allowed' => false, 'reason' => 'inactive_material'];
        }

        if ($material->isExpired() && ! $user->isAdmin()) {
            return ['allowed' => false, 'reason' => 'expired_material'];
        }

        return ['allowed' => true];
    }

    public function canDownload(User $user, LearningMaterial $material): bool
    {
        if (! $user->is_active || ! $material->allow_download) {
            return false;
        }

        return $this->canViewMaterial($user, $material);
    }

    public function storeUploadedFile(UploadedFile $file, string $materialType): array
    {
        $folder = $materialType === LearningMaterial::TYPE_VIDEO
            ? 'learning_materials/videos'
            : 'learning_materials/pdfs';

        $ext = strtolower($file->getClientOriginalExtension() ?: ($materialType === LearningMaterial::TYPE_VIDEO ? 'mp4' : 'pdf'));
        $filename = Str::uuid()->toString().'.'.$ext;
        $path = $file->storeAs($folder, $filename, 'local');

        return [
            'storage_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: ($materialType === LearningMaterial::TYPE_VIDEO ? 'video/mp4' : 'application/pdf'),
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function storeThumbnail(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$ext;

        return $file->storeAs('learning_materials/thumbnails', $filename, 'local');
    }

    public function deleteStoredFiles(LearningMaterial $material): void
    {
        if ($material->storage_path && Storage::disk('local')->exists($material->storage_path)) {
            Storage::disk('local')->delete($material->storage_path);
        }
        if ($material->thumbnail_path && Storage::disk('local')->exists($material->thumbnail_path)) {
            Storage::disk('local')->delete($material->thumbnail_path);
        }
    }

    public function createShareLink(LearningMaterial $material, User $creator, ?string $label = null, ?string $expiresAt = null): array
    {
        $plainToken = bin2hex(random_bytes(32));
        $link = MaterialShareLink::create([
            'material_id' => $material->id,
            'token_hash' => hash('sha256', $plainToken),
            'label' => $label ?: null,
            'created_by' => $creator->id,
            'expires_at' => $expiresAt ?: null,
        ]);

        return [
            'link' => $link,
            'plain_token' => $plainToken,
            'url' => route('secure-material.show', ['token' => $plainToken]),
        ];
    }

    public function logAccess(
        LearningMaterial $material,
        ?User $user,
        string $accessType,
        ?Request $request = null,
        ?int $shareLinkId = null,
        array $metadata = []
    ): void {
        MaterialAccessLog::create([
            'material_id' => $material->id,
            'user_id' => $user?->id,
            'share_link_id' => $shareLinkId,
            'access_type' => $accessType,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent() ? Str::limit((string) $request->userAgent(), 250, '') : null,
            'metadata' => $metadata !== [] ? $metadata : null,
            'accessed_at' => now(),
        ]);
    }

    /**
     * Stream private video with HTTP Range (206 Partial Content) support and low memory usage.
     */
    public function streamVideo(Request $request, LearningMaterial $material): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($material->storage_path), 404, 'Video file not found.');

        $fullPath = Storage::disk('local')->path($material->storage_path);
        $size = filesize($fullPath);
        $mime = $material->mime_type ?: 'video/mp4';

        $start = 0;
        $end = $size - 1;
        $status = 200;

        $range = $request->header('Range');
        if ($range && preg_match('/bytes=(\d*)-(\d*)/i', $range, $matches)) {
            $reqStart = $matches[1] !== '' ? (int) $matches[1] : null;
            $reqEnd = $matches[2] !== '' ? (int) $matches[2] : null;

            if ($reqStart === null && $reqEnd !== null) {
                $start = max(0, $size - $reqEnd);
                $end = $size - 1;
            } elseif ($reqStart !== null) {
                $start = $reqStart;
                $end = $reqEnd !== null ? min($reqEnd, $size - 1) : $size - 1;
            }

            if ($start > $end || $start >= $size) {
                return response()->stream(static function () {}, 416, [
                    'Content-Range' => "bytes */{$size}",
                ]);
            }

            $status = 206;
        }

        $length = ($end - $start) + 1;

        $headers = [
            'Content-Type' => $mime,
            'Content-Length' => (string) $length,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Disposition' => 'inline; filename="'.addslashes(basename($material->original_name)).'"',
        ];

        if ($status === 206) {
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        return response()->stream(function () use ($fullPath, $start, $length): void {
            $handle = fopen($fullPath, 'rb');
            if ($handle === false) {
                return;
            }

            try {
                fseek($handle, $start);
                $remaining = $length;
                $chunkSize = 65536; // 64 KB chunks to keep memory flat

                while ($remaining > 0 && ! feof($handle)) {
                    $readLength = min($chunkSize, $remaining);
                    $buffer = fread($handle, $readLength);
                    if ($buffer === false) {
                        break;
                    }
                    echo $buffer;
                    flush();
                    $remaining -= strlen($buffer);
                }
            } finally {
                fclose($handle);
            }
        }, $status, $headers);
    }

    public function streamPdfInline(LearningMaterial $material): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($material->storage_path), 404, 'PDF file not found.');
        $fullPath = Storage::disk('local')->path($material->storage_path);

        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.addslashes(basename($material->original_name)).'"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function downloadFile(LearningMaterial $material): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($material->storage_path), 404, 'Material file not found.');
        $fullPath = Storage::disk('local')->path($material->storage_path);

        return response()->download($fullPath, $material->original_name, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function notifyAuthorizedUsersOfNewMaterial(LearningMaterial $material): void
    {
        if (! $material->isAccessible() || $material->visibility !== LearningMaterial::VISIBILITY_LIBRARY) {
            return;
        }

        $typeColumn = $material->isVideo() ? 'lms_video_access' : 'lms_pdf_access';

        User::query()
            ->where('is_active', true)
            ->where('id', '!=', $material->uploaded_by)
            ->where('lms_material_access', true)
            ->where($typeColumn, true)
            ->chunkById(100, function ($users) use ($material) {
                Notification::send($users, new LearningMaterialPublishedNotification($material));
            });
    }

    public function notifyUserPermissionChange(User $user, string $field, bool $granted): void
    {
        if (! $user->is_active) {
            return;
        }

        $label = match ($field) {
            'lms_video_access' => 'Video Learning Materials access',
            'lms_pdf_access' => 'PDF Learning Materials access',
            'lms_can_upload_materials' => 'Learning Materials upload permission',
            default => 'Learning Materials Library access',
        };

        $state = $granted ? 'enabled' : 'disabled';
        $relativeUrl = $field === 'lms_can_upload_materials' && $user->isTeacher()
            ? route('teacher.learning-materials.index', [], false)
            : route('learning-materials.library', [], false);

        $user->notify(new LearningMaterialAccessNotification(
            ucfirst($label)." {$state}",
            "An administrator has {$state} your {$label}.",
            $relativeUrl
        ));
    }
}
