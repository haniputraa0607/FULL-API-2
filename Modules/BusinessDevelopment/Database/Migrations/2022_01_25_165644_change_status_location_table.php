<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStatusLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
         DB::statement('ALTER TABLE `locations` CHANGE `status` `status` ENUM("Active","Inactive","Candidate","Rejected","Close") default "Candidate";');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         DB::statement('ALTER TABLE `locations` CHANGE `status` `status` ENUM("Active","Inactive","Candidate","Rejected") default "Candidate";');
    }
}
