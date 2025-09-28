<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPayoutStatusAndRedeemedAtToVoucherTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            //
		 $table->string('payout_status')->default('pending')->after('amount');
            	 $table->timestamp('redeemed_at')->nullable()->after('payout_status');
        });
    }
}
