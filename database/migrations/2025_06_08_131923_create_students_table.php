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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('admission_no')->unique();
            $table->string('roll_no');
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('blood_group')->nullable();
            $table->string('religion')->nullable();
            $table->string('caste')->nullable();
            $table->string('nationality')->nullable();
            $table->string('aadhar_number')->nullable();
            $table->string('house_name')->nullable();
            $table->text('address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('pincode')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            
             // Parent/Guardian Information
             $table->string('father_name')->nullable();
             $table->string('father_phone')->nullable();
             $table->string('father_occupation')->nullable();
             $table->string('mother_name')->nullable();
             $table->string('mother_phone')->nullable();
             $table->string('mother_occupation')->nullable();
             $table->string('guardian_name')->nullable();
             $table->string('guardian_phone')->nullable();
             $table->string('guardian_occupation')->nullable();
             $table->string('guardian_relation')->nullable();
             $table->string('photo')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Academic Information
            $table->date('admission_date');
            $table->string('previous_school')->nullable();
            $table->text('previous_qualification')->nullable();
            $table->string('photo_path')->nullable();
            
            // Status and Relationships
            $table->boolean('is_active')->default(true);
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['admission_no', 'school_id']);
            $table->unique(['roll_no', 'class_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
