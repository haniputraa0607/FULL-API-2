<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnPartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->integer('id_company')->after('id_partner')->nullable();
            $table->integer('id_cluster')->after('id_company')->nullable();
            $table->string('contact_person')->after('name')->nullable();
            $table->enum('group',['0','1','2'])->after('gender')->nullable();
            $table->string('mobile')->after('phone')->nullable();
            $table->integer('id_term_payment')->after('end_date')->nullable();
            $table->integer('id_account_payable')->after('id_term_payment')->nullable();
            $table->integer('id_account_receivable')->after('id_account_payable')->nullable();
            $table->integer('id_sales_disc')->after('id_account_receivable')->nullable();
            $table->integer('id_purchase_disc')->after('id_sales_disc')->nullable();
            $table->integer('id_tax_in')->after('id_purchase_disc')->nullable();
            $table->integer('id_tax_out')->after('id_tax_in')->nullable();
            $table->integer('id_salesman')->after('id_tax_out')->nullable();
            $table->integer('id_sales_deposit')->after('id_salesman')->nullable();
            $table->text('notes')->after('id_salesman')->nullable();

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
            $table->dropColumn('id_company');
            $table->dropColumn('id_cluster');
            $table->dropColumn('contact_person');
            $table->dropColumn('group');
            $table->dropColumn('mobile');
            $table->dropColumn('id_term_payment');
            $table->dropColumn('id_account_payable');
            $table->dropColumn('id_account_receivable');
            $table->dropColumn('id_sales_disc');
            $table->dropColumn('id_purchase_disc');
            $table->dropColumn('id_tax_in');
            $table->dropColumn('id_tax_out');
            $table->dropColumn('id_salesman');
            $table->dropColumn('id_sales_deposit');
            $table->dropColumn('notes');
        });
    }
}
