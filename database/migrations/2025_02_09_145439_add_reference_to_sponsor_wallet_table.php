<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferenceToSponsorWalletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sponsor_wallets', function (Blueprint $table) {
            //
		$table->string('reference')->nullable()->after('wallet_balance');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sponsor_wallets', function (Blueprint $table) {
            //
		$table->dropColumn('reference');
        });
    }
}
