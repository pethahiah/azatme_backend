<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLocationAndDeviceToVoucherTransactions extends Migration
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
	    $table->decimal('longitude', 10, 6)->nullable()->after('redeemed_at');
            $table->decimal('latitude', 10, 6)->nullable()->after('longitude');
            $table->string('state')->nullable()->after('latitude');
            $table->string('city')->nullable()->after('state');
            $table->string('device')->nullable()->after('city');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('voucher_transactions', function (Blueprint $table) {
            //
		 $table->dropColumn(['longitude', 'latitude', 'state', 'city', 'device']);
        });
    }
}
