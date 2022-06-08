<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataEmployee extends Migration
{
     public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('id_cluster')->default(013);
            $table->string('code')->nullable()->unique();
            $table->integer('number')->nullable();
            $table->string('id_business_partner')->nullable();
            $table->string('id_business_partner_ima')->nullable();
            $table->string('id_term_payment')->default(011);
            $table->string('id_group_business_partner')->nullable();
            $table->string('id_company')->nullable();
            $table->string('npwp_name')->nullable();
            $table->string('npwp_address')->nullable();
            $table->string('contact_person')->nullable();
            $table->boolean('is_tax')->nullable();
            $table->text('notes')->nullable();
            $table->enum('type',[0,1,2])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('id_cluster');
            $table->dropColumn('code');
            $table->dropColumn('number');
            $table->dropColumn('id_business_partner');
            $table->dropColumn('id_business_partner_ima');
            $table->dropColumn('id_term_payment');
            $table->dropColumn('id_group_business_partner');
            $table->dropColumn('id_company');
            $table->dropColumn('npwp_name');
            $table->dropColumn('npwp_address');
            $table->dropColumn('contact_person');
            $table->dropColumn('is_tax');
            $table->dropColumn('notes');
            $table->dropColumn('type',['0','1','2']);
        });
    }
}
