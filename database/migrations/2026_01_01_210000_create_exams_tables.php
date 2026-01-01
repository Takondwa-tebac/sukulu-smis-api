<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Exam Types (e.g., Mid-Term, End of Term, Quiz, Assignment)
        Schema::create('exam_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(100);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'is_active']);
        });

        // Exams
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('term_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('exam_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('max_score', 5, 2)->default(100);
            $table->enum('status', ['draft', 'scheduled', 'in_progress', 'completed', 'published', 'archived'])->default('draft');
            $table->text('instructions')->nullable();
            $table->json('settings')->nullable();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'term_id']);
            $table->index(['school_id', 'status']);
        });

        // Exam Subjects (subjects included in an exam)
        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_subject_id')->constrained()->cascadeOnDelete();
            $table->date('exam_date')->nullable();
            $table->time('start_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->decimal('max_score', 5, 2)->default(100);
            $table->string('venue')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'marks_entered', 'moderated', 'approved', 'locked'])->default('pending');
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'class_subject_id']);
        });

        // Student Marks
        Schema::create('student_marks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('exam_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade', 10)->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->string('absent_reason')->nullable();
            $table->enum('status', ['draft', 'submitted', 'moderated', 'approved', 'locked'])->default('draft');
            $table->foreignUuid('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->foreignUuid('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->decimal('original_score', 5, 2)->nullable();
            $table->text('moderation_reason')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['exam_subject_id', 'student_id']);
            $table->index(['school_id', 'student_id']);
            $table->index(['exam_subject_id', 'status']);
        });

        // Continuous Assessment (CA) Components
        Schema::create('assessment_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 20);
            $table->text('description')->nullable();
            $table->decimal('weight', 5, 2)->default(100);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'code']);
        });

        // Continuous Assessments
        Schema::create('continuous_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('term_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_subject_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('assessment_component_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('assessment_date');
            $table->decimal('max_score', 5, 2)->default(100);
            $table->enum('status', ['draft', 'marks_entered', 'submitted', 'approved', 'locked'])->default('draft');
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'term_id']);
            $table->index(['class_subject_id', 'assessment_component_id'], 'ca_class_subject_component_idx');
        });

        // CA Marks
        Schema::create('continuous_assessment_marks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('continuous_assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_absent')->default(false);
            $table->enum('status', ['draft', 'submitted', 'approved'])->default('draft');
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['continuous_assessment_id', 'student_id'], 'ca_marks_ca_student_unique');
        });

        // Mark Moderation Logs
        Schema::create('mark_moderation_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_mark_id')->constrained()->cascadeOnDelete();
            $table->decimal('original_score', 5, 2);
            $table->decimal('moderated_score', 5, 2);
            $table->text('reason');
            $table->foreignUuid('moderated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('student_mark_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_moderation_logs');
        Schema::dropIfExists('continuous_assessment_marks');
        Schema::dropIfExists('continuous_assessments');
        Schema::dropIfExists('assessment_components');
        Schema::dropIfExists('student_marks');
        Schema::dropIfExists('exam_subjects');
        Schema::dropIfExists('exams');
        Schema::dropIfExists('exam_types');
    }
};
