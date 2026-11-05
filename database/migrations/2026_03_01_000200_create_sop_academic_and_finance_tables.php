<?php

use Illuminate\Database\Migrations\Migration;

/**
 * RETIRED - intentionally a no-op.
 *
 * This file belonged to an older, unused parallel schema that re-created tables and
 * columns already made by the 2026_01_01_* migrations (e.g. users.is_active), which
 * broke every test with "duplicate column name". Nothing in the app uses it.
 *
 * It is kept as an empty migration only so that copying this project over an older
 * copy overwrites the old file (a zip/copy cannot delete files). You may safely
 * delete this file; the migration order and schema are the same either way.
 */
return new class extends Migration
{
    public function up(): void
    {
        // intentionally empty
    }

    public function down(): void
    {
        // intentionally empty
    }
};
