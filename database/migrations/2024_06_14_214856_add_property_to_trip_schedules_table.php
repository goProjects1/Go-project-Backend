<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPropertyToTripSchedulesTable extends Migration
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
            $table->foreignId('ownProperty')->nullable();
            $table->string('destLatitude')->nullable();
            $table->string('destLongitude')->nullable();
            $table->boolean('allowUserMeetingPoint')->nullable();
            $table->string('available_seat')->nullable();
        });
    }


}
