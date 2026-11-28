<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\MaterialCategory;
use App\Models\MaterialShareLink;
use App\Models\SchoolClass;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use App\Models\VideoProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearningMaterialsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role, array $overrides = []): User
    {
        static $counter = 1;
        $num = $counter++;

        return User::create(array_merge([
            'name' => "{$role} User {$num}",
            'email' => strtolower(str_replace(' ', '_', $role))."_{$num}@example.com",
            'password' => Hash::make('password123'),
            'role' => $role,
            'is_active' => true,
            'lms_material_access' => true,
            'lms_can_upload_materials' => true,
        ], $overrides));
    }

    public function test_admin_can_upload_video_and_pdf_to_private_storage_and_manage_lifecycle(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser('Institution Admin');
        $category = MaterialCategory::firstOrCreate(['slug' => 'science'], ['name' => 'Science', 'is_active' => true]);

        // Upload Video
        $videoFile = UploadedFile::fake()->create('lecture.mp4', 1024, 'video/mp4');
        $thumbFile = UploadedFile::fake()->image('thumb.jpg', 320, 180);

        $response = $this->actingAs($admin)->post(route('admin.learning-materials.store'), [
            'title' => 'Physics Kinematics Lecture',
            'description' => 'Introductory mechanics lecture.',
            'material_type' => 'video',
            'file' => $videoFile,
            'thumbnail' => $thumbFile,
            'category_id' => $category->id,
            'course_subject_label' => 'Grade 11 Physics',
            'visibility' => 'library',
            'allow_download' => '1',
            'status' => 'active',
        ]);
        $response->assertRedirect(route('admin.learning-materials.index'));

        $video = LearningMaterial::where('title', 'Physics Kinematics Lecture')->firstOrFail();
        $this->assertSame('video', $video->material_type);
        $this->assertTrue($video->allow_download);
        Storage::disk('local')->assertExists($video->storage_path);
        Storage::disk('local')->assertExists($video->thumbnail_path);

        // Upload PDF with expiry_enabled + expires_at (datetime-local format)
        $pdfFile = UploadedFile::fake()->create('notes.pdf', 512, 'application/pdf');
        $this->actingAs($admin)->post(route('admin.learning-materials.store'), [
            'title' => 'Kinematics Formula Sheet',
            'material_type' => 'pdf',
            'file' => $pdfFile,
            'category_id' => $category->id,
            'visibility' => 'library',
            'expiry_enabled' => '1',
            'expires_at' => '2027-01-15T10:30',
            'status' => 'active',
        ])->assertRedirect(route('admin.learning-materials.index'));

        $pdf = LearningMaterial::where('title', 'Kinematics Formula Sheet')->firstOrFail();
        $this->assertFalse($pdf->allow_download);
        $this->assertTrue($pdf->expiry_enabled);
        $this->assertNotNull($pdf->expires_at);
        Storage::disk('local')->assertExists($pdf->storage_path);

        // Toggle Status & Download & Expire Now
        $this->actingAs($admin)->post(route('admin.learning-materials.toggle-status', $video))->assertRedirect();
        $this->assertSame('inactive', $video->fresh()->status);

        $this->actingAs($admin)->post(route('admin.learning-materials.toggle-download', $video))->assertRedirect();
        $this->assertFalse($video->fresh()->allow_download);

        $this->actingAs($admin)->post(route('admin.learning-materials.expire-now', $video))->assertRedirect();
        $this->assertTrue($video->fresh()->isExpired());
        // Expiring a video must NEVER delete the DB record or private file
        $this->assertNotNull(LearningMaterial::find($video->id));
        Storage::disk('local')->assertExists($video->storage_path);

        // Render Admin index & create pages without view errors, including non-teacher/student members
        $librarian = $this->makeUser('Librarian', ['lms_material_access' => false]);
        $this->actingAs($admin)->get(route('admin.learning-materials.index'))
            ->assertOk()
            ->assertSee('Physics Kinematics Lecture')
            ->assertSee($librarian->name);
        $this->actingAs($admin)->post(route('admin.learning-materials.users.toggle-access', $librarian), [
            'permission_field' => 'lms_material_access',
        ])->assertRedirect();
        $this->assertTrue($librarian->fresh()->lms_material_access);
        $this->actingAs($admin)->get(route('admin.learning-materials.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.learning-materials.edit', $video))->assertOk();
    }

    public function test_teacher_permissions_and_restrictions_are_enforced(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser('Institution Admin');
        $teacher1 = $this->makeUser('Teacher');
        $teacher2 = $this->makeUser('Teacher');

        // Teacher 1 uploads a PDF while enabled
        $pdfFile = UploadedFile::fake()->create('teacher_notes.pdf', 256, 'application/pdf');
        $this->actingAs($teacher1)->post(route('teacher.learning-materials.store'), [
            'title' => 'Teacher 1 Notes',
            'material_type' => 'pdf',
            'file' => $pdfFile,
            'status' => 'active',
            // Attempt to override admin-only fields (should be ignored)
            'allow_download' => 1,
            'expiry_enabled' => 1,
        ])->assertRedirect(route('teacher.learning-materials.index'));

        $material = LearningMaterial::where('title', 'Teacher 1 Notes')->firstOrFail();
        $this->assertSame($teacher1->id, $material->uploaded_by);
        $this->assertFalse($material->expiry_enabled);

        // Render Teacher index, create & edit pages without view errors
        $this->actingAs($teacher1)->get(route('teacher.learning-materials.index'))
            ->assertOk()
            ->assertSee('Teacher 1 Notes');
        $this->actingAs($teacher1)->get(route('teacher.learning-materials.create'))->assertOk();
        $this->actingAs($teacher1)->get(route('teacher.learning-materials.edit', $material))->assertOk();

        // Teacher 2 cannot edit or delete Teacher 1's material
        $this->actingAs($teacher2)->get(route('teacher.learning-materials.edit', $material))->assertForbidden();
        $this->actingAs($teacher2)->delete(route('teacher.learning-materials.destroy', $material))->assertForbidden();

        // Teacher cannot access Admin management routes
        $this->actingAs($teacher1)->get(route('admin.learning-materials.index'))->assertForbidden();

        // Admin disables teacher PDF upload globally
        Setting::set('lms_materials.teacher_pdf_uploads', '0', 'lms');
        $pdfFile2 = UploadedFile::fake()->create('blocked.pdf', 128, 'application/pdf');
        $this->actingAs($teacher1)->post(route('teacher.learning-materials.store'), [
            'title' => 'Blocked Upload',
            'material_type' => 'pdf',
            'file' => $pdfFile2,
            'status' => 'active',
        ])->assertForbidden();
    }

    public function test_student_library_streaming_range_progress_and_download_controls(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser('Institution Admin');
        $student = $this->makeUser('Student');

        $videoBytes = str_repeat('0123456789ABCDEF', 256); // 4096 bytes
        $videoPath = 'learning-materials/videos/sample.mp4';
        Storage::disk('local')->put($videoPath, $videoBytes);

        $video = LearningMaterial::create([
            'title' => 'Sample Streaming Video',
            'material_type' => 'video',
            'storage_path' => $videoPath,
            'original_name' => 'sample.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => strlen($videoBytes),
            'visibility' => 'library',
            'allow_download' => false,
            'status' => 'active',
            'uploaded_by' => $admin->id,
        ]);

        // Student can browse Common Library and view video page
        $this->actingAs($student)->get(route('learning-materials.library'))
            ->assertOk()
            ->assertSee('Sample Streaming Video');

        $this->actingAs($student)->get(route('learning-materials.view', $video))
            ->assertOk();

        // HTTP Range 206 Partial Content streaming check
        $rangeResponse = $this->actingAs($student)
            ->withHeaders(['Range' => 'bytes=0-1023'])
            ->get(route('learning-materials.stream', $video));
        $rangeResponse->assertStatus(206);
        $rangeResponse->assertHeader('Content-Range', 'bytes 0-1023/4096');

        // Download blocked when allow_download is OFF
        $this->actingAs($student)->get(route('learning-materials.download', $video))->assertForbidden();

        // Save playback progress
        $this->actingAs($student)->postJson(route('learning-materials.progress', $video), [
            'last_position' => 120,
            'duration' => 300,
            'completed' => false,
        ])->assertOk()->assertJson(['saved' => true]);

        $this->assertDatabaseHas('video_progress', [
            'material_id' => $video->id,
            'user_id' => $student->id,
            'last_position' => 120,
            'completed' => false,
        ]);

        // Enable download -> download succeeds
        $video->update(['allow_download' => true]);
        $this->flushHeaders();
        $this->actingAs($student)->get(route('learning-materials.download', $video))->assertOk();

        // Student cannot access Admin or Teacher panels
        $this->actingAs($student)->get(route('admin.learning-materials.index'))->assertForbidden();
        $this->actingAs($student)->get(route('teacher.learning-materials.index'))->assertForbidden();
    }

    public function test_secure_share_links_require_login_active_permission_and_non_revoked_non_expired_state(): void
    {
        Storage::fake('local');
        $admin = $this->makeUser('Institution Admin');
        $student = $this->makeUser('Student');
        $blockedStudent = $this->makeUser('Student', ['lms_material_access' => false]);

        $pdfPath = 'learning-materials/pdfs/guide.pdf';
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 sample');

        $material = LearningMaterial::create([
            'title' => 'Shared Study Guide',
            'material_type' => 'pdf',
            'storage_path' => $pdfPath,
            'original_name' => 'guide.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 15,
            'visibility' => 'private',
            'allow_download' => true,
            'status' => 'active',
            'uploaded_by' => $admin->id,
        ]);

        // Admin generates share link
        $this->actingAs($admin)->post(route('admin.learning-materials.share-links.store', $material))
            ->assertRedirect()
            ->assertSessionHas('generated_share_url');

        $shareUrl = session('generated_share_url');
        $token = basename(parse_url($shareUrl, PHP_URL_PATH));

        // Unauthenticated visitor is redirected to login
        auth()->logout();
        $this->get($shareUrl)->assertRedirect(route('login'));

        // Student without lms_material_access gets 403
        $this->actingAs($blockedStudent)->get($shareUrl)->assertForbidden();

        // Authorized student opens share link successfully
        $this->actingAs($student)->get($shareUrl)
            ->assertOk()
            ->assertSee('Shared Study Guide');

        // Invalid token returns 404
        $this->actingAs($student)->get(route('secure-material.show', 'nonexistent-token-123'))->assertNotFound();

        // Revoke share link -> returns 410 Gone
        $link = MaterialShareLink::firstOrFail();
        $this->actingAs($admin)->post(route('admin.learning-materials.share-links.revoke', $link))->assertRedirect();
        $this->actingAs($student)->get($shareUrl)->assertStatus(410);
    }

    public function test_admin_can_bulk_grant_and_revoke_access_by_selected_role_and_class(): void
    {
        $admin = $this->makeUser('Institution Admin');
        $branch = \App\Models\Branch::create(['name' => 'Main Campus', 'code' => 'MAIN', 'is_main' => true, 'is_active' => true]);
        $class = SchoolClass::create(['name' => 'Grade 10', 'section' => 'A', 'branch_id' => $branch->id]);

        $studentUser1 = $this->makeUser('Student', ['lms_material_access' => false]);
        $studentUser2 = $this->makeUser('Student', ['lms_material_access' => false]);
        Student::create([
            'user_id' => $studentUser1->id,
            'class_id' => $class->id,
            'branch_id' => $branch->id,
            'admission_no' => 'ADM-1001',
            'admission_date' => now()->toDateString(),
            'status' => 'active',
        ]);
        Student::create([
            'user_id' => $studentUser2->id,
            'class_id' => $class->id,
            'branch_id' => $branch->id,
            'admission_no' => 'ADM-1002',
            'admission_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $librarian = $this->makeUser('Librarian', ['lms_material_access' => false]);

        // Bulk grant by class
        $this->actingAs($admin)->post(route('admin.learning-materials.users.bulk-access'), [
            'scope' => 'class',
            'class_id' => $class->id,
            'permission_field' => 'lms_material_access',
            'grant' => '1',
        ])->assertRedirect();

        $this->assertTrue($studentUser1->fresh()->lms_material_access);
        $this->assertTrue($studentUser2->fresh()->lms_material_access);
        $this->assertFalse($librarian->fresh()->lms_material_access);

        // Bulk grant by role (Librarian)
        $this->actingAs($admin)->post(route('admin.learning-materials.users.bulk-access'), [
            'scope' => 'role',
            'target_role' => 'Librarian',
            'permission_field' => 'lms_material_access',
            'grant' => '1',
        ])->assertRedirect();
        $this->assertTrue($librarian->fresh()->lms_material_access);

        // Bulk revoke by selected user_ids
        $this->actingAs($admin)->post(route('admin.learning-materials.users.bulk-access'), [
            'scope' => 'selected',
            'user_ids' => [$studentUser1->id, $librarian->id],
            'permission_field' => 'lms_material_access',
            'grant' => '0',
        ])->assertRedirect();
        $this->assertFalse($studentUser1->fresh()->lms_material_access);
        $this->assertFalse($librarian->fresh()->lms_material_access);
        $this->assertTrue($studentUser2->fresh()->lms_material_access);

        // Video-only access: student sees video, blocked from PDF & PDF share link
        Storage::fake('local');
        Storage::disk('local')->put('learning-materials/videos/v.mp4', 'video-bytes');
        Storage::disk('local')->put('learning-materials/pdfs/p.pdf', '%PDF-1.4');

        $video = LearningMaterial::create([
            'title' => 'Only Video Item',
            'material_type' => 'video',
            'storage_path' => 'learning-materials/videos/v.mp4',
            'original_name' => 'v.mp4',
            'mime_type' => 'video/mp4',
            'file_size' => 11,
            'visibility' => 'library',
            'status' => 'active',
            'uploaded_by' => $admin->id,
        ]);
        $pdf = LearningMaterial::create([
            'title' => 'Hidden PDF Item',
            'material_type' => 'pdf',
            'storage_path' => 'learning-materials/pdfs/p.pdf',
            'original_name' => 'p.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 8,
            'visibility' => 'library',
            'status' => 'active',
            'uploaded_by' => $admin->id,
        ]);

        $videoOnlyStudent = $this->makeUser('Student', [
            'lms_material_access' => true,
            'lms_video_access' => true,
            'lms_pdf_access' => false,
        ]);

        $this->actingAs($videoOnlyStudent)->get(route('learning-materials.library'))
            ->assertOk()
            ->assertSee('Only Video Item')
            ->assertDontSee('Hidden PDF Item');

        $this->actingAs($videoOnlyStudent)->get(route('learning-materials.view', $video))->assertOk();
        $this->actingAs($videoOnlyStudent)->get(route('learning-materials.view', $pdf))->assertForbidden();
        $this->actingAs($videoOnlyStudent)->get(route('learning-materials.stream', $pdf))->assertForbidden();
    }
}
