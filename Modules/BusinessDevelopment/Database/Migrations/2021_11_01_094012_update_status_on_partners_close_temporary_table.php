<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateStatusOnPartnersCloseTemporaryTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        DB::statement('ALTER TABLE `partners_close_temporary` CHANGE `status` `status` ENUM("Process","Waiting","Success","Reject") default "Process";');
      
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `partners_close_temporary` CHANGE `status` `status` ENUM("Process","Success","Reject") default "Process";');
    }
}
