<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataSuratPerjanjian extends Migration
{
     public function up()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('surat_perjanjian')->nullable();
        });
    }
    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('surat_perjanjian')->nullable();
        });
    }
}
