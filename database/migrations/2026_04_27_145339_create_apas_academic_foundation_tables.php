<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abbreviation')->nullable();
            $table->string('dean');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('abbreviation')->nullable();
            $table->string('chairperson');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('abbreviation')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'name']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('subject_code')->unique();
            $table->string('subject_title');
            $table->json('subject_types');
            $table->decimal('credit_units', 5, 2);
            $table->string('grading_system')->default('numerical');
            $table->text('description')->nullable();
            $table->boolean('counts_toward_gpa')->default(true);
            $table->timestamps();
        });

        Schema::create('subject_requisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requisite_subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('type');
            $table->timestamps();

            $table->unique(['subject_id', 'requisite_subject_id', 'type']);
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('sex_at_birth');
            $table->unsignedTinyInteger('year_level')->default(1);
            $table->text('home_address');
            $table->boolean('is_graduating')->default(false);
            $table->timestamps();
        });

        Schema::create('adviser_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('sex_at_birth');
            $table->string('rank');
            $table->text('home_address');
            $table->timestamps();
        });

        Schema::create('prospectuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('prospectus_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospectus_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->string('term_name');
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->decimal('total_units', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['prospectus_id', 'year_level', 'term_name']);
        });

        Schema::create('prospectus_term_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospectus_term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->timestamps();

            $table->unique(['prospectus_term_id', 'subject_id']);
        });

        Schema::create('evaluation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('evaluation_template_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_template_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->timestamps();

            $table->foreign('evaluation_template_id', 'eval_template_classifications_template_fk')
                ->references('id')
                ->on('evaluation_templates')
                ->cascadeOnDelete();
        });

        Schema::create('evaluation_template_classification_subject', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_template_classification_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->timestamps();

            $table->foreign('evaluation_template_classification_id', 'eval_template_class_subject_classification_fk')
                ->references('id')
                ->on('evaluation_template_classifications')
                ->cascadeOnDelete();
            $table->foreign('subject_id', 'eval_template_class_subject_subject_fk')
                ->references('id')
                ->on('subjects')
                ->cascadeOnDelete();
            $table->unique([
                'evaluation_template_classification_id',
                'subject_id',
            ], 'eval_template_classification_subject_unique');
        });

        Schema::create('student_admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $table->date('admission_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('adviser_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adviser_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('year_level');
            $table->timestamps();

            $table->unique(['adviser_profile_id', 'course_id', 'major_id', 'year_level'], 'adviser_assignments_scope_unique');
        });

        Schema::create('student_subject_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('school_year')->nullable();
            $table->unsignedTinyInteger('year_level');
            $table->string('term_name');
            $table->string('grade')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_subject_enrollments');
        Schema::dropIfExists('adviser_assignments');
        Schema::dropIfExists('student_admissions');
        Schema::dropIfExists('evaluation_template_classification_subject');
        Schema::dropIfExists('evaluation_template_classifications');
        Schema::dropIfExists('evaluation_templates');
        Schema::dropIfExists('prospectus_term_subject');
        Schema::dropIfExists('prospectus_terms');
        Schema::dropIfExists('prospectuses');
        Schema::dropIfExists('adviser_profiles');
        Schema::dropIfExists('student_profiles');
        Schema::dropIfExists('subject_requisites');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('majors');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('colleges');
    }
};
