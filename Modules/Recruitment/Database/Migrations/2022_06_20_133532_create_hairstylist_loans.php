<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistLoans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_loans', function (Blueprint $table) {
            $table->Increments('id_hairstylist_loan');
            $table->unsignedBigInteger('id_user_hair_stylist');
            $table->unsignedBigInteger('id_hairstylist_category_loan');
            $table->foreign('id_user_hair_stylist')->on('user_hair_stylist')->references('id_user_hair_stylist')->onDelete('cascade');
            $table->date('effective_date')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('installment')->nullable();
            $table->enum('type',['Flat'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hairstylist_loans');
    }
}
