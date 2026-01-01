<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Time Periods (e.g., Period 1: 8:00-8:40)
        Schema::create('time_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', ['lesson', 'break', 'assembly', 'lunch', 'other'])->default('lesson');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'sort_order']);
        });

        // Timetables (master timetable per class/term)
        Schema::create('timetables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('term_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('stream_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name')->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'academic_year_id', 'term_id']);
            $table->index(['school_id', 'class_id']);
        });

        // Timetable Slots (individual lesson slots)
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('timetable_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('time_period_id')->constrained()->cascadeOnDelete();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->foreignUuid('class_subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('room')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['timetable_id', 'time_period_id', 'day_of_week'], 'timetable_slot_unique');
            $table->index(['timetable_id', 'day_of_week']);
        });

        // Teacher Timetable View (for teacher schedule)
        Schema::create('teacher_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('term_id')->constrained()->cascadeOnDelete();
            $table->integer('max_periods_per_day')->nullable();
            $table->integer('max_periods_per_week')->nullable();
            $table->json('unavailable_slots')->nullable();
            $table->timestamps();

            $table->unique(['teacher_id', 'academic_year_id', 'term_id'], 'teacher_schedule_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_schedules');
        Schema::dropIfExists('timetable_slots');
        Schema::dropIfExists('timetables');
        Schema::dropIfExists('time_periods');
    }
};
