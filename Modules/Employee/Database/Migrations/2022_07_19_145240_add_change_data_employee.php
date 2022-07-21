<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChangeDataEmployee extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    } 
    public function up()
    {
      Schema::table('employee_perubahan_datas', function (Blueprint $table) {
            $table->unsignedInteger('id_approved')->nullable();
            $table->foreign('id_approved')->references('id')->on('users')->onDelete('restrict');
            $table->datetime('date_action')->nullable();
            $table->text('note_approved')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('employee_perubahan_datas', function (Blueprint $table) {
            $table->dropColumn('id_approved')->nullable();
            $table->dropColumn('date_action')->nullable();
            $table->dropColumn('note_approved')->nullable();
        });
    }
}
