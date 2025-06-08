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
        Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->enum('frequency', ['one_time', 'monthly', 'quarterly', 'annually']);
            $table->boolean('is_optional')->default(false);
            $table->boolean('has_late_fee')->default(false);
            $table->decimal('late_fee_amount', 10, 2)->nullable();
            $table->integer('late_fee_grace_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['code', 'school_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_types');
    }
};
