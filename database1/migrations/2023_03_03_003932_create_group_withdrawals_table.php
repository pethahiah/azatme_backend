<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGroupWithdrawalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id');
            $table->foreignId('group_id');
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('description')->nullable();
            $table->string('amount')->nullable();
            $table->string('bank')->nullable();
            $table->string('transactionReference')->nullable();
            $table->string('paymentAmount')->nullable();
            $table->string('recordDateTime')->nullable();
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
        Schema::dropIfExists('group_withdrawals');
    }
}
