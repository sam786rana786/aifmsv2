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
        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('distance', 8, 2); // in kilometers
            $table->decimal('monthly_fee', 10, 2);
            $table->decimal('quarterly_fee', 10, 2)->nullable();
            $table->decimal('annual_fee', 10, 2)->nullable();
            $table->integer('capacity');
            $table->string('vehicle_number')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('driver_phone')->nullable();
            $table->time('pickup_time')->nullable();
            $table->time('drop_time')->nullable();
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
        Schema::dropIfExists('transport_routes');
    }
};
