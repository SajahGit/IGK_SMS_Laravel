<?php

namespace Database\Seeders;

use App\Models\LearningMaterial;
use App\Models\MaterialCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class LearningMaterialsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $uploader = User::whereIn('role', ['Institution Admin', 'Super Admin', 'Teacher'])->first();
        if (! $uploader) {
            return;
        }

        $science = MaterialCategory::firstOrCreate(
            ['slug' => 'science'],
            ['name' => 'Science', 'is_active' => true]
        );
        $math = MaterialCategory::firstOrCreate(
            ['slug' => 'mathematics'],
            ['name' => 'Mathematics', 'is_active' => true]
        );

        // Compact minimal valid PDF sample stored privately
        $pdfPath = 'learning-materials/pdfs/sample-study-guide.pdf';
        $pdfContent = "%PDF-1.4\n1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj\n4 0 obj<< /Length 55 >>stream\nBT /F1 18 Tf 72 700 Td (IGK LMS Sample Study Guide PDF) Tj ET\nendstream\nendobj\n5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\nxref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000241 00000 n \n0000000346 00000 n \ntrailer<< /Size 6 /Root 1 0 R >>\nstartxref\n416\n%%EOF\n";
        Storage::disk('local')->put($pdfPath, $pdfContent);

        LearningMaterial::updateOrCreate(
            ['storage_path' => $pdfPath],
            [
                'title' => 'Algebra & Equations Formula Guide',
                'description' => 'Compact quick-reference PDF formula sheet for students.',
                'material_type' => LearningMaterial::TYPE_PDF,
                'original_name' => 'sample-study-guide.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => strlen($pdfContent),
                'category_id' => $math->id,
                'course_subject_label' => 'Mathematics',
                'uploaded_by' => $uploader->id,
                'visibility' => LearningMaterial::VISIBILITY_LIBRARY,
                'status' => LearningMaterial::STATUS_ACTIVE,
                'allow_download' => true,
                'expiry_enabled' => false,
                'expires_at' => null,
            ]
        );

        // Compact sample WebM/MP4 placeholder stored privately
        $videoPath = 'learning-materials/videos/sample-physics-intro.mp4';
        if (! Storage::disk('local')->exists($videoPath)) {
            Storage::disk('local')->put($videoPath, str_repeat("\x00\x00\x00\x18ftypmp42", 64));
        }

        LearningMaterial::updateOrCreate(
            ['storage_path' => $videoPath],
            [
                'title' => 'Introduction to Motion & Forces',
                'description' => 'Sample lecture entry in the Common Learning Materials Library.',
                'material_type' => LearningMaterial::TYPE_VIDEO,
                'original_name' => 'sample-physics-intro.mp4',
                'mime_type' => 'video/mp4',
                'file_size' => Storage::disk('local')->size($videoPath),
                'category_id' => $science->id,
                'course_subject_label' => 'Physics',
                'uploaded_by' => $uploader->id,
                'visibility' => LearningMaterial::VISIBILITY_LIBRARY,
                'status' => LearningMaterial::STATUS_ACTIVE,
                'allow_download' => false,
                'expiry_enabled' => false,
                'expires_at' => null,
            ]
        );
    }
}
