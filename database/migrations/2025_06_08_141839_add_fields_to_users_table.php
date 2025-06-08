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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('employee_id')->nullable()->after('phone');
            $table->string('profile_picture')->nullable()->after('employee_id');
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('set null')->after('profile_picture');
            $table->boolean('is_active')->default(true)->after('school_id');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->integer('login_count')->default(0)->after('last_login_at');
            $table->softDeletes()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'employee_id', 
                'profile_picture',
                'school_id',
                'is_active',
                'last_login_at',
                'login_count',
                'deleted_at'
            ]);
        });
    }
};
