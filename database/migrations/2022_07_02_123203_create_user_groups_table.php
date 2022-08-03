<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reference_id');
            $table->foreignId('user_id');
            $table->foreignId('group_id');
            $table->decimal('amount_payable')->nullable();
            $table->decimal('amount_payed')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('split_method_id')->nullable();
            $table->date('payed_date')->nullable();
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
        Schema::dropIfExists('user_groups');
    }
}
