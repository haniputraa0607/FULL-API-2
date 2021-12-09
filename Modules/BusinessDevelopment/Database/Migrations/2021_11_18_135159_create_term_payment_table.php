<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTermPaymentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        Schema::create('term_of_payments', function (Blueprint $table) {
            $table->increments('id_term_of_payment');
            $table->integer('id_company')->nullable();
            $table->string('name')->nullable();
            $table->double('duration')->nullable();
            $table->enum('is_deleted',['true','false'])->default('false');
            $table->timestamps();
        });
        Schema::table('partners', function (Blueprint $table) {
            $table->integer('id_term_payment')->unsigned()->nullable()->change();
            $table->foreign('id_term_payment','fk_partner_term_payment')->references('id_term_of_payment')->on('term_of_payments')->onDelete('restrict'); 
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
            $table->dropForeign('fk_partner_term_payment');
            $table->dropIndex('fk_partner_term_payment');
        });
        Schema::dropIfExists('term_of_payments');
        Schema::table('partners', function (Blueprint $table) {
            $table->integer('id_term_payment')->nullable()->change();
        });
    }
}
