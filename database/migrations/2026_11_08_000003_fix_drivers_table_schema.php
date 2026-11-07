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
            if (!Schema::hasColumn('drivers', 'total_deliveries')) {
                $table->unsignedInteger('total_deliveries')->default(0);
            }
            if (Schema::hasColumn('drivers', 'phone')) {
                $table->string('phone', 50)->nullable()->change();
            }
            if (Schema::hasColumn('drivers', 'vehicle_type')) {
                $table->string('vehicle_type', 50)->nullable()->change();
            }
            if (Schema::hasColumn('drivers', 'vehicle_number')) {
                $table->string('vehicle_number', 50)->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (Schema::hasColumn('drivers', 'total_deliveries')) {
                $table->dropColumn('total_deliveries');
            }
        });
    }
};
