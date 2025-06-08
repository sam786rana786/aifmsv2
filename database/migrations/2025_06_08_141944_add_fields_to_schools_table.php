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
        Schema::table('schools', function (Blueprint $table) {
            // Add affiliation_number if it doesn't exist
            if (!Schema::hasColumn('schools', 'affiliation_number')) {
                $table->string('affiliation_number')->nullable()->after('code');
            }
            
            // Rename logo_path if needed
            if (Schema::hasColumn('schools', 'logo_path') && !Schema::hasColumn('schools', 'favicon_path')) {
                $table->string('favicon_path')->nullable()->after('logo_path');
            }
            
            // Add currency_code if it doesn't exist
            if (!Schema::hasColumn('schools', 'currency_code')) {
                $table->string('currency_code', 3)->default('USD')->after('favicon_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'affiliation_number')) {
                $table->dropColumn('affiliation_number');
            }
            if (Schema::hasColumn('schools', 'favicon_path')) {
                $table->dropColumn('favicon_path');
            }
            if (Schema::hasColumn('schools', 'currency_code')) {
                $table->dropColumn('currency_code');
            }
        });
    }
};
