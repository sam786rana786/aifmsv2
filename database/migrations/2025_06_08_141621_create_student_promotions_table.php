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
        Schema::create('student_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('from_class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('to_class_id')->constrained('classes')->onDelete('cascade');
            $table->foreignId('from_academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('to_academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->date('promotion_date');
            $table->enum('status', ['pending', 'promoted', 'rolled_back'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('promoted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->text('rollback_reason')->nullable();
            $table->date('rollback_date')->nullable();
            $table->foreignId('rollback_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'to_academic_year_id']);
            $table->index(['school_id', 'status']);
            $table->index('promotion_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_promotions');
    }
};
