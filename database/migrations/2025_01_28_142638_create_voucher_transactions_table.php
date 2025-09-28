<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVoucherTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voucher_transactions', function (Blueprint $table) {
            $table->id();
	    $table->unsignedBigInteger('voucher_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->enum('status', ['used', 'unused'])->default('unused')->nullable();
            $table->string('type')->nullable();
            $table->string('code_generation_method')->nullable();
            $table->timestamps();

            // Define foreign key constraints
            $table->foreign('voucher_id')->references('id')->on('vouchers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_transactions');
    }
}
