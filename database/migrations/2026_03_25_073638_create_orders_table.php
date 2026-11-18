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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->decimal('total_price', 10, 2)->default(0);
                $table->string('status')->default('pending');
                $table->string('address')->nullable();
                $table->string('payment_method')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('orders', function (Blueprint $table) {
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
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
