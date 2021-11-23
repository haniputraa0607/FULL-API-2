<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeSomeColumnPartnerAndLocationTable extends Migration
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
        Schema::table('partners', function (Blueprint $table) { 
            $table->string('contact_person')->nullable(false)->change(); 
            $table->string('phone')->nullable(false)->change();  
        });
        DB::statement('ALTER TABLE `partners` CHANGE `npwp` `npwp` varchar(191) NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `id_company` `id_company` varchar(191) NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `group` `group` ENUM("0","1","2") default "2" NOT NULL;');
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partners', function (Blueprint $table) {
                $table->string('contact_person')->nullable()->change(); 
                $table->string('phone')->nullable()->change(); 
        });
        DB::statement('ALTER TABLE `partners` CHANGE `npwp` `npwp` int NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `id_company` `id_company` int NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `group` `group` ENUM("0","1","2") NULL;');
    }
}
