<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateJenisOnOutletCloseTemporaryTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        DB::statement('ALTER TABLE `outlet_close_temporary` CHANGE `jenis` `jenis` ENUM("Close","Active") default "Close";');
      
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `outlet_close_temporary` CHANGE `jenis` `jenis` ENUM("Close","Active") default "Close";');
    }
}
