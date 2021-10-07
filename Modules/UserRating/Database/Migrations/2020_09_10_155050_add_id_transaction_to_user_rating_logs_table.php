<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdTransactionToUserRatingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_rating_logs', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction')->after('id_user')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_rating_logs', function (Blueprint $table) {
            $table->dropColumn('id_transaction');
        });
    }
}
