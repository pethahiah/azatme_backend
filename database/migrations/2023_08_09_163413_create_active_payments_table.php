<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('active_payments', function (Blueprint $table) {
            $table->id();
	   $table->string('paymentReference')->nullable();
            $table->timestamps();
        });
    }
}
