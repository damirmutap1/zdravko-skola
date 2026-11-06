<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">


    <title>Основна школа Здравко Јовановић </title>


    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700&amp;subset=cyrillic" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=PT+Sans:400,700&amp;subset=cyrillic-ext" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Alice&amp;subset=cyrillic,cyrillic-ext" rel="stylesheet">



    <link href="../stylesheets/screen.css" rel="stylesheet">
    <link href="../css/tables.css" rel="stylesheet">

        <!-- OWL SLIDER PLUGIN -->
    <link rel="stylesheet" href="../css/owl.carousel.css">
    <link rel="stylesheet" href="../css/owl.theme.default.css">

</head>

<body>

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once('spreadsheet-reader-master/php-excel-reader/excel_reader2.php');
require_once('spreadsheet-reader-master/SpreadsheetReader.php');
require_once('function.php');

call_excel ('1.xls');


?>




<div id="response" class="<?php if(!empty($type)) { echo $type . " display-block"; } ?>"><?php if(!empty($message)) { echo $message; } ?></div>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="js/owl-slider/owl.carousel.min.js"></script>


</body>
</html>
