<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAjosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ajos', function (Blueprint $table) {
            $table->id();
	    $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->string('starting_date')->nullable();
            $table->string('frequency')->nullable();
            $table->string('cycle')->nullable();
            $table->string('amount_per_member')->nullable();
            $table->string('member_count')->nullable();
            $table->string('uique_code')->nullable();
            $table->string('user_id')->nullable();
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
        Schema::dropIfExists('ajos');
    }
}
