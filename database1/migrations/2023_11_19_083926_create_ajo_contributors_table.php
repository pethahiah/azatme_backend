<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAjoContributorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ajo_contributors', function (Blueprint $table) {
            $table->id();
	    $table->string('payThruReference');
            $table->string('transactionReference');
            $table->string('fiName');
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->string('responseCode')->nullable();
            $table->string('paymentMethod');
            $table->decimal('commission', 10, 2);
            $table->decimal('residualAmount', 10, 2);
            $table->string('resultCode')->nullable();
            $table->string('responseDescription')->nullable();
            $table->string('providedEmail');
            $table->string('providedName');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
}
