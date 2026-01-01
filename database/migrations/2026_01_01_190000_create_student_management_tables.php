<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guardians/Parents
        Schema::create('guardians', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->enum('title', ['Mr', 'Mrs', 'Ms', 'Dr', 'Prof', 'Rev', 'Hon'])->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('national_id')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_primary');
            $table->string('phone_secondary')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->default('Malawi');
            $table->enum('relationship_type', ['father', 'mother', 'guardian', 'grandparent', 'sibling', 'uncle', 'aunt', 'other'])->default('guardian');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'is_active']);
            $table->index('phone_primary');
            $table->index('email');
        });

        // Students
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('admission_number')->nullable();
            $table->string('student_id_number')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth');
            $table->string('place_of_birth')->nullable();
            $table->string('nationality')->default('Malawian');
            $table->string('national_id')->nullable();
            $table->string('birth_certificate_number')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->default('Malawi');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('blood_group', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->text('medical_conditions')->nullable();
            $table->text('allergies')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('previous_school')->nullable();
            $table->string('previous_school_address')->nullable();
            $table->date('admission_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'transferred', 'expelled', 'withdrawn', 'deceased'])->default('active');
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'admission_number']);
            $table->unique(['school_id', 'student_id_number']);
            $table->index(['school_id', 'status']);
        });

        // Student-Guardian relationship (many-to-many)
        Schema::create('student_guardian', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('guardian_id')->constrained()->cascadeOnDelete();
            $table->enum('relationship', ['father', 'mother', 'guardian', 'grandparent', 'sibling', 'uncle', 'aunt', 'other'])->default('guardian');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_emergency_contact')->default(false);
            $table->boolean('can_pickup')->default(true);
            $table->boolean('receives_reports')->default(true);
            $table->boolean('receives_invoices')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'guardian_id']);
            $table->index(['student_id', 'is_primary']);
        });

        // Student Enrollments (class/stream assignments per academic year)
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('stream_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('roll_number')->nullable();
            $table->date('enrollment_date');
            $table->date('withdrawal_date')->nullable();
            $table->enum('status', ['active', 'promoted', 'repeated', 'transferred', 'withdrawn', 'graduated'])->default('active');
            $table->text('remarks')->nullable();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id'], 'student_year_unique');
            $table->index(['school_id', 'academic_year_id', 'class_id']);
            $table->index(['school_id', 'academic_year_id', 'status']);
        });

        // Student Subject Enrollments (which subjects a student is taking)
        Schema::create('student_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_subject_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->date('dropped_date')->nullable();
            $table->string('drop_reason')->nullable();
            $table->timestamps();

            $table->unique(['student_enrollment_id', 'class_subject_id'], 'student_subject_unique');
        });

        // Student Promotions History
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('from_academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('to_academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignUuid('from_class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignUuid('to_class_id')->constrained('classes')->cascadeOnDelete();
            $table->enum('promotion_type', ['promoted', 'repeated', 'skipped', 'graduated', 'transferred'])->default('promoted');
            $table->decimal('final_average', 5, 2)->nullable();
            $table->integer('final_position')->nullable();
            $table->text('remarks')->nullable();
            $table->uuid('promoted_by')->nullable();
            $table->timestamp('promoted_at');
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'from_academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
        Schema::dropIfExists('student_subjects');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('student_guardian');
        Schema::dropIfExists('students');
        Schema::dropIfExists('guardians');
    }
};
