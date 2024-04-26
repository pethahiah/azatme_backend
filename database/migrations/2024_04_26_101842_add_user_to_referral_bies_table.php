<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserToReferralBiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referral_bies', function (Blueprint $table) {
            //
            $table->string('referee_email')->nullable();
            $table->string('referee_name')->nullable();
        });
    }


}
