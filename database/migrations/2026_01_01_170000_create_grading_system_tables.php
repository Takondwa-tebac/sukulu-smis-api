<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grading Systems - the main configuration table
        Schema::create('grading_systems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['primary', 'secondary_jce', 'secondary_msce', 'international'])->default('primary');
            $table->enum('scale_type', ['letter', 'numeric', 'percentage', 'gpa', 'points'])->default('percentage');
            $table->decimal('min_score', 5, 2)->default(0);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->decimal('pass_mark', 5, 2)->default(50);
            $table->integer('min_subjects_to_pass')->default(6);
            $table->json('priority_subjects')->nullable();
            $table->json('certification_rules')->nullable();
            $table->json('progression_rules')->nullable();
            $table->json('settings')->nullable();
            $table->integer('version')->default(1);
            $table->boolean('is_system_default')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
            $table->index('type');
            $table->index('is_system_default');
        });

        // Grade Scales - individual grade definitions within a grading system
        Schema::create('grade_scales', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grading_system_id')->constrained()->cascadeOnDelete();
            $table->string('grade');
            $table->string('grade_label')->nullable();
            $table->decimal('min_score', 5, 2);
            $table->decimal('max_score', 5, 2);
            $table->decimal('gpa_points', 4, 2)->nullable();
            $table->integer('points')->nullable();
            $table->string('remark')->nullable();
            $table->boolean('is_passing')->default(true);
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index('grading_system_id');
            $table->index('sort_order');
        });

        // School Grading Configurations - links schools to grading systems with overrides
        Schema::create('school_grading_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('grading_system_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['primary', 'jce', 'msce', 'all'])->default('all');
            $table->json('custom_priority_subjects')->nullable();
            $table->json('custom_pass_rules')->nullable();
            $table->json('custom_settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'grading_system_id', 'level']);
            $table->index(['school_id', 'is_active']);
        });

        // Grading System History - for versioning and audit
        Schema::create('grading_system_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grading_system_id')->constrained()->cascadeOnDelete();
            $table->integer('version');
            $table->json('snapshot');
            $table->string('change_reason')->nullable();
            $table->uuid('changed_by')->nullable();
            $table->timestamps();

            $table->index(['grading_system_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_system_histories');
        Schema::dropIfExists('school_grading_configs');
        Schema::dropIfExists('grade_scales');
        Schema::dropIfExists('grading_systems');
    }
};
