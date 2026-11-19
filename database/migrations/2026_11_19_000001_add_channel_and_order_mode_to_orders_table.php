<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add channel and order_mode columns safely
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'channel')) {
                $table->string('channel', 20)->default('app')->after('status')->index();
            }
            if (!Schema::hasColumn('orders', 'order_mode')) {
                $table->string('order_mode', 30)->default('delivery')->after('channel')->index();
            }
        });

        // 2. Non-destructive backfill for existing records
        try {
            // A. Identify POS Counter transactions
            DB::table('orders')
                ->where(function ($q) {
                    $q->where('pod_notes', 'like', '%"pos_order":true%')
                      ->orWhere('pod_notes', 'like', '%"pos_order": true%')
                      ->orWhere('delivery_address', 'like', '%Walk-in Counter%')
                      ->orWhere('delivery_address', 'like', '%Dine-In%')
                      ->orWhere('address', 'like', '%Walk-in%')
                      ->orWhere('address', 'like', '%Dine-In%');
                })
                ->update(['channel' => 'pos']);

            // B. Identify Dine-In orders
            DB::table('orders')
                ->where(function ($q) {
                    $q->where('pod_notes', 'like', '%"order_type":"dine_in"%')
                      ->orWhere('pod_notes', 'like', '%"order_type": "dine_in"%')
                      ->orWhere('delivery_address', 'like', '%Dine-In%')
                      ->orWhere('address', 'like', '%Dine-In%');
                })
                ->update(['order_mode' => 'dine_in']);

            // C. Identify Takeaway orders
            DB::table('orders')
                ->where(function ($q) {
                    $q->where('pod_notes', 'like', '%"order_type":"takeaway"%')
                      ->orWhere('pod_notes', 'like', '%"order_type": "takeaway"%')
                      ->orWhere('delivery_address', 'like', '%Walk-in Counter%')
                      ->orWhere('address', 'like', '%Walk-in%');
                })
                ->update(['order_mode' => 'takeaway']);

            // D. Ensure any remaining orders have valid defaults
            DB::table('orders')->whereNull('channel')->update(['channel' => 'app']);
            DB::table('orders')->whereNull('order_mode')->update(['order_mode' => 'delivery']);
        } catch (\Throwable $e) {
            // Graceful non-blocking catch if table is empty or driver differs
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('orders', 'channel')) {
                $cols[] = 'channel';
            }
            if (Schema::hasColumn('orders', 'order_mode')) {
                $cols[] = 'order_mode';
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
