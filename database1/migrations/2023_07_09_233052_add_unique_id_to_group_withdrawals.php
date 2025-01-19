<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueIdToGroupWithdrawals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('group_withdrawals', function (Blueprint $table) {
            //
		$table->string('uniqueId')->nullable();
        });
    }

}
