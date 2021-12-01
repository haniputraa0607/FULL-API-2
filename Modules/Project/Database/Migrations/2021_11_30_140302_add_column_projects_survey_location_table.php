<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnProjectsSurveyLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_survey_location', function (Blueprint $table) {
            $table->enum('kondisi',['Bare','Tidak','Lainnya'])->default('Bare');
            $table->string('keterangan_kondisi',255)->nullable();
            $table->enum('listrik',['Ada','Tidak'])->nullable('Ada');
            $table->string('keterangan_listrik',255)->nullable();
            $table->enum('ac',['Ada','Tidak'])->nullable('Ada');
            $table->string('keterangan_ac',255)->nullable();
            $table->enum('air',['Ada','Tidak'])->nullable('Ada');
            $table->string('keterangan_air',255)->nullable();
            $table->enum('internet',['Ada','Tidak'])->nullable('Ada');
            $table->string('keterangan_internet',255)->nullable();
            $table->enum('line_telepon',['Ada','Tidak'])->nullable('Ada');
            $table->string('keterangan_line_telepon',255)->nullable();
            $table->string('cp_pic_mall',255)->nullable();
            $table->string('nama_kontraktor',255)->nullable();
            $table->string('cp_kontraktor',255)->nullable();
            $table->date('tanggal_mulai_pekerjaan')->nullable();
            $table->date('tanggal_selesai_pekerjaan')->nullable();
            $table->date('tanggal_loading_barang')->nullable();
            $table->enum('area_lokasi',['Jabodetabek','Non Jabodetabek'])->nullable('Ada');
            $table->date('tanggal_pengiriman_barang')->nullable();
            $table->date('estimasi_tiba')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects_survey_location', function (Blueprint $table) {
            $table->dropColumn('kondisi',['Bare','Tidak','Lainnya'])->default('Bare');
            $table->dropColumn('keterangan_kondisi',255)->nullable();
            $table->dropColumn('listrik',['Ada','Tidak'])->nullable('Ada');
            $table->dropColumn('keterangan_listrik',255)->nullable();
            $table->dropColumn('ac',['Ada','Tidak'])->nullable('Ada');
            $table->dropColumn('keterangan_ac',255)->nullable();
            $table->dropColumn('air',['Ada','Tidak'])->nullable('Ada');
            $table->dropColumn('keterangan_air',255)->nullable();
            $table->dropColumn('internet',['Ada','Tidak'])->nullable('Ada');
            $table->dropColumn('keterangan_internet',255)->nullable();
            $table->dropColumn('line_telepon',['Ada','Tidak'])->nullable('Ada');
            $table->dropColumn('keterangan_line_telepon',255)->nullable();
            $table->dropColumn('cp_pic_mall',255)->nullable();
            $table->dropColumn('nama_kontraktor',255)->nullable();
            $table->dropColumn('cp_kontraktor',255)->nullable();
            $table->dropColumn('tanggal_mulai_pekerjaan')->nullable();
            $table->dropColumn('tanggal_selesai_pekerjaan')->nullable();
            $table->dropColumn('tanggal_loading_barang')->nullable();
            $table->dropColumn('area_lokasi',['Jabodetabek','Non Jabodetabek'])->nullable('Ada');
            $table->dropColumn('tanggal_pengiriman_barang')->nullable();
            $table->dropColumn('estimasi_tiba')->nullable();
        });
    }
}
