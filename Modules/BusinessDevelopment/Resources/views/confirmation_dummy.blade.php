<!DOCTYPE html>
<html>
<head>
	<title>Confir</title>
    <!-- Required meta tags -->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <style type="text/css">
        table tr td,
        table tr th{
            font-size: 9pt;
        }
        body{
            margin: 10px 30px 30px 30px;
        }
        ol.terluar {
            list-style-type: lower-alpha;
            margin-bottom: 0px;
        }
        ul.dalam {
            list-style-type: circle;
        }
        ul li {
            margin-left: 5px;
            padding-left: 10px;
        }
        ol li {
            padding-left: 10px;
        }
        .table td {
            padding: 10px;
        }
        h6 {
            font-size: 10pt;
        }
        header { 
            position: fixed; 
            top: -60px; 
            left: 0px; 
            right: 0px; 
            bottom: 50px;
            height: 80px; 
        }
        @page { margin: 100px 25px; }
    </style>
</head>
<body>
    <header>
        <div class="row" style="text-align: center">
             <div class="col-md-12">
                <img src="{{env('STORAGE_URL_API') }}{{ ('images/logo_pdf.png')}}" alt="" style="height:50px;"/>
            </div>
        </div>
    </header>

    <main>
        <center><h5 class="font-weight-bold mb-1" style="font-size: 11pt">SURAT KONFIRMASI PERSETUJUAN KERJASAMA KEMITRAAN</h5></center>
        <h6 class="font-weight-normal mb-0">%lokasi_surat%, %tanggal_surat% </h6>
        <h6 class="font-weight-normal mb-0">No: %no_surat%</h6>
        <br>
        <h6 class="font-weight-normal mb-0">PIHAK I  :  PT IXOBOX MULTITREN ASIA </h6>
        <h6 class="font-weight-normal mb-0">PIHAK II :  %pihak_dua% </h6>
        <h6 class="font-weight-normal mb-0">LOCATION :  Ixobox %location_name% </h6>

        <table class="table table-bordered mt-4 mb-0" width="700px" nobr>
            <thead>
                <tr class="text-center">
                    <th width="10px">NO</th>
                    <th colspan="2">SERVICE DESCRIPTION</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td width="130px">OBJECTIVE</td>
                    <td>
                        <ol class="pl-2 text-justify terluar">
                            <li>Pihak Pertama adalah operator tunggal yang telah ditunjuk oleh PT Ixobox Multitren Asia (“PT IMA”) selaku pemegang Merek Dagang Ixobox untuk wilayah teritorial Negara Kesatuan Republik Indonesia untuk menjalankan operasional atas seluruh unit usaha Ixobox di wilayah teritori Negara Kesatuan Republik Indonesia dan bermaksud mengadakan Perjanjian Kerja Sama Operasional (KSO) dengan Pihak Kedua dalam membangun dan mengembangkan unit outlet Ixobox di lokasi %location_city%, %location_province%.</li>
                            <li>Pihak Kedua adalah mitra usaha Ixobox yang melakukan Kerja Sama Operasional dengan Pihak Pertama sesuai skema kerja sama yang disepakati bersama, dengan melakukan investasi kerja sama dan menyediakan lokasi usaha.</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td>2</td>
                    <td width="130px">UNIT OUTLET</td>
                    <td>
                        <ol class="pl-2 text-justify terluar">
                            <li>%address%.</li>
                            <li>Luas Lokasi = %large% M<sup>2</sup>;</li>
                            <li>Masa kerjasama antara Pihak Kedua dengan pengelola lokasi selama %total_waktu%. </li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td width="130px">PARTNERSHIP FEE</td>
                    <td>
                        <ol class="pl-2 text-justify terluar">
                            <li>Partnership fee = Rp %partnership_fee% (%partnership_fee_string%). sudah termasuk PPN 10%. Harga tersebut mencakup:
                                <ul class="pl-0 text-justify dalam">
                                    <li>Penggunaan Brand ixobox  & Masa Kerjasama selama 6 tahun</li>
                                    <li>Biaya Desain outlet</li>
                                    <li>Peralatan dan perlengkapan untuk paket %box% kursi</li>
                                    <li>Mesin (fraud system hardware) & sistem aplikasi pembayaran</li>
                                    <li>Pelatihan & penyediaan SDM</li>
                                    <li>Peralatan penunjang (CCTV, audio, finger print, TV, dll)</li>
                                    <li>Dekorasi penunjang & marketing supports (signage, poster, dll)</li>
                                </ul>
                            </li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td>4</td>
                    <td width="130px">CARA PEMBAYARAN</td>
                    <td>
                        <ol class="pl-2 text-justify terluar">
                            <li>Tahap 1, yaitu tanda jadi kesepakatan kerjasama Ixobox: 20% dari nilai partnership fee= Rp %dp% (%dp_string%) diluar PPN. Dibayar maksimal 5 hari kerja setelah ditandatangani surat konfirmasi  ini;</li>
                            <li>Tahap 2, yaitu 30% dari nilai partnership fee= Rp %dp2% (%dp2_string%) di luar PPN dan  dibayar 5 hari kerja sebelum unit lokasi direnovasi di luar PPN; </li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="table table-bordered mt-4 mb-0 pb-0" width="700px" nobr>
            <tbody>
                <tr>
                    <td width="10px"></td>
                    <td width="130px" colspan="2"></td>
                    <td colspan="8">
                        <ol class="pl-2 text-justify terluar">
                            <li>Tahap 3, yaitu 50% dari nilai partnership fee = Rp %final% (%final_string%) di luar PPN dan dibayar 5 hari kerja sebelum unit lokasi beroperasional di luar PPN;</li>
                            <li>Biaya survey & mobilisasi (pengiriman,akomodasi dan transportasi) di luar Jabodetabek menjadi kewajiban Pihak Kedua sesuai lokasi usaha </li> 
                            <li>Pembayaran dilakukan dengan cara transfer ke PT.Ixobox Multitren Asia, Bank BCA No: 6840308608;</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td>9</td>
                    <td width="130px" colspan="2">OTHER</td>
                    <td colspan="8">
                        <ol class="pl-2 text-justify terluar">
                            <li>Semua biaya awal yang berhubungan dengan lokasi dan persiapan fit out menjad</li>
                            <li>Semua biaya yang berhubungan dengan Fit Out lokasi dibayar oleh Pihak Kedua langsung kepada kontraktor yang direferensikan oleh Pihak Pertama, jika Pihak Kedua menggunakan kontraktor sendiri, seluruh <br> spesifikasi harus sesuai dengan standartisasi dan persetujuan tertulis dari Pihak Pertama;</li>
                            <li>Pihak Kedua tidak diijinkan melaksanakan renovasi tanpa persetujuan Pihak Pertama</li>
                            <li>Seluruh ketentuan detail kerjasama ini akan dituangkan dalam Perjanjian Kerja Sama Operasional (KSO) Jika Pihak Kedua melakukan pengunduran diri secara sepihak maka seluruh pembayaran yang telah dilakukan tidak dapat dikembalikan;</li>
                        </ol>
                    </td>
                </tr>
                <tr class="text-center">
                    <td colspan="6">Pihak Pertama <br>
                        <b>PT Ixobox Multitren Asia</b> <br><br><br><br><br>
                        <b><u>%position_name%</u></b> <br>
                        <b>%position%</b>
                    </td>
                    <td colspan="5">Pihak Kedua <br><br><br><br><br><br>
                        <b><u>%ttd_pihak_dua%</u></b> <br>
                        <b>Mitra Usaha</b>
                    </td>
                </tr>
            </tbody>
        </table>
        <h6 class="font-weight-normal mt-3 ml-4">* Detail Surat Konfimasi ini akan dituangkan dalam Perjanjian Kerjasama Operasional (KSO)</h6>

    </main>
</body>
</html>