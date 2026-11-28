<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'lms_material_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('lms_material_access')->default(true)->after('is_active');
                $table->boolean('lms_can_upload_materials')->default(true)->after('lms_material_access');
            });
        }

        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('material_type', 20); // video | pdf
            $table->string('storage_path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('thumbnail_path', 500)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('material_categories')->nullOnDelete();
            $table->string('course_subject_label', 160)->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('visibility', 30)->default('library'); // library | private
            $table->string('status', 20)->default('active'); // active | inactive
            $table->boolean('allow_download')->default(false);
            $table->boolean('expiry_enabled')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['material_type', 'status']);
            $table->index(['status', 'visibility']);
            $table->index('expires_at');
        });

        Schema::create('material_share_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->string('label', 120)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamps();

            $table->index(['material_id', 'revoked_at']);
        });

        Schema::create('material_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('share_link_id')->nullable()->constrained('material_share_links')->nullOnDelete();
            $table->string('access_type', 40);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('accessed_at')->nullable();

            $table->index(['material_id', 'access_type']);
            $table->index(['user_id', 'accessed_at']);
        });

        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('learning_materials')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('last_position')->default(0);
            $table->unsignedInteger('duration')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['material_id', 'user_id']);
        });

        // Seed default categories idempotently
        foreach ([
            ['name' => 'Mathematics', 'slug' => 'mathematics', 'description' => 'Mathematics lectures, problem sets, and formula sheets'],
            ['name' => 'Science & Lab', 'slug' => 'science-lab', 'description' => 'Physics, Chemistry, Biology videos and study guides'],
            ['name' => 'Languages & Literature', 'slug' => 'languages-literature', 'description' => 'English, Tamil, Sinhala, and reading resources'],
            ['name' => 'Computing & ICT', 'slug' => 'computing-ict', 'description' => 'Computer Science tutorials and reference manuals'],
            ['name' => 'General Study Guides', 'slug' => 'general-study-guides', 'description' => 'Revision packs, past-paper guides, and orientation videos'],
        ] as $cat) {
            DB::table('material_categories')->insertOrIgnore([
                'name' => $cat['name'],
                'slug' => $cat['slug'],
                'description' => $cat['description'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('video_progress');
        Schema::dropIfExists('material_access_logs');
        Schema::dropIfExists('material_share_links');
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('material_categories');

        if (Schema::hasColumn('users', 'lms_material_access')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['lms_material_access', 'lms_can_upload_materials']);
            });
        }
    }
};
