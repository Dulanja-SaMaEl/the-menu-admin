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
            if (!Schema::hasColumn('orders', 'proof_photo_path')) {
                $table->string('proof_photo_path')->nullable();
            }
            if (!Schema::hasColumn('orders', 'pod_notes')) {
                $table->text('pod_notes')->nullable();
            }
            if (!Schema::hasColumn('orders', 'recipient_name')) {
                $table->string('recipient_name')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('orders', 'proof_photo_path')) {
                $columnsToDrop[] = 'proof_photo_path';
            }
            if (Schema::hasColumn('orders', 'pod_notes')) {
                $columnsToDrop[] = 'pod_notes';
            }
            if (Schema::hasColumn('orders', 'recipient_name')) {
                $columnsToDrop[] = 'recipient_name';
            }

            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
