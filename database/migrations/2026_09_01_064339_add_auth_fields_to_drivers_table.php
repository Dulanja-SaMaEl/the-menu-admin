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
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'password')) {
                $table->string('password')->after('email')->nullable();
            }
            if (!Schema::hasColumn('drivers', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('drivers', 'password')) $cols[] = 'password';
            if (Schema::hasColumn('drivers', 'remember_token')) $cols[] = 'remember_token';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
