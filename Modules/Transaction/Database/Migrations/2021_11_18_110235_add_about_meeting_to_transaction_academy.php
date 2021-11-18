<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAboutMeetingToTransactionAcademy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('transaction_academy', function (Blueprint $table) {
            $table->integer('transaction_academy_duration')->nullable()->after('payment_method');
            $table->integer('transaction_academy_total_meeting')->nullable()->after('payment_method');
            $table->integer('transaction_academy_hours_meeting')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_academy', function (Blueprint $table) {
            $table->dropColumn('transaction_academy_duration');
            $table->dropColumn('transaction_academy_total_meeting');
            $table->dropColumn('transaction_academy_hours_meeting');
        });
    }
}
