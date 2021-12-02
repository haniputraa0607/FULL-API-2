<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToPartnerAndLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('trans_date');
            $table->dropColumn('due_date');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->date('trans_date')->after('total_payment')->nullable();
            $table->date('due_date')->after('trans_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->date('trans_date')->after('notes')->nullable();
            $table->date('due_date')->after('trans_date')->nullable();
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('trans_date');
            $table->dropColumn('due_date');
        });
    }
}
