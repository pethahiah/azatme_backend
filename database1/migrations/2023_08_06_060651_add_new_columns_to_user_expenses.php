<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToUserExpenses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_expenses', function (Blueprint $table) {
            //
		$table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
        });
    }
}
