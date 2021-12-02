<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStepLocationsLogsTable extends Migration
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
        Schema::create('step_locations_logs', function (Blueprint $table) {
            $table->increments('id_step_locations_log');
            $table->integer('id_location');
            $table->enum('follow_up', ['Follow Up','Survey Location','Calculation','Confirmation Letter','Payment'])->default('Follow Up');
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
            $table->timestamps();
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->enum('step_loc', ['On Follow Up','Finished Follow Up','Survey Location','Calculation','Confirmation Letter','Payment'])->nullable()->after('value_detail');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('step_locations_logs');
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('step_loc');
        });
    }
}
