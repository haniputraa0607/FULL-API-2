<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBusinessPartnerIdToUserHairstylistTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->string('id_business_partner')->nullable()->after('file_contract');
            $table->string('id_business_partner_ima')->nullable()->after('id_business_partner');
            $table->string('id_term_payment')->nullable()->after('id_business_partner_ima');
            $table->string('id_group_business_partner')->nullable()->after('id_term_payment');
            $table->string('id_company')->nullable()->after('id_group_business_partner');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_hair_stylist', function (Blueprint $table) {
            $table->dropColumn('id_business_partner');
            $table->dropColumn('id_business_partner_ima');
            $table->dropColumn('id_term_payment');
            $table->dropColumn('id_group_business_partner');
            $table->dropColumn('id_company');
        });
    }
}
