<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAjoBalanacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ajo_balanaces', function (Blueprint $table) {
            $table->id();
	    $table->foreignId('user_id');
            $table->string('balance')->nullable();
            $table->timestamps();
        });
    }
}
