<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToBusinessTransactionsTable extends Migration
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
	    $table->string('providedEmail')->nullable();
            $table->string('providedName')->nullable();
            $table->string('remarks')->nullable();
        });
    }
}
