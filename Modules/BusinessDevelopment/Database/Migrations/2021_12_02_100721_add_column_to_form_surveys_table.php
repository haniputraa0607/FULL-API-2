<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToFormSurveysTable extends Migration
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
        Schema::table('form_surveys', function (Blueprint $table) {
            $table->integer('id_location')->after('id_partner');
        });
        Schema::table('confirmation_letters', function (Blueprint $table) {
            $table->integer('id_location')->after('id_partner');
        });
        DB::statement('ALTER TABLE `locations` CHANGE `status` `status` ENUM("Active","Inactive","Candidate","Rejected") default "Candidate" NOT NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_surveys', function (Blueprint $table) {
            $table->dropColumn('id_location');
        });
        Schema::table('confirmation_letters', function (Blueprint $table) {
            $table->dropColumn('id_location');
        });
        DB::statement('ALTER TABLE `locations` CHANGE `status` `status` ENUM("Active","Inactive","Candidate") default "Candidate" NOT NULL;');
    }
}
