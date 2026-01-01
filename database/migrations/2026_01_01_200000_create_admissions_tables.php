<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admission Periods (when admissions are open)
        Schema::create('admission_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'open', 'closed', 'archived'])->default('draft');
            $table->integer('max_applications')->nullable();
            $table->decimal('application_fee', 10, 2)->nullable();
            $table->json('required_documents')->nullable();
            $table->json('eligible_classes')->nullable();
            $table->text('instructions')->nullable();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'academic_year_id']);
        });

        // Admission Applications
        Schema::create('admission_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('admission_period_id')->constrained()->cascadeOnDelete();
            $table->string('application_number')->unique();
            
            // Applicant Information
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth');
            $table->string('place_of_birth')->nullable();
            $table->string('nationality')->default('Malawian');
            $table->string('birth_certificate_number')->nullable();
            
            // Contact Information
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->default('Malawi');
            
            // Previous School
            $table->string('previous_school')->nullable();
            $table->string('previous_school_address')->nullable();
            $table->string('previous_class')->nullable();
            $table->decimal('previous_average', 5, 2)->nullable();
            
            // Application Details
            $table->foreignUuid('applied_class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignUuid('preferred_stream_id')->nullable()->constrained('streams')->nullOnDelete();
            
            // Guardian Information (primary)
            $table->string('guardian_first_name');
            $table->string('guardian_last_name');
            $table->string('guardian_relationship');
            $table->string('guardian_phone');
            $table->string('guardian_email')->nullable();
            $table->string('guardian_occupation')->nullable();
            $table->text('guardian_address')->nullable();
            
            // Status and Workflow
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'documents_pending',
                'interview_scheduled', 'interviewed', 'approved', 'rejected',
                'waitlisted', 'enrolled', 'withdrawn', 'expired'
            ])->default('draft');
            $table->text('status_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decision_at')->nullable();
            $table->foreignUuid('decision_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Interview
            $table->timestamp('interview_date')->nullable();
            $table->text('interview_notes')->nullable();
            $table->integer('interview_score')->nullable();
            
            // Entrance Exam
            $table->decimal('entrance_exam_score', 5, 2)->nullable();
            $table->date('entrance_exam_date')->nullable();
            
            // Payment
            $table->boolean('fee_paid')->default(false);
            $table->timestamp('fee_paid_at')->nullable();
            $table->string('payment_reference')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'admission_period_id']);
            $table->index(['school_id', 'applied_class_id']);
        });

        // Application Documents
        Schema::create('application_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('admission_application_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('document_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->integer('file_size')->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['admission_application_id', 'document_type'], 'app_docs_app_id_doc_type_idx');
        });

        // Application Status History
        Schema::create('application_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('admission_application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->foreignUuid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('admission_application_id');
        });

        // Application Comments/Notes
        Schema::create('application_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('admission_application_id')->constrained()->cascadeOnDelete();
            $table->text('comment');
            $table->boolean('is_internal')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('admission_application_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_comments');
        Schema::dropIfExists('application_status_history');
        Schema::dropIfExists('application_documents');
        Schema::dropIfExists('admission_applications');
        Schema::dropIfExists('admission_periods');
    }
};
