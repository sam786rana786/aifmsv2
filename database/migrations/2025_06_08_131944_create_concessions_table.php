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
        Schema::create('concessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['scholarship', 'financial_hardship', 'merit_based', 'sibling_discount']);
            $table->enum('calculation_type', ['percentage', 'fixed']);
            $table->decimal('value', 10, 2);
            $table->text('description')->nullable();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->boolean('requires_approval')->default(true);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concessions');
    }
};
