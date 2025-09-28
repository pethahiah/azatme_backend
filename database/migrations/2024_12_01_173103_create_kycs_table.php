<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKycsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kycs', function (Blueprint $table) {
            $table->id();
	    $table->unsignedBigInteger('user_id')->unique();
            $table->string('identity_card')->nullable();
            $table->string('utility_bill')->nullable();
            $table->string('proof_of_address')->nullable();
            $table->string('business_registration_certificate')->nullable();
            $table->string('business_registration_number')->nullable();
            $table->string('company_name')->nullable();
            $table->string('share_capital')->nullable();
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
        Schema::dropIfExists('kycs');
    }
}
