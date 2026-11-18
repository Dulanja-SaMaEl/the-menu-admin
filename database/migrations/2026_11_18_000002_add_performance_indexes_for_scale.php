<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations to add high-performance indexes for 750-1000 users.
     */
    public function up(): void
    {
        // Helper to check existing indexes safely
        $hasIndex = function (string $table, string $indexName): bool {
            if (!Schema::hasTable($table)) return true;
            $indexes = Schema::getIndexes($table);
            $names = array_column($indexes, 'name');
            return in_array($indexName, $names, true);
        };

        // Orders Table Indexes
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) use ($hasIndex) {
                if (!$hasIndex('orders', 'idx_orders_user_created') && Schema::hasColumn('orders', 'user_id')) {
                    $table->index(['user_id', 'created_at'], 'idx_orders_user_created');
                }
                if (!$hasIndex('orders', 'idx_orders_status_created') && Schema::hasColumn('orders', 'status')) {
                    $table->index(['status', 'created_at'], 'idx_orders_status_created');
                }
                if (!$hasIndex('orders', 'idx_orders_driver_created') && Schema::hasColumn('orders', 'driver_id')) {
                    $table->index(['driver_id', 'created_at'], 'idx_orders_driver_created');
                }
                if (!$hasIndex('orders', 'idx_orders_subscription') && Schema::hasColumn('orders', 'is_subscription')) {
                    $table->index(['is_subscription', 'auto_renew', 'payment_status'], 'idx_orders_subscription');
                }
            });
        }

        // Food Table Indexes
        if (Schema::hasTable('food')) {
            Schema::table('food', function (Blueprint $table) use ($hasIndex) {
                if (!$hasIndex('food', 'idx_food_type') && Schema::hasColumn('food', 'type')) {
                    $table->index('type', 'idx_food_type');
                }
                if (!$hasIndex('food', 'idx_food_price') && Schema::hasColumn('food', 'price')) {
                    $table->index('price', 'idx_food_price');
                }
            });
        }

        // Order Items Table Indexes
        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) use ($hasIndex) {
                if (!$hasIndex('order_items', 'idx_order_items_scheduled') && Schema::hasColumn('order_items', 'scheduled_date')) {
                    $table->index(['scheduled_date', 'meal_type'], 'idx_order_items_scheduled');
                }
            });
        }

        // User Notifications Table Indexes
        if (Schema::hasTable('user_notifications')) {
            Schema::table('user_notifications', function (Blueprint $table) use ($hasIndex) {
                if (!$hasIndex('user_notifications', 'idx_notif_user_read') && Schema::hasColumn('user_notifications', 'user_id')) {
                    $table->index(['user_id', 'is_read'], 'idx_notif_user_read');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex('idx_orders_user_created');
                $table->dropIndex('idx_orders_status_created');
                $table->dropIndex('idx_orders_driver_created');
                $table->dropIndex('idx_orders_subscription');
            });
        }

        if (Schema::hasTable('food')) {
            Schema::table('food', function (Blueprint $table) {
                $table->dropIndex('idx_food_type');
                $table->dropIndex('idx_food_price');
            });
        }

        if (Schema::hasTable('order_items')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropIndex('idx_order_items_scheduled');
            });
        }

        if (Schema::hasTable('user_notifications')) {
            Schema::table('user_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notif_user_read');
            });
        }
    }
};
