<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistLoansReturn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_loan_returns', function (Blueprint $table) {
            $table->Increments('id_hairstylist_loan_return');
            $table->unsignedInteger('id_hairstylist_loan');
            $table->foreign('id_hairstylist_loan', 'fk_return_id_hairstylist_loan')->references('id_hairstylist_loan')->on('hairstylist_loans')->references('id_hairstylist_loan')->onDelete('cascade');
            $table->date('return_date')->nullable();
            $table->integer('amount_return')->nullable();
            $table->enum('status_return',['Pending','Success'])->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hairstylist_loans');
    }
}
