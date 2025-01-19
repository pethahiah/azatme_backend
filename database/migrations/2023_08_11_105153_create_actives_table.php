<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('actives', function (Blueprint $table) {
            $table->id();
	    $table->unsignedBigInteger('product_id')->nullable();
            $table->string('paymentReference')->nullable();
 	    $table->string('product_type')->nullable();
            $table->timestamps();
        });
    }
}
