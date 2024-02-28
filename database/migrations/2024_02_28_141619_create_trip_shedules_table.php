<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripShedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trip_shedules', function (Blueprint $table) {
            $table->id();
            $table->string('description')->nullable();
            $table->string('pickUp')->nullable();
            $table->string('destination')->nullable();
            $table->string('to_time')->nullable();
            $table->string('from_time')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('day')->nullable();
            $table->string('frequency')->nullable();
            $table->string('type')->nullable();
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
        Schema::dropIfExists('trip_shedules');
    }
}
