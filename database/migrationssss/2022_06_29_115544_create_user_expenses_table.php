<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserExpensesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('principal_id');
            $table->foreignId('user_id');
            $table->foreignId('expense_id');
            $table->decimal('payable')->nullable();
            $table->foreignId('split_method_id');
            $table->string('productId')->nullable();
            $table->dateTime('transactionDate')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->nullable();
            $table->decimal('merchantReference')->nullable();
            $table->string('fiName')->nullable();
            $table->string('paymentMethod')->nullable();
            $table->decimal('payThruReference')->nullable();
            $table->string('paymentReference')->nullable();
            $table->string('responseCode')->nullable();
            $table->string('responseDescription')->nullable();
            $table->enum('status', array(1, 2, 3))->nullable();
            $table->decimal('amount')->nullable();
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
        Schema::dropIfExists('user_expenses');
    }
}