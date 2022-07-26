<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeIntegerBalanceToDecimalDepartmentBudgetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('department_budgets', function (Blueprint $table) {
            $table->decimal('budget_balance', 25, 4)->change();
        });

        Schema::table('department_budget_logs', function (Blueprint $table) {
            $table->decimal('balance', 25, 4)->change();
            $table->decimal('balance_before', 25, 4)->change();
            $table->decimal('balance_after', 25, 4)->change();
            $table->decimal('balance_total', 25, 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('department_budgets', function (Blueprint $table) {
            $table->integer('budget_balance')->change();
        });

        Schema::table('department_budget_logs', function (Blueprint $table) {
            $table->integer('balance')->change();
            $table->integer('balance_before')->change();
            $table->integer('balance_after')->change();
            $table->integer('balance_total')->change();
        });
    }
}
