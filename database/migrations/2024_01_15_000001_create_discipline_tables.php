<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Discipline Categories (e.g., Minor Offense, Major Offense, Misconduct)
        Schema::create('discipline_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->enum('severity', ['minor', 'moderate', 'major', 'critical'])->default('minor');
            $table->integer('default_points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'severity']);
        });

        // Discipline Actions (e.g., Warning, Detention, Suspension, Expulsion)
        Schema::create('discipline_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['warning', 'detention', 'suspension', 'expulsion', 'community_service', 'counseling', 'other'])->default('warning');
            $table->integer('duration_days')->nullable();
            $table->boolean('requires_parent_notification')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'type']);
        });

        // Discipline Incidents (actual incidents recorded)
        Schema::create('discipline_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('discipline_categories')->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('term_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('incident_number')->nullable();
            $table->date('incident_date');
            $table->time('incident_time')->nullable();
            $table->string('location')->nullable();
            $table->text('description');
            $table->text('witnesses')->nullable();
            $table->integer('points_assigned')->default(0);
            
            $table->enum('status', ['reported', 'under_investigation', 'resolved', 'dismissed', 'appealed'])->default('reported');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->foreignUuid('reported_by')->constrained('users')->cascadeOnDelete();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id']);
            $table->index(['school_id', 'incident_date']);
            $table->index(['school_id', 'status']);
        });

        // Discipline Incident Actions (actions taken for an incident)
        Schema::create('discipline_incident_actions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incident_id')->constrained('discipline_incidents')->cascadeOnDelete();
            $table->foreignUuid('action_id')->constrained('discipline_actions')->cascadeOnDelete();
            
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('completed_at')->nullable();
            
            $table->foreignUuid('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->softDeletes();
            $table->timestamps();

            $table->index(['incident_id', 'status']);
        });

        // Parent/Guardian Notifications for discipline
        Schema::create('discipline_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incident_id')->constrained('discipline_incidents')->cascadeOnDelete();
            $table->foreignUuid('guardian_id')->constrained()->cascadeOnDelete();
            
            $table->enum('method', ['email', 'sms', 'letter', 'meeting'])->default('email');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->foreignUuid('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['incident_id', 'guardian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discipline_notifications');
        Schema::dropIfExists('discipline_incident_actions');
        Schema::dropIfExists('discipline_incidents');
        Schema::dropIfExists('discipline_actions');
        Schema::dropIfExists('discipline_categories');
    }
};
