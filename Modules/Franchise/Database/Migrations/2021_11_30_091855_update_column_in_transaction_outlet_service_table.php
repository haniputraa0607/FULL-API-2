<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnInTransactionOutletServiceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_outlet_services', function (Blueprint $table) {
            $table->dateTime('reject_at')->after('completed_at')->nullable();
            $table->string('reject_reason')->after('reject_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_outlet_services', function (Blueprint $table) {
            $table->dropColumn('reject_at');
            $table->dropColumn('reject_reason');
        });
    }
}
