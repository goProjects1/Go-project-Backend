<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddValuesToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            //
            $table->string('bankName')->nullable();
            $table->string('bankCode')->nullable();
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
        });
    }


}
