<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id');
            $table->enum('duration', array('evergreen', 'fixed'))->nullable();
            $table->string('start_date')->nullable();
            $table->string('end_date')->nullable();
            $table->string('point_limit')->nullable();
            $table->enum('point_conversion', array('charges_deduction', 'fund_wallet'))->nullable();
            $table->enum('status', array('active', 'not_active'))->nullable();
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
        Schema::dropIfExists('referral_settings');
    }
}
