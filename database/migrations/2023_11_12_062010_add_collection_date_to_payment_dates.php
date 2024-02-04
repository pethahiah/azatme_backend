<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCollectionDateToPaymentDates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_dates', function (Blueprint $table) {
            //
		$table->date('collection_date')->nullable();
		$table->integer('position')->nullable();

        });
    }
}
