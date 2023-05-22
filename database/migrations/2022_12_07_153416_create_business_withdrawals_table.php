<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_withdrawals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('beneficiary_id');
            $table->foreignId('product_id');
            $table->string('bank')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('description')->nullable();
            $table->string('amount')->nullable();
            $table->string('recordDateTime')->nullable();
            $table->string('paymentAmount')->nullable();
            $table->string('transactionReference')->nullable();
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
        Schema::dropIfExists('business_withdrawals');
    }
}
