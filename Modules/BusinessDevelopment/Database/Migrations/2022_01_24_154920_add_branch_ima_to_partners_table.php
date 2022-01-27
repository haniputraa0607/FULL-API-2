<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBranchImaToPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('id_business_partner_ima')->nullable()->after('id_business_partner');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->string('id_branch_ima')->nullable()->after('id_branch');
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
            $table->dropColumn('id_business_partner_ima');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('id_branch_ima');
        });
    }
}
