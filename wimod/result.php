<?php
  error_reporting(0);
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]>      <html class="no-js"> <!--<![endif]-->
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title></title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="winmod.css" />
    <img class="logo" src="./Logo_KPSH.jpg" />

</head>

<body>
    <div class="wrapper">
        <div class="content">
        <?php
             if (isset($_GET["Kurssuche"]))
             {
                 $Kurssuche = $_GET["Kurssuche"];
             }
             else
             {
                 $thema = $_REQUEST["thema"];
                 $region = $_REQUEST["region"];
     
             }
            ?>
            <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
            <div class="frame">
                <h1>Die angezeigten Ergebnisse reichen mir nicht aus.<br></h1>
                <?php print '<a href="Komfortstufe_1.php?Kurssuche=' . $Kurssuche . '&thema=' . $thema . '&region=' . $region . '">';?>
                    <h1>Weiter zur Komfortsuche</h1>
                </a>
            </div>
            <?php
 

            if (isset($Kurssuche) and !empty($Kurssuche)) {
                print "<h1>Ihre Kurssuche zu " . "\"" . $Kurssuche . "\"" . " ergab folgendes Ergebnis:" . "</h1><br>\n";
            } elseif (isset($thema) and !empty($thema) and isset($region) and !empty($region)) {
                print "<h1>Unsere Empfehlungen zum Thema " . "\"" . $thema . "\"" . " im Bereich " . "\"" . $region . "\"" . "</h1><br>\n";
            } elseif (isset($thema) and !empty($thema) and !isset($region)) {
                print "<h1>Unsere Empfehlungen zum Thema " . "\"" . $thema . "\"" . "</h1><br>\n";
            }
            ?>
            <img src="./Kursliste.png" />
            <div class="frame">
                <h1>Die angezeigten Ergebnisse reichen mir nicht aus.<br></h1>
                <?php print '<a href="Komfortstufe_1.php?Kurssuche=' . $Kurssuche . '&thema=' . $thema . '&region=' . $region . '">';?>
                    <h1>Weiter zur Komfortsuche</h1>
                </a>
            </div>
        </div>
        <footer>
            <table>
                <tr>
                    <td colspan="3">
                        Das koennte Dich auch interessieren:
                    </td>
                </tr>
                <tr>
                    <td>
                        Allgemeine Beratungsmoeglichkeiten
                    </td>
                    <td>Allgemeine Foerdermoeglichkeiten</td>
                    <td>Hilfe</td>
                <tr>
                    <td>
                        Service
                    </td>
                    <td>
                        Anbieter
                    </td>
                </tr>
                </tr>
            </table>
        </footer>
    </div>
</body>

</html>