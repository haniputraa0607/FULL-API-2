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
        <h6 class="font-weight-normal mb-0">{{ $data['lokasi_surat'] }}, {{ $data['tanggal_surat'] }} </h6>
        <h6 class="font-weight-normal mb-0">No: {{ $data['no_surat'] }}</h6>
        <br>
        <h6 class="font-weight-normal mb-0">PIHAK I	 :	PT IXOBOX MULTITREN ASIA </h6>
        <h6 class="font-weight-normal mb-0">PIHAK II :	{{ $data['pihak_dua'] }} </h6>
        <h6 class="font-weight-normal mb-0">LOCATION : 	{{ $data['location_mall'] }} - {{ $data['location_city'] }} </h6>
        
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
                            <li>{{ $data['address'] }};</li>
                            <li>Luas Lokasi = {{ $data['large'] }} M2;</li>
                            <li>Masa Kerja Sama antara Pihak Kedua dengan Mall, minimal 3 (tiga) tahun dan akan diperpanjang oleh Pihak Kedua;</li>
                            <li>Perjanjian Kerja Sama Operasional ini hanya berlaku untuk lokasi yang tercantum dalam poin 2a;</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td width="130px">MASA KERJASAMA</td>
                    <td>{{ $data['total_waktu'] }}, terdiri 3 (tiga) tahun pertama{{ $data['sisa_waktu'] }}</td>
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
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <table class='table table-bordered mt-4 mb-0' width="700px" nobr>
            <thead>
                <tr>
                    <td width="10px"></td>
                    <td width="130px"></td>
                    <td>
                        <ol class="pl-2 text-justify terluar" start="2">
                            <li>Tugas dan tanggung jawab Pihak II:
                                <ul class="pl-0 text-justify dalam">
                                    <li>Membayar Partnership Fee kepada Pihak I (point 6);</li>
                                    <li>Menanggung biaya bulanan yang berhubungan dengan lokasi (mall) setelah outlet beroperasi: biaya sewa, service charge, promotion levy, biaya listrik, biaya air (jika ada), dan lain-lain;</li>
                                    <li>Biaya-biaya yang berhubungan dengan pemeliharaan dan kerusakan fisik outlet, dan asuransi outlet (jika ada);</li>
                                </ul>
                            </li>
                        </ol>
                    </td>
                </tr>
            </thead>
            <tbody>
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
                            <li>Partnership fee = Rp {{ $data['partnership_fee'] }} ({{ $data['partnership_fee_string'] }}). Harga belum termasuk PPN 10%. Harga tersebut mencakup:
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
                            <li>Booking fee kesepakatan kerjasama Ixobox 20% = Rp {{ $data['dp'] }},- ({{ $data['dp_string'] }} Rupiah), belum termasuk PPN. Dibayar maksimal 5 hari kerja setelah ditandatangani surat konfirmasi ini;</li>
                            <li>Down Payment 1 = Rp {{ $data['dp2'] }},- ({{ $data['dp2_string'] }} Rupiah), belum termasuk PPN dan dibayar maksimal 2 minggu sebelum proses renovasi dilakukan;</li>
                            <li>Final Payment = Rp {{ $data['final'] }},- ({{ $data['final_string'] }} Rupiah), belum termasuk PPN;</li>
                            @if (isset($data['angsuran']) && !empty($data['angsuran']))
                            <li>{{ $data['angsuran'] }};</li>    
                            @endif
                            <li>Pembayaran dilakukan dengan cara transfer ke PT.Ixobox Multitren Asia, Bank BCA, nomor rekening: 6840308608 dan seluruh pembayaran yang telah disepakati dan dilakukan oleh Pihak II kepada Pihak I, tidak dapat dikembalikan;</li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>
        <table class='table table-bordered mt-4 mb-0 pb-0' width="700px" nobr>
            <tbody>
                <tr>
                    <td width="10px">8</td>
                    <td width="130px" colspan="2">OTHER (INITIAL) INVESTMENT</td>
                    <td colspan="8">
                        <ol class="pl-2 text-justify terluar">
                            <li>Biaya-biaya awal yang berhubungan dengan lokasi seperti instalasi listrik, internet, hoarding, APAR dan lainnya serta persiapan fit out akan dibayar oleh Pihak II kepada pemilik unit lokasi (manajemen mal);</li>
                            <li>Biaya-biaya yang berhubungan dengan fit out lokasi, dibayar oleh Pihak II kepada kontraktor, yang ditunjuk oleh Pihak I (termasuk biaya transportasi dan akomodasi tim kontraktor);</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td>9</td>
                    <td width="130px" colspan="2">LAIN-LAIN</td>
                    <td colspan="8">Detail dari Surat Konfimasi ini akan dituangkan dalam Perjanjian Kerjasama Operasional (KSO), yang akan ditanda-tangani oleh Pihak I dan Pihak II.</td>
                </tr>
                <tr class="text-center">
                    <td colspan="6">Pihak Pertama <br>
                        <b>PT Ixobox Multitren Asia</b> <br><br><br><br><br>
                        <b><u>Alese Sandria</u></b> <br>
                        <b>General Manager</b>
                    </td>
                    <td colspan="5">Pihak Kedua <br><br><br><br><br><br>
                        <b><u>{{ $data['ttd_pihak_dua'] }}</u></b> <br>
                        <b>Business Partner</b>
                    </td>
                </tr>
            </tbody>
        </table> 
    </main>
    
 
</body>
</html>