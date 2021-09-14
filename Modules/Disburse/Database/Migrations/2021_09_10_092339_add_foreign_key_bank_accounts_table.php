<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyBankAccountsTable extends Migration
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
        
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->bigInteger('id_bank_name')->unsigned()->change();
            $table->foreign('id_bank_name','fk_bank_name')->references('id_bank_name')->on('bank_name')->onDelete('restrict'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropForeign('fk_bank_name');
            $table->dropIndex('fk_bank_name');
        });
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->integer('id_bank_name')->change();
        });
    }
}
