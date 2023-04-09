<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('owner_id');
            $table->string('email');
            $table->string('name');
            $table->string('unique_code');
            $table->string('business_code');
            $table->string('description');
            $table->string('account_number');
            $table->string('bankName');
            $table->string('bankCode');
            $table->foreignId('moto_id');
            $table->foreignId('product_id');
            $table->timestamp('transactionDate')->nullable()->default(null);
            $table->decimal('merchantReference')->nullable();
            $table->string('fiName')->nullable();
            $table->string('paymentMethod')->nullable();
            $table->decimal('payThruReference')->nullable();
            $table->string('paymentReference')->nullable();
            $table->string('responseCode')->nullable();
            $table->string('responseDescription')->nullable();
            $table->decimal('amount')->nullable();
            $table->string('Grand_total')->nullable();
            $table->date('issue_date')->nullable();
            $table->string('due_days')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->nullable();
            $table->string('total')->nullable();
            $table->string('vat')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('qty')->nullable();
            $table->string('rate')->nullable();
            $table->decimal('commission')->nullable();
            $table->decimal('residualAmount')->nullable();
            $table->string('customerName')->nullable();
            $table->string('resultCode')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_transactions');
    }
}