<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBvnDetailsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            //
	        $table->string('enrollment_username')->nullable();
            $table->string('nin_bvnDetails')->nullable();
            $table->longText('accessToken')->nullable();
            $table->string('face_image')->nullable();
            $table->string('lga_of_origin')->nullable();

        });
    }

}
