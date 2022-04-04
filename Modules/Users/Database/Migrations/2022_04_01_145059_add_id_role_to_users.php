<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdRoleToUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('id_outlet')->nullable()->after('level');
            $table->unsignedInteger('id_role')->nullable()->after('level');
            $table->dropColumn('id_department');
            $table->dropColumn('id_job_level');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('id_outlet');
            $table->dropColumn('id_role');
            $table->unsignedInteger('id_department')->nullable()->after('level');
            $table->unsignedInteger('id_job_level')->nullable()->after('level');
        });
    }
}
