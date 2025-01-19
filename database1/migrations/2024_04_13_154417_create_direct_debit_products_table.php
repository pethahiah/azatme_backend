<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectDebitProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('direct_debit_products', function (Blueprint $table) {
            $table->id();
            $table->string('productName')->nullable();
            $table->boolean('isPacketBased')->default(0);
            $table->boolean('isUserResponsibleForCharges')->default(0);
            $table->boolean('partialCollectionEnabled')->default(0);
            $table->string('collectionAccountId')->nullable();
            $table->string('productDescription')->nullable();
            $table->enum('classification', array('SubscriptionService', 'FixedContract'))->nullable();
            $table->string('remarks')->nullable();
            $table->foreignId('productId')->nullable();
            $table->enum('feeType', array('FixedAmount', 'Variable', 'Both'))->nullable();
            $table->timestamps();
        });
    }





}
