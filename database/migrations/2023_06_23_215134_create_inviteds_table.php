<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvitedsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inviteds', function (Blueprint $table) {
            $table->id();
	    $table->string('email')->unique();
            $table->enum('type', array('refundme','kontribute'));
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
           $table->string('auth_id')->nullable();
            $table->timestamps();
        });
    }
}
