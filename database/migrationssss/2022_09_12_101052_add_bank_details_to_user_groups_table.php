<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankDetailsToUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_groups', function (Blueprint $table) {
            //
            $table->string('bankName')->nullable();
            $table->double('bankCode')->nullable();
            $table->double('account_number')->nullable();
        });
    }
   
}
