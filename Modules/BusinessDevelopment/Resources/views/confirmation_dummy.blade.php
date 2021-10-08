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
            margin-bottom: 15px;
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
    </style>
</head>
<body>
    <center>
		<img src="{{env('STORAGE_URL_API') }}{{ ('/images/logo_pdf.png')}}" alt="" style="height:150px" /> </a>
	</center>

    <h5 class="font-weight-bold mb-0" style="font-size: 11pt">SURAT KONFIRMASI</h5>
    <h6 class="font-weight-normal mb-0">Jakarta, 28 Februari 2021 </h6>
    <h6 class="font-weight-normal mb-0">No: 003/IMA/II/2021</h6>
    <br>
    <h6 class="font-weight-normal mb-0">PIHAK I	 :	PT IXOBOX MULTITREN ASIA </h6>
    <h6 class="font-weight-normal mb-0">PIHAK II :	CV. SUKSES ENAM SAHABAT </h6>
    <h6 class="font-weight-normal mb-0">LOCATION : 	KOTA KASABLANKA - JAKARTA </h6>

	<table class='table table-bordered mt-4 mb-0' width="700px">
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
                    <ol class="pl-3 text-justify terluar">
                        <li>Pihak I sebagai pemegang merek IXOBOX di teritori wilayah Indonesia, bermaksud mengadakan Perjanjian Kerja Sama Operasional (KSO) dengan Pihak II dalam mengembangkan unit outlet Ixobox di Kota Kasablanka, Jakarta;</li>
                        <li>Pihak I menunjuk PT. Ixobox Mitra Sejahtera sebagai Sole Operator di teritori wilayah Indonesia, untuk membantu Pihak II menjalankan operasional unit outlet Ixobox di Kota Kasablanka, Jakarta;</li>
                    </ol>
                </td>
            </tr>
			<tr>
                <td>2</td>
                <td width="130px">UNIT OUTLET</td>
                <td>
                    <ol class="pl-3 text-justify terluar">
                        <li>Kota Kasablanka Basement Floor 12, Jakarta <br>Jl. Casablanca Raya Kav. 88, Jakarta, 12870;</li>
                        <li>Luas Lokasi= 28 M2;</li>
                        <li>Masa Kerja Sama antara Pihak Kedua dengan Mall, minimal 3 (tiga) tahun dan akan diperpanjang oleh Pihak Kedua;</li>
                        <li>Perjanjian Kerja Sama Operasional ini hanya berlaku untuk lokasi yang tercantum dalam poin 2a;</li>
                    </ol>
                </td>
            </tr>
            <tr>
                <td>3</td>
                <td width="130px">MASA KERJASAMA</td>
                <td>6 (enam) tahun, terdiri 3 (tiga) tahun pertama + 3 (tiga) tahun berikutnya;</td>
            </tr>
            <tr>
                <td>4</td>
                <td width="130px">ROLES & RESPONSIBILITIEST</td>
                <td>
                    <ol class="pl-3 text-justify terluar">
                        <li>Tugas dan tanggung jawab Pihak I:
                            <ul class="pl-3 text-justify dalam">
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
                            <ul class="pl-3 text-justify dalam">
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
                        <ol class="pl-3 text-justify terluar">
                            <li>Skema pembagian hasil antara Pihak I dan Pihak II: 50%:50% dihitung dari penghasilan bersih outlet (net revenue), setelah dipotong 10% untuk management contribution dan diskon penjualan serta biaya administrasi bank atau merchant discount rate dari digital payment (jika ada);</li>
                            <li>Pembayaran akan ditransfer oleh Pihak I ke rekening Pihak II, dalam 15 hari kerja bulan berikutnya setiap bulan sesuai poin 5a;</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td>6</td>
                    <td width="130px"PARTNERSHIP FEE</td>
                    <td>
                        <ol class="pl-3 text-justify terluar">
                            <li>Partnership fee= 350.000.000,- (Tiga Ratus Lima Puluh Juta Rupiah). Harga belum termasuk PPN 10%. Harga tersebut mencakup:
                                <ul class="pl-3 text-justify dalam">
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
                        <ol class="pl-3 text-justify terluar">
                            <li>Booking fee kesepakatan kerjasama Ixobox 20% = Rp 70.000.000,- (Tujuh Puluh Juta Rupiah), belum termasuk PPN. Dibayar maksimal 5 hari kerja setelah ditandatangani surat konfirmasi ini;</li>
                            <li>Down Payment 1= Rp 105.000.000,- (Seratus Lima Juta Rupiah), belum termasuk PPN dan dibayar maksimal 2 minggu sebelum proses renovasi dilakukan;</li>
                            <li>Down Payment 2= Rp 125.000.000,- (Seratus Dua Puluh Lima Juta Rupiah), belum termasuk PPN dan dibayar 5 hari kerja sebelum unit lokasi beroperasional;</li>
                            <li>Final Payment= Rp 50.000.000,- (Lima Puluh Juta Rupiah), belum termasuk PPN akan diangsur selama 5 kali, masing-masing Rp 10.000.000,- (Sepuluh Juta Rupiah) per bulan sebelum PPN selama 5 bulan dan angsuran tersebut akan mengurangi pembagian hasil kepada Pihak II sesuai dengan poin 5a;</li>
                            <li>Pembayaran dilakukan dengan cara transfer ke PT.Ixobox Multitren Asia, Bank BCA, nomor rekening: 6840308608 dan seluruh pembayaran yang telah disepakati dan dilakukan oleh Pihak II kepada Pihak I, tidak dapat dikembalikan;</li>
                        </ol>
                    </td>
                </tr>
                <tr>
                    <td>8</td>
                    <td width="130px">OTHER (INITIAL) INVESTMENT</td>
                    <td>
                        <ol class="pl-3 text-justify terluar">
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
    <table class='table table-bordered mt-4' width="700px" style="margin-top: 0px !important">
		<thead>
			<tr class="text-center">
				<td width="50%">Pihak Pertama <br>
                    <b>PT Ixobox Multitren Asia</b> <br><br><br><br>
                    <b><u>Alese Sandria</u></b> <br>
                    <b>General Manager</b>
                </td>
				<td width="50%">Pihak Kedua <br><br><br><br><br>
                    <b><u>CV.Sukses Enam Sahabat </u></b> <br>
                    <b>Business Partner</b>
                </td>
			</tr>
		</thead>
	</table>
 
</body>
</html>