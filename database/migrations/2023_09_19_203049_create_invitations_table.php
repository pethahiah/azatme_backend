<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
	    $table->string('email');
            $table->string('name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('position')->nullable();
            $table->string('inviter_id')->nullable();
            $table->string('ajo_id')->nullable();
            $table->string('amount')->nullable();
            $table->string('token')->nullable();
            $table->string('status')->nullable();
	    $table->timestamp('transactionDate')->nullable()->default(null);
            $table->string('merchantReference')->nullable();
            $table->string('fiName')->nullable();
            $table->string('paymentMethod')->nullable();
            $table->string('linkExpireDateTime')->nullable();
            $table->string('payThruReference')->nullable();
            $table->string('paymentReference')->nullable();
            $table->string('responseCode')->nullable();
            $table->string('responseDescription')->nullable();
            $table->string('amount_paid')->nullable();
            $table->string('commission')->nullable();
            $table->string('residualAmount')->nullable();
            $table->string('account_name')->nullable();
            $table->string('resultCode')->nullable();
            $table->string('uidd')->nullable();
            $table->string('minus_residual')->nullable();
            $table->string('first_name')->nullable();
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
        Schema::dropIfExists('invitations');
    }
}
