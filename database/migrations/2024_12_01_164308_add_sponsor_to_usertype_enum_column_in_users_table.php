<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSponsorToUsertypeEnumColumnInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
    {
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `usertype` ENUM('admin', 'merchant', 'user', 'sponsor') NOT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
     public function down()
    {
        // Revert back to the original ENUM options without 'sponsor'
        DB::statement("ALTER TABLE `users` MODIFY COLUMN `usertype` ENUM('admin', 'merchant', 'user') NOT NULL");
    }
}
