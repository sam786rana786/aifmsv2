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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('late_fee', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'online', 'cheque', 'bank_transfer']);
            $table->string('transaction_id')->nullable();
            $table->string('cheque_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->date('payment_date');
            $table->text('remarks')->nullable();
            
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('cascade');
            $table->foreignId('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('collected_by')->constrained('users')->onDelete('restrict');
            
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->timestamp('processed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
