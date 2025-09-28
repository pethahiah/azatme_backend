<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnUserIdToMerchants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('merchants', function (Blueprint $table) {
            //
		$table->softDeletes()->after('updated_at');
		$table->unsignedBigInteger('user_id')->nullable()->change(); 
		$table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}
