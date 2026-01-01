<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Report Cards
        Schema::create('report_cards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('term_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('stream_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('average_score', 5, 2)->nullable();
            $table->integer('position')->nullable();
            $table->integer('total_students')->nullable();
            $table->string('overall_grade', 10)->nullable();
            $table->text('class_teacher_remarks')->nullable();
            $table->text('head_teacher_remarks')->nullable();
            $table->date('next_term_begins')->nullable();
            $table->decimal('next_term_fees', 12, 2)->nullable();
            $table->enum('status', ['draft', 'generated', 'approved', 'published'])->default('draft');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id', 'term_id'], 'report_card_unique');
            $table->index(['school_id', 'academic_year_id', 'term_id']);
            $table->index(['school_id', 'class_id', 'term_id']);
        });

        // Report Card Subjects (individual subject results)
        Schema::create('report_card_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_card_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('ca_score', 5, 2)->nullable();
            $table->decimal('exam_score', 5, 2)->nullable();
            $table->decimal('total_score', 5, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->integer('position')->nullable();
            $table->text('remarks')->nullable();
            $table->string('teacher_initials', 10)->nullable();
            $table->timestamps();

            $table->unique(['report_card_id', 'subject_id']);
        });

        // Report Templates
        Schema::create('report_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->enum('type', ['term_report', 'annual_report', 'transcript', 'progress_report'])->default('term_report');
            $table->json('layout_config')->nullable();
            $table->json('sections')->nullable();
            $table->boolean('include_position')->default(true);
            $table->boolean('include_class_average')->default(true);
            $table->boolean('include_attendance')->default(false);
            $table->boolean('include_conduct')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });

        // Student Transcripts
        Schema::create('student_transcripts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->string('transcript_number')->unique();
            $table->date('issue_date');
            $table->enum('type', ['partial', 'complete', 'official'])->default('complete');
            $table->decimal('cumulative_gpa', 4, 2)->nullable();
            $table->string('graduation_status')->nullable();
            $table->date('graduation_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'generated', 'approved', 'issued'])->default('draft');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
        });

        // Transcript Records (academic history entries)
        Schema::create('transcript_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_transcript_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('class_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->decimal('gpa_points', 4, 2)->nullable();
            $table->integer('credit_hours')->nullable();
            $table->timestamps();

            $table->index('student_transcript_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transcript_records');
        Schema::dropIfExists('student_transcripts');
        Schema::dropIfExists('report_templates');
        Schema::dropIfExists('report_card_subjects');
        Schema::dropIfExists('report_cards');
    }
};
