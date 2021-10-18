<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnsToPartnersAndLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('code')->after('id_partner')->nullable();
            $table->enum('gender', ['Man','Woman'])->nullable()->after('name');
            $table->integer('npwp')->after('id_bank_account')->nullable();
            $table->string('npwp_name')->after('npwp')->nullable();
            $table->string('npwp_address')->after('npwp_name')->nullable();
            $table->integer('is_suspended')->after('status')->default(0);
            $table->integer('is_tax')->after('is_suspended')->default(0);
            $table->integer('price_level')->after('is_tax')->default(0);
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->string('mall')->after('address')->nullable();
            $table->integer('installment')->after('partnership_fee')->nullable();
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
            $table->dropColumn('code');
            $table->dropColumn('gender');
            $table->dropColumn('npwp');
            $table->dropColumn('npwp_name');
            $table->dropColumn('npwp_address');
            $table->dropColumn('is_suspended');
            $table->dropColumn('is_tax');
            $table->dropColumn('price_level');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('mall');
            $table->dropColumn('installment');
        });
    }
}
