<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBookingEndHsNotAvailable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_not_available', function (Blueprint $table) {
            $table->dropColumn('booking_time');
            $table->dropColumn('booking_date');
            $table->dateTime('booking_end')->nullable()->after('id_transaction_product_service');
            $table->dateTime('booking_start')->nullable()->after('id_transaction_product_service');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_not_available', function (Blueprint $table) {
            $table->time('booking_time');
            $table->date('booking_date');
            $table->dropColumn('booking_start');
            $table->dropColumn('booking_end');
        });
    }
}
