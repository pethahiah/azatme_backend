<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAjoIdToAjoContributorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ajo_contributors', function (Blueprint $table) {
            //
		$table->foreignId('ajo_id');
        });
    }
}
