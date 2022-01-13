<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStepsLocationLogsAndLocationsTable extends Migration
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
        DB::statement('ALTER TABLE `locations` CHANGE `step_loc` `step_loc` ENUM("On Follow Up","Finished Follow Up","Survey Location","Approved") default NULL;');
        DB::statement('ALTER TABLE `step_locations_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up","Survey Location","Approved") default "Follow Up";');
        Schema::table('form_surveys', function (Blueprint $table) {
            $table->integer('id_partner')->nullable()->unsigned()->change();
            $table->string('title')->nullable()->after('id_location');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `locations` CHANGE `step_loc` `step_loc` ENUM("On Follow Up","Finished Follow Up","Survey Location","Calculation","Confirmation Letter","Payment") default NULL;');
        DB::statement('ALTER TABLE `step_locations_logs` CHANGE `follow_up` `follow_up` ENUM("Follow Up","Survey Location","Calculation","Confirmation Letter","Payment") default "Follow Up";');
        Schema::table('form_surveys', function (Blueprint $table) {
            $table->dropForeign('fk_survey_partner');
            $table->dropIndex('fk_survey_partner');
        });
        DB::statement('ALTER TABLE `form_surveys` MODIFY `id_partner` int unsigned NOT NULL;');
        Schema::table('form_surveys', function (Blueprint $table) {
            $table->foreign('id_partner', 'fk_survey_partner')->references('id_partner')->on('partners')->onDelete('restrict');
            $table->dropColumn('title');
        });
    }
}
