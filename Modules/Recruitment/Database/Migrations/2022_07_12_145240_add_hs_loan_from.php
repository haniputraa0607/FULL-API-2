<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHsLoanFrom extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    } 
    public function up()
    {
       DB::statement('ALTER TABLE `hairstylist_loans` CHANGE `type` `type` ENUM ("CMS","Icount") default null;');
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `hairstylist_loans` CHANGE `type` `type` ENUM ("CMS","Icount") default null;');
    }
}
