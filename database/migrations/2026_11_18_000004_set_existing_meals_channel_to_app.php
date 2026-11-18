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
        // Set all existing delivery subscription meals to 'app' channel so they do not clutter the POS counter
        DB::table('food')
            ->whereIn('type', [
                'Breakfast',
                'Lunch',
                'Dinner',
                'Sri Lankan Style Breakfast Menu',
                'Sri Lankan Style Lunch Menu',
                'Sri Lankan Style Dinner Menu',
                'English breakfast Menu',
            ])
            ->whereNull('portion_size')
            ->update(['channel' => 'app']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('food')
            ->where('channel', 'app')
            ->whereNull('portion_size')
            ->update(['channel' => 'all']);
    }
};
