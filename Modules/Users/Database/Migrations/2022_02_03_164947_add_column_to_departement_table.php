<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnToDepartementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->smallInteger('from_icount')->default(0)->after('id_parent');
            $table->text('id_department_icount')->nullable()->after('from_icount');
            $table->text('code_icount')->nullable()->after('id_department_icount');
            $table->enum('is_actived', ['true','false'])->default('true')->after('code_icount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('from_icount');
            $table->dropColumn('id_department_icount');
            $table->dropColumn('code_icount');
            $table->dropColumn('is_actived');
        });
    }
}
