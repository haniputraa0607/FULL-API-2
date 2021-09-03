<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->increments('id_user_franchise');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->string('address')->nullable();
            $table->enum('ownership_status', ['Central','Partner'])->default('Central');
            $table->enum('cooperation_scheme', ['Profit Sharing','Management Fee'])->default('Profit Sharing');
            $table->bigInteger('id_bank_account')->unsigned()->index('fk_partner_bank_account');
            $table->enum('status', ['Active','Inactive','Candidate'])->default('Candidate');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->string('password')->nullable();
            $table->boolean('first_update_password')->default(1);
            $table->timestamps();
            $table->foreign('id_bank_account', 'fk_partner_bank_account')->references('id_bank_account')->on('bank_accounts')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners');
    }
}
