<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('pickUp')->nullable();
            $table->string('destination')->nullable();
            $table->string('to_time')->nullable();
            $table->string('variable_distance')->nullable();
            $table->enum('plan_time', array('fixed','dynamic'));
            $table->foreignId('user_id')->nullable();
            $table->string('day')->nullable();
            $table->string('frequency')->nullable();
            $table->string('amount')->nullable();
            $table->enum('pay_option', array('free','fee'));
            $table->enum('usertype', array('passenger','driver'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trip_schedules');
    }
}
