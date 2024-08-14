<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('withrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id_id');
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('description')->nullable();
            $table->string('amount')->nullable();
            $table->string('paymentAmount')->nullable();
            $table->string('transactionReference')->nullable();
            $table->string('recordDateTime')->nullable();
            $table->string('bank')->nullable();
            $table->string('charges')->nullable();
            $table->string('minus_residual')->nullable();
            $table->longText('status', 255)->nullable();
            $table->string('uniqueId')->nullable();
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
        Schema::dropIfExists('withrawals');
    }
}
