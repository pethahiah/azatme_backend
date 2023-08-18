<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWalletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id');
            $table->string('charges')->nullable();
            $table->string('amount_paid_by_paythru')->nullable();
            $table->string('residual_amount')->nullable();
            $table->string('amountExpectedRefundMe')->nullable();
            $table->string('amountExpectedKontribute')->nullable();
            $table->string('amountExpectedBusiness')->nullable();
            $table->string('balance')->nullable();
            $table->timestamps();
        });
    }
}
