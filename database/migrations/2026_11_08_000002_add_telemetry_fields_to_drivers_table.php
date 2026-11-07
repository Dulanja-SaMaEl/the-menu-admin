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
            if (!Schema::hasColumn('drivers', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('drivers', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable();
            }
            if (!Schema::hasColumn('drivers', 'heading')) {
                $table->float('heading')->nullable()->default(0.0);
            }
            if (!Schema::hasColumn('drivers', 'speed')) {
                $table->float('speed')->nullable()->default(0.0);
            }
            if (!Schema::hasColumn('drivers', 'last_ping_at')) {
                $table->timestamp('last_ping_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('drivers', 'latitude')) {
                $columnsToDrop[] = 'latitude';
            }
            if (Schema::hasColumn('drivers', 'longitude')) {
                $columnsToDrop[] = 'longitude';
            }
            if (Schema::hasColumn('drivers', 'heading')) {
                $columnsToDrop[] = 'heading';
            }
            if (Schema::hasColumn('drivers', 'speed')) {
                $columnsToDrop[] = 'speed';
            }
            if (Schema::hasColumn('drivers', 'last_ping_at')) {
                $columnsToDrop[] = 'last_ping_at';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
