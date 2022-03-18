<!DOCTYPE html>
<html>
<head>
	<title><?php echo $title ?></title>
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
        <?php echo $content ?>
    </main>
</body>
</html>