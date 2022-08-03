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
            $table->decimal('payed')->nullable();
            $table->string('status')->nullable();
            $table->date('payed_date')->nullable();
            $table->foreignId('split_method_id');
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