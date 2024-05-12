<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLatandlonToTripSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trip_schedules', function (Blueprint $table) {
            //
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->enum('schedule_status', array('active','inactive','ready'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trip_schedules', function (Blueprint $table) {
            //
        });
    }
}
