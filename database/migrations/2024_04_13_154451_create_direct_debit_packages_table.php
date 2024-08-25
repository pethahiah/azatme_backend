<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectDebitPackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('direct_debit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('productName')->nullable();
            $table->foreignId('productId')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

        });
    }
}
