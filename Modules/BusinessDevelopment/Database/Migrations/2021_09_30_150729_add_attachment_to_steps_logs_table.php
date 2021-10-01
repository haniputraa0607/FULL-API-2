<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAttachmentToStepsLogsTable extends Migration
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
        Schema::table('steps_logs', function (Blueprint $table) {
            $table->text('note')->nullable()->change();
            $table->string('attachment',255)->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('steps_logs', function (Blueprint $table) {
            $table->string('note',255)->nullable()->change();
            $table->dropColumn('attachment');
        });
    }
}
