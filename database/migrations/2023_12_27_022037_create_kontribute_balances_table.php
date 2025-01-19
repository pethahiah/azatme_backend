<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKontributeBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kontribute_balances', function (Blueprint $table) {
            $table->id();
	    $table->foreignId('user_id');
            $table->string('balance')->nullable();
            $table->timestamps();
        });
    }
}
