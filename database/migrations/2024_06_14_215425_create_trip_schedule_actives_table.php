<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripScheduleActivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_schedule_actives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable();
            $table->foreignId('passenger_id')->nullable();
            $table->foreignId('schedule_trip_id')->nullable();
            $table->string('destLatitude')->nullable();
            $table->string('destLongitude')->nullable();
            $table->string('destination')->nullable();
            $table->string('meeting_point')->nullable();
            $table->string('sourceLatitude')->nullable();
            $table->string('sourceLongitude')->nullable();
            $table->enum('schedule_journey_status', array('going','waiting','stopping'))->nullable();
            $table->timestamps();
        });
    }

}
