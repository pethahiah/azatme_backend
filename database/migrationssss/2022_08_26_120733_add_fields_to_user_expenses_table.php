<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToUserExpensesTable extends Migration
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

            $table->string('description')->nullable();
            $table->string('name')->nullable();
            $table->decimal('balance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_expenses', function (Blueprint $table) {
            //
            $table->dropColumn('description');
            $table->dropColumn('name');
            $table->dropColumn('balance');
        });
    }
}
