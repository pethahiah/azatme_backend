<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdsToAjopaymentSentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ajopayment_sents', function (Blueprint $table) {
            //
		$table->foreignId('ajo_id')->nullable();
		$table->date('date_sent')->nullable();
        });
    }
}
