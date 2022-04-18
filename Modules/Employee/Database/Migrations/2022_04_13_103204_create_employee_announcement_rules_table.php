<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeAnnouncementRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_announcement_rules', function (Blueprint $table) {
            $table->increments('id_employee_announcement_rule');
			$table->integer('id_employee_announcement_rule_parent')->unsigned()->nullable()->index('fk_employee_announcement_rules_parents');
			$table->string('employee_announcement_rule_subject', 191);
			$table->enum('employee_announcement_rule_operator', array('=','like','>','<','>=','<='));
			$table->string('employee_announcement_rule_param', 191);
			$table->string('employee_announcement_rule_param_select', 191)->nullable();
			$table->integer('employee_announcement_rule_param_id')->nullable();
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
        Schema::dropIfExists('employee_announcement_rules');
    }
}
