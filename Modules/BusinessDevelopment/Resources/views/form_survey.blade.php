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
        .table-borderless > tbody > tr > td,
        .table-borderless > tbody > tr > th,
        .table-borderless > thead > tr > td,
        .table-borderless > thead > tr > th {
            border: none;
            padding: 2px 10px 2px 2px;
            border-color: black;
        }
        .kotak {
            border: 1px;
            padding: 5px;
        }
        .main > table > tbody> tr > td {
            font-size: 6pt;
        }
        .head tr td,
        .head tr th{
            font-weight: bold;
        }
        .no {
            text-align: right;
        }
        .checklist {
            text-align: center;
        }
        .judul {
            font-weight: bold;
            background-color: #dee2e6;
        }
        .sub {
            margin-left: 20px;
        }
        main {
            margin: 10px 30px 30px 30px;
        }
        .ok {
            font-size: 9px;
            padding-left: 9px 
        }
        .total {
            text-align: right;
        }
        .table td {
            padding: 10px;
        }
        h5 {
            font-family: "Times New Roman", Times, serif;
            letter-spacing: 7px;
            font-size: 11pt;
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
        <div class="row" style="text-align: left">
             <div class="col-md-12">
                <img src="{{ $logo }}" alt="" style="height:50px;"/>
            </div>
        </div>
    </header>

    <main>
        <center>
            <h5 class="font-weight-bold mb-0">TARGET MARKET CRITERIA IXOBOX OUTLET</h5>
        </center>
        
        <table class="table table-borderless mt-3 mb-0" width="700px" style="font-size: 11px" nobr>
            <thead>
                <tr>
                    <td class="no" width="30px"></td>
                    <td width="400px">Location Name : {{ $location }} </td>
                    <td width="270px" colspan="4">Survey Date : {{ $date }}</td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td width="400px">Surveyor : {{ $surveyor }}</td>
                    <td width="270px" colspan="4">Outlet Potential : </td>
                </tr>
                <tr>
                    <td></td>
                    <td width="400px">Sub-Brand : {{ $brand }}</td>
                    <td width="20px" style="border: 1px; text-align: center;border-style: solid; padding: 2px; !important">@if ($potential==1) 1 @endif</td>
                    <td style="padding-left: 5px"><span class="ok">OK</span></td>
                    <td width="20px" style="border: 1px; text-align: center;border-style: solid; padding: 2px; !important">@if ($potential==0) 1 @endif</td>
                    <td style="padding-left: 5px"><span class="ok pl-4">NOT OK</span></td>
                </tr>
            </tbody>
        </table>
        
        <table class="table table-bordered mt-4 mb-0 head" width="700px" style="font-size: 10px" nobr>
            <thead>
                <tr class="text-center">
                    <td width="370px" style="background-color: #dee2e6;">KONDISI & KRITERIA </td>
                    <td width="25px">a</td>
                    <td width="25px">b</td>
                    <td width="25px">c</td>
                    <td width="25px">d</td>
                </tr>
            </thead>
        </table>

        <table class="table table-bordered mt-2 mb-0 main" width="700px" style="font-size: 9px" nobr>
            <tbody>
                <tr>
                    <td class="judul pl-4" colspan="6">A. <span class="sub" style="padding-left: 10px; margin-left: 10px;">KONDISI UMUM LOKASI</span></td>
                </tr>
                @foreach ($cat1 as $c1)
                <tr>
                    <td class="no" width="8px">{{ $no++ }}</td>
                    <td width="340px">{{ $c1['question'] }}</td>
                    <td class="checklist" width="25px">@if ($c1['answer']=='a')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c1['answer']=='b')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c1['answer']=='c')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c1['answer']=='d')&#10003;@endif</td>
                </tr>
                @endforeach
                <tr>
                    <td class="judul pl-4" colspan="6">B. <span class="sub" style="padding-left: 10px; margin-left: 10px;">KONDISI DALAM LOKASI</span></td>
                </tr>
                @foreach ($cat2 as $c2)
                <tr>
                    <td class="no" width="8px">{{ $no++ }}</td>
                    <td width="340px">{{ $c2['question'] }}</td>
                    <td class="checklist" width="25px">@if ($c2['answer']=='a')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c2['answer']=='b')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c2['answer']=='c')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c2['answer']=='d')&#10003;@endif</td>
                </tr>
                @endforeach
                <tr>
                    <td class="judul pl-4" colspan="6">C. <span class="sub" style="padding-left: 10px; margin-left: 10px;">UNIT OUTLET YANG DITAWARKAN</span></td>
                </tr>
                    @foreach ($cat3 as $c3)
                    <tr>
                    <td class="no" width="8px">{{ $no++ }}</td>
                    <td width="340px">{{ $c3['question'] }}</td>
                    <td class="checklist" width="25px">@if ($c3['answer']=='a')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c3['answer']=='b')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c3['answer']=='c')&#10003;@endif</td>
                    <td class="checklist" width="25px">@if ($c3['answer']=='d')&#10003;@endif</td>
                </tr>
                @endforeach
                <tr>
                    <td rowspan="2" colspan="2" class="total" style="vertical-align: middle; border-bottom-style: hidden; border-left-style: hidden; !important"> Total Score</td>
                    <td class="checklist" width="25px">{{ $total_a }}</td>
                    <td class="checklist" width="25px">{{ $total_b }}</td>
                    <td class="checklist" width="25px">{{ $total_c }}</td>
                    <td class="checklist" width="25px">{{ $total_d }}</td>
                </tr>
                <tr>
                    <td colspan="4">Nilai = {{ $total }}</td>
                </tr>
                <tr>
                    <td style="border-right-style: hidden; border-left-style: hidden;"></td>
                    <td style="border-right-style: hidden; border-left-style: hidden;" colspan="5">Note: a=4, b=3, c=2, d=1 </td>
                </tr>
                <tr class="keterangan">
                    <td colspan="2">Keterangan & Referensi</td>
                    <td colspan="4">Surveyor</td>
                </tr>
                <tr>
                    <td rowspan="2" colspan="2">{{ $note }}</td>
                    <td colspan="4" height="100px"></td>
                </tr>
                <tr>
                    <td colspan="4" height="70px">Name: {{ $surveyor }}</td>
                </tr>
            </tbody>
        </table>
    </main> 
</body>
</html>