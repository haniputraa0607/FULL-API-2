<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionAcademyScheduleTheoryCategory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_academy_schedule_theory_categories', function (Blueprint $table) {
            $table->bigIncrements('id_transaction_academy_schedule_theory_category');
            $table->unsignedInteger('id_theory_category');
            $table->unsignedInteger('id_transaction_academy');
            $table->integer('conclusion_score')->nullable();
            $table->timestamps();
        });

        Schema::table('transaction_academy_schedule_theories', function (Blueprint $table) {
            $table->dropColumn('id_transaction_academy');
            $table->unsignedInteger('id_transaction_academy_schedule_theory_category')->after('id_transaction_academy_schedule');
            $table->integer('score')->default(0)->after('theory_title');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transaction_academy_schedule_theory_categories');

        Schema::table('transaction_academy_schedule_theories', function (Blueprint $table) {
            $table->unsignedInteger('id_transaction_academy');
            $table->dropColumn('id_transaction_academy_schedule_theory_category');
            $table->dropColumn('score');
        });
    }
}
