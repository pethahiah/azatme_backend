<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColunToReferralSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            //
            $table->string('referral_active_point')->nullable();
            $table->string('product_getting_point')->nullable();
        });
    }

}
