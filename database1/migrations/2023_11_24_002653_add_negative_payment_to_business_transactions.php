<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNegativePaymentToBusinessTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_transactions', function (Blueprint $table) {
            //
		$table->string('negative_amount')->nullable();
        });
    }
}
