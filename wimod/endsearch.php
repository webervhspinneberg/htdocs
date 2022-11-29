<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]>      <html class="no-js"> <!--<![endif]-->
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="winmod.css" />
    <img class="logo" src="./Logo_KPSH_mit_Claim.jpg" />
    <title>Volltext- und Schlagwortsuche</title>
</head>

<body>
    <?php
    $thema = $_REQUEST["thema"];
    $region = $_REQUEST["region"];
    $einsteiger_start = $_REQUEST["einsteiger_start"];
    $fortgeschritten_start = $_REQUEST["fortgeschritten_start"];
    $spezialist_start = $_REQUEST["spezialist_start"];
    $einsteiger_end = $_REQUEST["einsteiger_end"];
    $fortgeschritten_end = $_REQUEST["fortgeschritten_end"];
    $spezialist_end = $_REQUEST["spezialist_end"];
    ?>
    <?php print "<h1>Volltext- und Schlagwortsuche zum Thema ". $thema . " im Bereich " . $region ."</h1><br>\n"; ?>
    <img src="./Textsuche.png" />
    <script src="" async defer></script>
</body>

</html>