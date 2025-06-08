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
        Schema::table('students', function (Blueprint $table) {
            // Only add fields that don't already exist
            if (!Schema::hasColumn('students', 'admission_date')) {
                $table->date('admission_date')->after('photo_path');
            }
            if (!Schema::hasColumn('students', 'previous_school')) {
                $table->string('previous_school')->nullable()->after('admission_date');
            }
            if (!Schema::hasColumn('students', 'previous_qualification')) {
                $table->text('previous_qualification')->nullable()->after('previous_school');
            }
            if (!Schema::hasColumn('students', 'documents')) {
                $table->json('documents')->nullable()->after('previous_qualification');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'admission_date')) {
                $table->dropColumn('admission_date');
            }
            if (Schema::hasColumn('students', 'previous_school')) {
                $table->dropColumn('previous_school');
            }
            if (Schema::hasColumn('students', 'previous_qualification')) {
                $table->dropColumn('previous_qualification');
            }
            if (Schema::hasColumn('students', 'documents')) {
                $table->dropColumn('documents');
            }
        });
    }
};
