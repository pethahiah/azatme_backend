<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeclinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('declines', function (Blueprint $table) {
            $table->id();
	    $table->unsignedBigInteger('invitation_id')->nullable();
            $table->unsignedBigInteger('inviter_id')->nullable();
            $table->string('invitee_name')->nullable();
            $table->string('remark')->nullable();
            $table->string('reason')->nullable();
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
        Schema::dropIfExists('declines');
    }
}
