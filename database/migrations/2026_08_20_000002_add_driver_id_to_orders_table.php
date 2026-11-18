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
        Schema::table('orders', function (Blueprint $table) {
            // Ensure essential user and order columns exist if table was initialized by an earlier driver migration
            if (!Schema::hasColumn('orders', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'total_price')) {
                $table->decimal('total_price', 10, 2)->default(0)->after('user_id');
            }
            if (!Schema::hasColumn('orders', 'address')) {
                $table->string('address')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('address');
            }

            // Ensure driver_id column exists
            if (!Schema::hasColumn('orders', 'driver_id')) {
                $col = $table->foreignId('driver_id')->nullable();
                if (Schema::hasColumn('orders', 'user_id')) {
                    $col->after('user_id');
                }
                $col->constrained('drivers')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'driver_id')) {
                $table->dropForeign(['driver_id']);
                $table->dropColumn('driver_id');
            }
        });
    }
};
