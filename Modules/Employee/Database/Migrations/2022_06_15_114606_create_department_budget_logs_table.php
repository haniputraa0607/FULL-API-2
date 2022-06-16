<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDepartmentBudgetLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('department_budget_logs', function (Blueprint $table) {
            $table->Increments('id_department_budget_log');
            $table->unsignedInteger('id_department_budget');
            $table->date('date_budgeting');
            $table->string('source')->nullable();
            $table->integer('balance')->default(0);
            $table->integer('balance_before')->nullable();
            $table->integer('balance_after')->nullable();
            $table->integer('balance_total')->default(0);
            $table->integer('id_reference')->nullable();
            $table->string('notes')->nullable();

            $table->timestamps();
            $table->foreign('id_department_budget', 'fk_budget_department_log')->references('id_department_budget')->on('department_budgets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('department_budget_logs');
    }
}
