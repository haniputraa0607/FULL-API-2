<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusOnPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `partners` CHANGE `status` `status` ENUM("Active","Inactive","Candidate","Rejected") default "Candidate";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `partners` CHANGE `status` `status` ENUM("Active","Inactive","Candidate") default "Candidate";');
    }
}
