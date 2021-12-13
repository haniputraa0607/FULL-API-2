<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRejectAtAndRejectReasonToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
        	$table->datetime('reject_at')->nullable()->default(null)->after('scope');
            $table->string('reject_reason')->nullable()->after('reject_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
        	$table->dropColumn('reject_at');
            $table->dropColumn('reject_reason');
        });
    }
}
