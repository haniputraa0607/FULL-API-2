<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTermPaymentToLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->integer('id_term_of_payment')->nullable()->unsigned()->after('handover_date');
            $table->enum('ownership_status', ['Central','Partner'])->nullable()->after('id_term_of_payment');
            $table->enum('cooperation_scheme', ['Profit Sharing','Management Fee'])->nullable()->after('ownership_status');
            $table->boolean('sharing_percent')->default(0)->after('cooperation_scheme');
            $table->integer('sharing_value')->nullable()->after('sharing_percent');

            $table->foreign('id_term_of_payment', 'fk_location_term_payment')->on('term_of_payments')->references('id_term_of_payment')->onDelete('restrict');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign('fk_location_term_payment');
            $table->dropIndex('fk_location_term_payment');
        }); 

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('id_term_of_payment');
            $table->dropColumn('ownership_status');
            $table->dropColumn('cooperation_scheme');
            $table->dropColumn('sharing_percent');
            $table->dropColumn('sharing_value');
        }); 
    }
}
