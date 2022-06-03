<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSettingUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting_users', function (Blueprint $table) {
            $table->bigIncrements('id_setting_user');
            $table->integer('id')->unsigned();
            $table->string('key');
            $table->string('value')->nullable();
            $table->text('value_text')->nullable();
            $table->timestamps();

            $table->foreign('id', 'fk_id_setting_user')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setting_users');
    }
}
