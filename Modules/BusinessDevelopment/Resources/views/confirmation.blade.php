<?php 
    if($partner['gender']=='Man'){
        $gender = 'BAPAK';
    }elseif($partner['gender']=='Woman'){
        $gender = 'IBU';
    }
    $pihakDua = $gender.' '.strtoupper($partner['name']);
    $bulan = array (1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember');
    $pecah = explode('-', $letter['date']);
    $date = $pecah[2].' '.$bulan[intval($pecah[1])].' '.$pecah[0];
    $mall = strtoupper($location['mall']);
    $lok = strtoupper($city['city_name']);
    $loc = $mall.' - '.$lok;
    //nominal
    $pf_rupiah = rupiah($location['partnership_fee']);
    $pf_string = stringNominal(intval($location['partnership_fee']));
    $dp = intval($location['partnership_fee']) * 0.2;
    $dp_rupiah = rupiah($dp);
    $dp_string = stringNominal($dp);
    $dp_2 = intval($location['partnership_fee']) * 0.3;
    $dp_2_rupiah = rupiah($dp_2);
    $dp_2_string = stringNominal($dp_2);
    $final = intval($location['partnership_fee']) * 0.5;
    $final_rupiah = rupiah($final);
    $final_string = stringNominal($final);
    if($location['installment']==null || $location['installment']==0 || $location['installment']==1){
        $angsur = 'dibayarkan dalam satu kali pembayaran tanpa angsuran dan tidak mengurangi pembagian hasil kepada Pihak II sesuai dengan poin 5a';
    }else{
        $angsuran = $final / intval($location['installment']);
        $angsuran_rupiah = rupiah($angsuran);
        $angsuran_string = stringNominal($angsuran);
        $angsur = 'diangsur selama '. intval($location['installment']) .' kali, masing-masing Rp '.$angsuran_rupiah.',- ('.$angsuran_string.' Rupiah) per bulan sebelum PPN selama '. intval($location['installment']) .' bulan dan angsuran tersebut akan mengurangi pembagian hasil kepada Pihak II sesuai dengan poin 5a';
    }
    //waktu
    $start_date = explode('-', $partner['start_date']);
    $end_date = explode('-', $partner['end_date']);
    if($end_date[2]==$start_date[2] && $end_date[1]==$start_date[1]){
        $tahun = $end_date[0]-$start_date[0];
        $string_tahun = strtolower(stringNominal($tahun));
        $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun';
        $array_waktu = [
            0 => $tahun,
        ];
    }elseif($end_date[1]==$start_date[1]){
        $selisih_tanggal = $end_date[2]-$start_date[2];
        if($start_date[1]==2){
            if($start_date[0]%4==0){
                $jumlah_hari = 29;
            }else{
                $jumlah_hari =28;
            }
        }elseif($start_date[1]==4 || $start_date[1]==6 || $start_date[1]==9 || $start_date[1]==11){
            $jumlah_hari = 30;
        }else{
            $jumlah_hari = 31;
        }
        if($selisih_tanggal>0){
            $tahun = $end_date[0]-$start_date[0];
            $tanggal = $end_date[2]-$start_date[2];
        }else{
            $awal = intval($start_date[2]);
            $akhir = intval($end_date[2]);
            $tahun = ($end_date[0]-$start_date[0])-1;
            $tanggal = ($jumlah_hari-$awal)+$akhir;
        }
        $string_tahun = strtolower(stringNominal($tahun));
        $string_tanggal = strtolower(stringNominal($tanggal));
        $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$tanggal.' ('.$string_tanggal.')'.' hari';
        $array_waktu = [
            0 => $tahun,
            2 => $tanggal,
        ];
    }elseif($end_date[2]==$start_date[2]){
        $selisih_bulan = $end_date[1]-$start_date[1];
        if($selisih_bulan>0){
            $tahun = $end_date[0]-$start_date[0];
            $bulan = $end_date[1]-$start_date[1];
        }else{
            $awal = intval($start_date[1]);
            $akhir = intval($end_date[1]);
            $tahun = ($end_date[0]-$start_date[0])-1;
            $bulan = (12-$awal)+$akhir;
        }
        $string_tahun = strtolower(stringNominal($tahun));
        $string_bulan = strtolower(stringNominal($bulan));
        $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$bulan.' ('.$string_bulan.')'.' bulan';
        $array_waktu = [
            0 => $tahun,
            1 => $bulan,
        ];
    }else{
        $selisih_bulan = $end_date[1]-$start_date[1];
        $selisih_tanggal = $end_date[2]-$start_date[2];
        if($start_date[1]==2){
            if($start_date[0]%4==0){
                $jumlah_hari = 29;
            }else{
                $jumlah_hari =28;
            }
        }elseif($start_date[1]==4 || $start_date[1]==6 || $start_date[1]==9 || $start_date[1]==11){
            $jumlah_hari = 30;
        }else{
            $jumlah_hari = 31;
        }
        if($selisih_tanggal>0){
            if($selisih_bulan>0){
                $tahun = $end_date[0]-$start_date[0];
                $bulan = $end_date[1]-$start_date[1];
                $tanggal = $end_date[2]-$start_date[2];
            }else{
                $awal = intval($start_date[1]);
                $akhir = intval($end_date[1]);
                $tahun = ($end_date[0]-$start_date[0])-1;
                $bulan = (12-$awal)+$akhir;
                $tanggal = $end_date[2]-$start_date[2];
            }
            $string_tahun = strtolower(stringNominal($tahun));
            $string_bulan = strtolower(stringNominal($bulan));
            $string_tanggal = strtolower(stringNominal($tanggal));
            $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$bulan.' ('.$string_bulan.')'.' bulan '.$tanggal.' ('.$string_tanggal.')'.' hari';
            $array_waktu = [
                0 => $tahun,
                1 => $bulan,
                2 => $tanggal,
            ];
        }else{
            if($selisih_bulan==1){
                $tahun = $end_date[0]-$start_date[0];
                $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                $string_tahun = strtolower(stringNominal($tahun));
                $string_tanggal = strtolower(stringNominal($tanggal));
                $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$tanggal.' ('.$string_tanggal.')'.' hari';
                $array_waktu = [
                    0 => $tahun,
                    2 => $tanggal,
                ];
            }elseif($selisih_bulan>0){
                $tahun = $end_date[0]-$start_date[0];
                $bulan = $end_date[1]-$start_date[1];
                $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                $string_tahun = strtolower(stringNominal($tahun));
                $string_bulan = strtolower(stringNominal($bulan));
                $string_tanggal = strtolower(stringNominal($tanggal));
                $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$bulan.' ('.$string_bulan.')'.' bulan '.$tanggal.' ('.$string_tanggal.')'.' hari';
                $array_waktu = [
                    0 => $tahun,
                    1 => $bulan,
                    2 => $tanggal,
                ];
            }else{
                $awal = intval($start_date[1]);
                $akhir = intval($end_date[1]);
                $tahun = ($end_date[0]-$start_date[0])-1;
                $bulan = (12-$awal)+$akhir;
                $tanggal = ($jumlah_hari-$start_date[2])+$end_date[2];
                $string_tahun = strtolower(stringNominal($tahun));
                $string_bulan = strtolower(stringNominal($bulan));
                $string_tanggal = strtolower(stringNominal($tanggal));
                $total_waktu = $tahun.' ('.$string_tahun.')'.' tahun '.$bulan.' ('.$string_bulan.')'.' bulan '.$tanggal.' ('.$string_tanggal.')'.' hari';
                $array_waktu = [
                    0 => $tahun,
                    1 => $bulan,
                    2 => $tanggal,
                ];
            }
        }
        
    }
    //sisa
    $sisa = $array_waktu[0] - 3;
    if($sisa==0){
        if(isset($array_waktu[1]) && isset($array_waktu[2])){
            $string_sisa = ' + '.$array_waktu[1].' ('.strtolower(stringNominal($array_waktu[1])).')'.' bulan '.$array_waktu[2].' ('.strtolower(stringNominal($array_waktu[2])).')'.' hari berikutnya;';
        }elseif(isset($array_waktu[1])){
            $string_sisa = ' + '.$array_waktu[1].' ('.strtolower(stringNominal($array_waktu[1])).')'.' bulan berikutnya;';
        }elseif(isset($array_waktu[2])){
            $string_sisa = ' + '.$array_waktu[2].' ('.strtolower(stringNominal($array_waktu[2])).')'.' hari berikutnya;';
        }else{
            $string_sisa = ';';
        }
    }else{
        if(isset($array_waktu[1]) && isset($array_waktu[2])){
            $string_sisa = ' + '.$sisa.' ('.strtolower(stringNominal($sisa)).')'.' tahun '.$array_waktu[1].' ('.strtolower(stringNominal($array_waktu[1])).')'.' bulan '.$array_waktu[2].' ('.strtolower(stringNominal($array_waktu[2])).')'.' hari berikutnya;';
        }elseif(isset($array_waktu[1])){
            $string_sisa = ' + '.$sisa.' ('.strtolower(stringNominal($sisa)).')'.' tahun '.$array_waktu[1].' ('.strtolower(stringNominal($array_waktu[1])).')'.' bulan berikutnya;';
        }elseif(isset($array_waktu[2])){
            $string_sisa = ' + '.$sisa.' ('.strtolower(stringNominal($sisa)).')'.' tahun '.$array_waktu[2].' ('.strtolower(stringNominal($array_waktu[2])).')'.' hari berikutnya;';
        }else{
            $string_sisa = ' + '.$sisa.' ('.strtolower(stringNominal($sisa)).')'.' tahun;';
        }
    }
    
    function rupiah($nominal){
        $rupiah = number_format($nominal ,0, ',' , '.' );
        return $rupiah;
    }

    function stringNominal($angka) {
        
        $angka = $angka;
        $bilangan = array('','Satu','Dua','Tiga','Empat','Lima','Enam','Tujuh','Delapan','Sembilan','Sepuluh','Sebelas');

        if ($angka < 12) {
            return $bilangan[$angka];
        } else if ($angka < 20) {
            return $bilangan[$angka - 10] . ' Belas';
        } else if ($angka < 100) {
            $hasil_bagi = ($angka / 10);
            $hasil_mod = $angka % 10;
            return trim(sprintf('%s Puluh %s', $bilangan[$hasil_bagi], $bilangan[$hasil_mod]));
        } else if ($angka < 200) {
            return sprintf('Seratus %s', stringNominal($angka - 100));
        } else if ($angka < 1000) {
            $hasil_bagi = ($angka / 100);
            $hasil_mod = $angka % 100;
            return trim(sprintf('%s Ratus %s', $bilangan[$hasil_bagi], stringNominal($hasil_mod)));
        } else if ($angka < 2000) {
            return trim(sprintf('Seribu %s', stringNominal($angka - 1000)));
        } else if ($angka < 1000000) {
            $hasil_bagi = ($angka / 1000);
            $hasil_mod = $angka % 1000;
            return sprintf('%s Ribu %s', stringNominal($hasil_bagi), stringNominal($hasil_mod));
        } else if ($angka < 1000000000) {
            $hasil_bagi = ($angka / 1000000);
            $hasil_mod = $angka % 1000000;
            return trim(sprintf('%s Juta %s', stringNominal($hasil_bagi), stringNominal($hasil_mod)));
        } else if ($angka < 1000000000000) {
            $hasil_bagi = ($angka / 1000000000);
            $hasil_mod = fmod($angka, 1000000000);
            return trim(sprintf('%s Milyar %s', stringNominal($hasil_bagi), stringNominal($hasil_mod)));
        } else if ($angka < 1000000000000000) {
            $hasil_bagi = $angka / 1000000000000;
            $hasil_mod = fmod($angka, 1000000000000);
            return trim(sprintf('%s Triliun %s', stringNominal($hasil_bagi), stringNominal($hasil_mod)));
        } else {
            return 'Data Salah';
        }
    }
?>

<!DOCTYPE html>
<html>
<head>
	<title>Membuat Laporan PDF Dengan DOMPDF Laravel</title>
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
                <img src="{{env('STORAGE_URL_API') }}{{ ('/images/logo_pdf.png')}}" alt="" style="height:50px;"/>
            </div>
        </div>
    </header>

    <main>
    <div class="contect">
        <h5 class="font-weight-bold mb-0" style="font-size: 11pt">SURAT KONFIRMASI</h5>
        <h6 class="font-weight-normal mb-0">{{ $letter['location'] }}, {{ $date }} </h6>
        <h6 class="font-weight-normal mb-0">No: {{ $letter['no_letter'] }}</h6>
        <br>
        <h6 class="font-weight-normal mb-0">PIHAK I	 :	PT IXOBOX MULTITREN ASIA </h6>
        <h6 class="font-weight-normal mb-0">PIHAK II :	{{ $pihakDua }} </h6>
        <h6 class="font-weight-normal mb-0">LOCATION : 	{{ $loc }} </h6>
        
        <div class="page_break">
            <table class='table table-bordered mt-4 mb-0' width="700px" nobr>
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
                                <li>Pihak I sebagai pemegang merek IXOBOX di teritori wilayah Indonesia, bermaksud mengadakan Perjanjian Kerja Sama Operasional (KSO) dengan Pihak II dalam mengembangkan unit outlet Ixobox di Kota Kasablanka, Jakarta;</li>
                                <li>Pihak I menunjuk PT. Ixobox Mitra Sejahtera sebagai Sole Operator di teritori wilayah Indonesia, untuk membantu Pihak II menjalankan operasional unit outlet Ixobox di Kota Kasablanka, Jakarta;</li>
                            </ol>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td width="130px">UNIT OUTLET</td>
                        <td>
                            <ol class="pl-2 text-justify terluar">
                                <li>{{ $location['address'] }};</li>
                                <li>Luas Lokasi= {{ $location['location_large'] }} M2;</li>
                                <li>Masa Kerja Sama antara Pihak Kedua dengan Mall, minimal 3 (tiga) tahun dan akan diperpanjang oleh Pihak Kedua;</li>
                                <li>Perjanjian Kerja Sama Operasional ini hanya berlaku untuk lokasi yang tercantum dalam poin 2a;</li>
                            </ol>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td width="130px">MASA KERJASAMA</td>
                        <td>{{ $total_waktu }}, terdiri 3 (tiga) tahun pertama{{ $string_sisa }}</td>
                    </tr>
                    <tr>
                        <td>4</td>
                        <td width="130px">ROLES & RESPONSIBILITIEST</td>
                        <td>
                            <ol class="pl-2 text-justify terluar">
                                <li>Tugas dan tanggung jawab Pihak I:
                                    <ul class="pl-0 mb-1 text-justify dalam">
                                        <li>Pihak I bertanggung jawab untuk mengelola sumber daya manusia (tenaga kerja) agar operasional outlet dapat berjalan dengan baik;</li>
                                        <li>Pengembangan Merek;</li>
                                        <li>Melakukan pemeliharaan dan pengembangan Ixobox system;</li>
                                        <li>Pengembangan SOP dan sistem pendukung lainnya;</li>
                                        <li>Melakukan pelatihan dan pengembangan kualitas tenaga kerja;</li>
                                        <li>Pembagian hasil setiap bulan kepada Pihak II (poin 5);</li>
                                        <li>Menanggung seluruh biaya operasional termasuk gaji dan insentif tenaga kerja;</li>
                                        <li>Biaya akomodasi dan transportasi yang berhubungan dengan peningkatan kualitas dan pengembangan tenaga kerja ataupun penambahan atau penggantian tenaga kerja (jika diperlukan);</li>
                                        <li>Biaya-biaya supplies dan biaya-biaya yang berhubungan dengan operasional;</li>
                                        <li>Biaya media marketing promotion secara lokal dan nasional;</li>
                                    </ul>
                                </li>
                                <li>Tugas dan tanggung jawab Pihak II:
                                    <ul class="pl-0 text-justify dalam">
                                        <li>Membayar Partnership Fee kepada Pihak I (point 6);</li>
                                        <li>Menanggung biaya bulanan yang berhubungan dengan lokasi (mall) setelah outlet beroperasi: biaya sewa, service charge, promotion levy, biaya listrik, biaya air (jika ada), dan lain-lain;</li>
                                        <li>Biaya-biaya yang berhubungan dengan pemeliharaan dan kerusakan fisik outlet, dan asuransi outlet (jika ada);</li>
                                    </ul>
                                </li>
                            </ol>
                        </td>
                        <tr>
                            <td>5</td>
                            <td width="130px"PEMBAGIAN HASIL KERJASAMA</td>
                            <td>Selama masa perjanjian Kerjasama, pembagian hasil yang dibagikan oleh Pihak I kepada Pihak II adalah sebagai berikut:
                                <ol class="pl-2 text-justify terluar">
                                    <li>Skema pembagian hasil antara Pihak I dan Pihak II: 50%:50% dihitung dari penghasilan bersih outlet (net revenue), setelah dipotong 10% untuk management contribution dan diskon penjualan serta biaya administrasi bank atau merchant discount rate dari digital payment (jika ada);</li>
                                    <li>Pembayaran akan ditransfer oleh Pihak I ke rekening Pihak II, dalam 15 hari kerja bulan berikutnya setiap bulan sesuai poin 5a;</li>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <td>6</td>
                            <td width="130px"PARTNERSHIP FEE</td>
                            <td>
                                <ol class="pl-2 text-justify terluar">
                                    <li>Partnership fee = Rp {{ $pf_rupiah }},- ({{ $pf_string }} Rupiah). Harga belum termasuk PPN 10%. Harga tersebut mencakup:
                                        <ul class="pl-0 text-justify dalam">
                                            <li>Paket 5 Boxes termasuk peralatan dan perlengkapan pendukung operasional;</li>
                                            <li>Furniture pelengkap seperti bench, credenza, meja, bangku, lemari dan lainnya;</li>
                                            <li>Sistem pendukung, hardware dan software termasuk kiosk mesin, handphone dan fraud system panel, cctv dan finger print;</li>
                                            <li>Starter kit package untuk supplies dan perlengkapan;</li>
                                            <li>Decoration dan marketing tools seperti signage, TV, poster, sticker dan lainnya;</li>
                                            <li>Penggunaan merek Ixobox selama masa berlaku Perjanjian Kerja Sama;</li>
                                            <li>Penggunaan SOP dan sistem pendukung Ixobox lainnya;</li>
                                            <li>Recruitment, Training dan Penempatan Tenaga kerja dan tenaga pendukung lainnya, selama masa berlaku Perjanjian Kerja Sama;</li>
                                            <li>3D & 2D Ixobox Kota Kasablanka design concept;</li>
                                        </ul>
                                    </li>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <td>7</td>
                            <td width="130px">CARA PEMBAYARAN</td>
                            <td>
                                <ol class="pl-2 text-justify terluar">
                                    <li>Booking fee kesepakatan kerjasama Ixobox 20% = Rp {{ $dp_rupiah }},- ({{ $dp_string }} Rupiah), belum termasuk PPN. Dibayar maksimal 5 hari kerja setelah ditandatangani surat konfirmasi ini;</li>
                                    <li>Down Payment 1 = Rp {{ $dp_2_rupiah }},- ({{ $dp_2_string }} Rupiah), belum termasuk PPN dan dibayar maksimal 2 minggu sebelum proses renovasi dilakukan;</li>
                                    <li>Final Payment = Rp {{ $final_rupiah }},- ({{ $final_string }} Rupiah), belum termasuk PPN akan {{ $angsur  }};</li>
                                    <li>Pembayaran dilakukan dengan cara transfer ke PT.Ixobox Multitren Asia, Bank BCA, nomor rekening: 6840308608 dan seluruh pembayaran yang telah disepakati dan dilakukan oleh Pihak II kepada Pihak I, tidak dapat dikembalikan;</li>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <td>8</td>
                            <td width="130px">OTHER (INITIAL) INVESTMENT</td>
                            <td>
                                <ol class="pl-2 text-justify terluar">
                                    <li>Biaya-biaya awal yang berhubungan dengan lokasi seperti instalasi listrik, internet, hoarding, APAR dan lainnya serta persiapan fit out akan dibayar oleh Pihak II kepada pemilik unit lokasi (manajemen mal);</li>
                                    <li>Biaya-biaya yang berhubungan dengan fit out lokasi, dibayar oleh Pihak II kepada kontraktor, yang ditunjuk oleh Pihak I (termasuk biaya transportasi dan akomodasi tim kontraktor);</li>
                                </ol>
                            </td>
                        </tr>
                        <tr>
                            <td>9</td>
                            <td width="130px">LAIN-LAIN</td>
                            <td>Detail dari Surat Konfimasi ini akan dituangkan dalam Perjanjian Kerjasama Operasional (KSO), yang akan ditanda-tangani oleh Pihak I dan Pihak II.</td>
                        </tr>
                    </tr>
                </tbody>
            </table>
        </div>
        <table class='table table-bordered mt-4' width="700px" style="margin-top: 0px !important">
            <thead>
                <tr class="text-center">
                    <td width="50%">Pihak Pertama <br>
                        <b>PT Ixobox Multitren Asia</b> <br><br><br><br><br>
                        <b><u>Alese Sandria</u></b> <br>
                        <b>General Manager</b>
                    </td>
                    <td width="50%">Pihak Kedua <br><br><br><br><br><br>
                        <b><u>{{ $partner['name'] }}</u></b> <br>
                        <b>Business Partner</b>
                    </td>
                </tr>
            </thead>
        </table>    
    </main>
    
 
</body>
</html>