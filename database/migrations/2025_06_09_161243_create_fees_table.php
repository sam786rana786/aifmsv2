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
        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('fee_structure_id')->constrained()->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            
            // Amount fields
            $table->decimal('amount', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('waiver_amount', 10, 2)->default(0);
            
            // Date fields
            $table->date('due_date')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Status fields
            $table->enum('status', ['pending', 'overdue', 'completed', 'cancelled', 'waived'])->default('pending');
            $table->enum('payment_status', ['unpaid', 'partially_paid', 'fully_paid'])->default('unpaid');
            
            // Fee categorization
            $table->enum('fee_category', [
                'tuition', 'admission', 'library', 'laboratory', 'sports', 
                'transport', 'examination', 'development', 'miscellaneous'
            ])->default('tuition');
            $table->enum('fee_type', ['mandatory', 'optional', 'conditional'])->default('mandatory');
            
            // Installment fields
            $table->unsignedTinyInteger('installment_number')->default(1);
            $table->unsignedTinyInteger('installment_of')->default(1);
            
            // Text fields
            $table->text('description')->nullable();
            $table->text('waiver_reason')->nullable();
            $table->text('remarks')->nullable();
            
            // User tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['school_id', 'status']);
            $table->index(['fee_category', 'fee_type']);
            $table->index(['due_date', 'status']);
            $table->index(['payment_status', 'status']);
            $table->index('created_at');
            
            // Composite indexes
            $table->index(['school_id', 'academic_year_id', 'status']);
            $table->index(['student_id', 'fee_category', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fees');
    }
};
