<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('description')->nullable();
            $table->string('pickUp')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->foreignId('sender_id')->nullable();
            $table->foreignId('guest_id')->nullable();
            $table->boolean('fee_option')->nullable();
            $table->boolean('load_option')->nullable();
            $table->string('load_in_kg')->nullable();
            $table->string('charges')->nullable();
            $table->string('fee_amount')->nullable();
            $table->foreignId('property_id')->nullable();
            $table->string('number_of_guest')->nullable();
            $table->string('available_seat')->nullable();
            $table->string('trip_status')->nullable();
            $table->string('variable_distance')->nullable();
            $table->string('meeting_point')->nullable();
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
        Schema::dropIfExists('trips');
    }
}
