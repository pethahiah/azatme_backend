<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNrqMerchantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('nrq_merchants', function (Blueprint $table) {
            $table->id();
	    $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->string('tin')->nullable();
            $table->string('contact')->nullable();
            $table->string('address')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('accountName')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('bankNo')->nullable();
            $table->string('referenceCode')->nullable();
            $table->string('remarks')->nullable();
            $table->string('phone')->nullable();
	    $table->string('merchantNumber')->nullable();
	    $table->string('auth_id')->nullable();	
            $table->timestamps();
        });
    }
}
