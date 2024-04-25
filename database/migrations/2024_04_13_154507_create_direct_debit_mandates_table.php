<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectDebitMandatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('direct_debit_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('productId')->nullable();
            $table->string('productName')->nullable();
            $table->string('paymentAmount')->nullable();
            $table->string('serviceReference')->nullable();
            $table->string('accountNumber')->nullable();
            $table->string('bankCode')->nullable();
            $table->string('accountName')->nullable();
            $table->string('phoneNumber')->nullable();
            $table->string('homeAddress')->nullable();
            $table->string('fileName')->nullable();
            $table->string('description')->nullable();
            $table->string('fileBase64String')->nullable();
            $table->string('fileExtension')->nullable();
            $table->string('startDate')->nullable();
            $table->string('endDate"')->nullable();
            $table->string('paymentFrequency')->nullable();
            $table->foreignId('packageId')->nullable();
            $table->string('referenceCode')->nullable();
            $table->string('collectionAccountNumber')->nullable();
            $table->string('mandateType')->nullable();
            $table->string('routingOption"')->nullable();
            $table->timestamps();
        });
    }

}
