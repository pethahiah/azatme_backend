<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSurveysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('surveys', function (Blueprint $table) {
            $table->id();
            $table->string('age')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('timestamp')->nullable();
            $table->string('occupation')->nullable();
            $table->string('sector')->nullable();
            $table->string('gender')->nullable();
            $table->string('consent')->nullable();
            $table->string('wk_bholiday_commute')->nullable();
            $table->string('isTrans_issue')->nullable();
            $table->string('trate_wk_bholiday')->nullable();
            $table->string('currentT_rate_wk_bholiday')->nullable();
            $table->string('consent_rideshare_toDestination')->nullable();
            $table->string('will_urideshare_onbholiday_wk')->nullable();
            $table->string('can_you_offer_rideshare')->nullable();
            $table->string('impact_ofshortageTrans_WkHoliday_onWork')->nullable();
            $table->string('publicTrans_normalDay')->nullable();
            $table->string('publicTrans_bholiday')->nullable();
            $table->string('publicTrans_wk')->nullable();
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
        Schema::dropIfExists('surveys');
    }
}
