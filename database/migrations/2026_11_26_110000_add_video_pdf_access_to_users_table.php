<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'lms_video_access')) {
                $table->boolean('lms_video_access')->default(true)->after('lms_material_access');
            }
            if (! Schema::hasColumn('users', 'lms_pdf_access')) {
                $table->boolean('lms_pdf_access')->default(true)->after('lms_video_access');
            }
        });

        // Synchronize existing users' video/pdf flags with lms_material_access
        DB::table('users')->where('lms_material_access', false)->update([
            'lms_video_access' => false,
            'lms_pdf_access' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = array_filter(['lms_video_access', 'lms_pdf_access'], fn ($c) => Schema::hasColumn('users', $c));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
