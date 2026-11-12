<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_authors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->timestamps();
        });

        Schema::create('library_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique();
            $table->timestamps();
        });

        Schema::create('library_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('library_authors')->restrictOnDelete();
            $table->foreignId('category_id')->constrained('library_categories')->restrictOnDelete();
            $table->string('title', 200);
            $table->string('isbn', 20)->nullable()->unique();
            $table->unsignedSmallInteger('publication_year')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'title']);
        });

        Schema::create('library_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('library_books')->cascadeOnDelete();
            $table->unsignedInteger('copy_number');
            $table->string('accession_no', 40)->unique();
            $table->string('condition', 20)->default('good');
            $table->string('status', 20)->default('available');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['book_id', 'copy_number']);
            $table->index(['status', 'book_id']);
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('copy_id')->constrained('library_copies')->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->date('due_at');
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->index(['student_id', 'returned_at']);
            $table->index(['due_at', 'returned_at']);
        });

        Schema::create('library_fines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->unique()->constrained('library_loans')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('reason', 160);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['paid_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('library_fines');
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_copies');
        Schema::dropIfExists('library_books');
        Schema::dropIfExists('library_categories');
        Schema::dropIfExists('library_authors');
    }
};
