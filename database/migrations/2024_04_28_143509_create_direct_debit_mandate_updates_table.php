<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDirectDebitMandateUpdatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('direct_debit_mandate_updates', function (Blueprint $table) {
            $table->id();
            $table->enum('requestType', array('Suspend', 'Enable', 'Update'))->nullable();
            $table->foreignId('mandateId')->nullable()->nullable();
            $table->string('amountLimit')->nullable()->nullable();
            $table->timestamps();
        });
    }
}
