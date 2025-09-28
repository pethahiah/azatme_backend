<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClaimedByAndClaimedAtToVouchersTable extends Migration
{

 public function up()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('claimed_by')->nullable()->after('voucher_status');
            $table->timestamp('claimed_at')->nullable()->after('claimed_by');
            $table->timestamp('state')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vouchers', function (Blueprint $table) {
            //
             $table->dropColumn('claimed_by');
             $table->dropColumn('claimed_at');
             $table->dropColumn('state');
        });
    }
}
