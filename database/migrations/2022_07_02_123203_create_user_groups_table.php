<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_id');
            $table->foreignId('user_id');
            $table->foreignId('group_id');
            $table->decimal('amount_payable')->nullable();
            $table->foreignId('split_method_id')->nullable();
            $table->string('productId')->nullable();
            $table->string('actualAmount')->nullable();
            $table->decimal('percentage')->nullable();
            $table->decimal('percentage_per_user')->nullable();
            $table->timestamp('transactionDate')->nullable()->default(null);
            //$table->dateTime('transactionDate')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'))->nullable();
            $table->decimal('merchantReference')->nullable();
            $table->string('fiName')->nullable();
            $table->string('paymentMethod')->nullable();
            $table->decimal('payThruReference')->nullable();
            $table->string('paymentReference')->nullable();
            $table->string('responseCode')->nullable();
            $table->string('linkExpireDateTime')->nullable();
            $table->string('responseDescription')->nullable();
            $table->enum('status', array(1, 2, 3))->nullable();
            $table->decimal('amount')->nullable()->nullable();
            $table->decimal('commission')->nullable();
            $table->decimal('residualAmount')->nullable();
            $table->string('account_name')->nullable();
            $table->string('resultCode')->nullable();
            $table->string('email')->nullable();
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
        Schema::dropIfExists('user_groups');
    }
}
