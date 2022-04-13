<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdXenditAccountToOutletsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->unsignedBigInteger('id_xendit_account')->after('id_outlet')->nullable();

            $table->foreign('id_xendit_account', 'fk_ixa_o_xa')->on('xendit_accounts')->references('id_xendit_account')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropForeign('fk_ixa_o_xa');
            $table->dropColumn('id_xendit_account');
        });
    }
}
