<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('passenger_id');
            $table->foreignId('user_id');
            $table->foreignId('trip_id');
            $table->decimal('amount')->nullable();
            $table->foreignId('split_method_id');
            $table->string('unique_code')->nullable();
            $table->decimal('percentage')->nullable();
            $table->decimal('percentage_per_user')->nullable();
            $table->string('email')->nullable();
            $table->enum('status', array('successful', 'failed', 'pending'))->nullable();
            $table->decimal('commission')->nullable();
            $table->decimal('residualAmount')->nullable();
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
        Schema::dropIfExists('payments');
    }
}
