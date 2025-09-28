<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVerifysmsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('verifysms', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('otp');
            $table->foreignId('user_id');
            $table->timestamps();
        });
    }

}
