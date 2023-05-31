<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsBinnedAndIsWinnedToBallsPlayers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('balls_players', function (Blueprint $table) {
            $table->boolean('is_binned')->default(false);
            $table->boolean('is_winned')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('balls_players', function (Blueprint $table) {
            $table->dropColumn('is_binned');
            $table->dropColumn('is_winned');
        });
    }
}
