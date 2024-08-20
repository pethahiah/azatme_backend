<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAjoWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ajo_withdrawals', function (Blueprint $table) {
            $table->id();
	    $table->foreignId('beneficiary_id');
            $table->foreignId('ajo_id');
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('description')->nullable();
            $table->string('amount')->nullable();
            $table->string('paymentAmount')->nullable();
            $table->string('transactionReference')->nullable();
            $table->string('recordDateTime')->nullable();
            $table->string('bank')->nullable();
	     $table->string('charges')->nullable();
            $table->timestamps();
        });
    }
}
