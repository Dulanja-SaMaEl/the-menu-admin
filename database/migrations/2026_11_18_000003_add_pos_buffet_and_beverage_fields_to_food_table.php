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
        Schema::table('food', function (Blueprint $table) {
            if (!Schema::hasColumn('food', 'portion_size')) {
                $table->string('portion_size', 100)->nullable()->after('servings')->comment('e.g. 1 Portion, 10 Pieces, 1 Piece');
            }
            if (!Schema::hasColumn('food', 'brand')) {
                $table->string('brand', 100)->nullable()->after('portion_size')->comment('Brand for beverages/packaged items, e.g. Coca-Cola, Elephant House');
            }
            if (!Schema::hasColumn('food', 'volume_ml')) {
                $table->unsignedInteger('volume_ml')->nullable()->after('brand')->comment('Liquid volume in millilitres, e.g. 330, 500, 750');
            }
            if (!Schema::hasColumn('food', 'channel')) {
                $table->string('channel', 20)->default('all')->after('volume_ml')->comment('pos, app, or all');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('food', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('food', 'portion_size')) $cols[] = 'portion_size';
            if (Schema::hasColumn('food', 'brand')) $cols[] = 'brand';
            if (Schema::hasColumn('food', 'volume_ml')) $cols[] = 'volume_ml';
            if (Schema::hasColumn('food', 'channel')) $cols[] = 'channel';

            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
