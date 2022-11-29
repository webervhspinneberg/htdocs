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
  <title>Komfortstufe1</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="winmod.css" />
</head>

<body>
  
  <div class="wrapper">
    <div class="content">    
    <img class="logo" src="./Logo_KPSH.jpg" />
    <h1>Komfortsuche Stufe 1</h1>
      <?php
      if (isset($_GET["Kurssuche"]) or isset($_GET["thema"])) {
        $Kurssuche = $_GET["Kurssuche"];
        $thema = $_GET["thema"];
        $region = $_GET["region"];
      } else {
        $Kurssuche = $_REQUEST["Kurssuche"];
        $thema = $_REQUEST["thema"];
        $region = $_REQUEST["region"];
        $wb_suchende = $_REQUEST['wb_suchende'];
        $beratungsperson = $_REQUEST['beratungsperson'];
        $unternehmen = $_REQUEST['unternehmen'];
        $einsteiger_start = $_REQUEST["einsteiger_start"];
        $fortgeschritten_start = $_REQUEST["fortgeschritten_start"];
        $spezialist_start = $_REQUEST["spezialist_start"];
      }
      if ($einsteiger_start == "1") {
        $kompetenz_start = "1";
      } elseif ($fortgeschritten_start == "1") {
        $kompetenz_start = "2";
      } elseif ($spezialist_start == "1") {
        $kompetenz_start = "3";
      }

      if ($wb_suchende == "1") {
        $suchcat = "1";
      } elseif ($beratungsperson == "1") {
        $suchcat = "2";
      } elseif ($unternehmen == "1") {
        $suchcat = "3";
      }
      else
      {
        $suchcat = "1";
      }
      $paramweiter = "?Kurssuche=" . $Kurssuche . "&thema=" . $thema .
        "&region=" . $region . "&suchcat=" . $suchcat .
        "&kompetenz_start=" . $kompetenz_start;

      //    $einsteiger_end = $_REQUEST["einsteiger_end"];
      //    $fortgeschritten_end = $_REQUEST["fortgeschritten_end"];
      //    $spezialist_end = $_REQUEST["spezialist_end"];


      if (isset($Kurssuche) and !empty($Kurssuche)) {
        print "<h1>Um zu Deiner Weiterbildungssuche zu " . "\"" . $Kurssuche . "\"" . " passendere Empfehlungen zu erhalten, benötigen wir noch einige Angaben von Dir." . "</h1>\n";
      } elseif (isset($thema) and !empty($thema) and isset($region) and !empty($region)) {
        print "<h1>Um zu Deiner Weiterbildungssuche zum Thema " . "\"" . $thema . "\"" .
          " im Bereich " . "\"" . $region . "\" passendere Empfehlungen zu erhalten, benötigen wir noch einige Angaben von Dir." . "</h1>\n";
      } elseif (isset($thema) and !empty($thema) and !isset($region)) {
        print "<h1>Um zu Deiner Weiterbildungssuche zum Thema " . "\"" . $thema . "\" passendere Empfehlungen zu erhalten, benötigen wir noch einige Angaben von Dir.  " . "</h1>\n";
      } else {
        print "<h1>Um zu Deiner Weiterbildungssuche passendere Empfehlungen zu erhalten, benötigen wir noch einige Angaben von Dir." . "</h1>\n";
      }

      ?>
      <table class="detailstable">
        <tr>
          <td colspan="3">
            Zu welcher Nutzergruppe gehören Sie?
          </td>
        </tr>
        <tr>
          <td>
            <?php print '<Form method="post" action="./Komfortstufe_1.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="einsteiger_start" value="' . $einsteiger_start . '"/>';
            print '<input type=hidden name="fortgeschritten_start" value="' . $fortgeschritten_start . '"/>';
            print '<input type=hidden name="unternehmen" value="' . $unternehmen . '"/>';
            print "\n";
            ?>

            <input type=hidden name="wb_suchende" value="1" />
            <input type="hidden" name="beratungsperson" value="0" />
            <input type="hidden" name="unternehmen" value="0" />
            <?php
            if ($wb_suchende == "1") {
              print '<input type=submit value="WB_Suchende*r" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="WB_Suchende*r" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_1.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="einsteiger_start" value="' . $einsteiger_start . '"/>';
            print '<input type=hidden name="fortgeschritten_start" value="' . $fortgeschritten_start . '"/>';
            print '<input type=hidden name="unternehmen" value="' . $unternehmen . '"/>';
            print "\n";
            ?>
            <input type=hidden name="wb_suchende" value="0" />
            <input type="hidden" name="beratungsperson" value="1" />
            <input type="hidden" name="unternehmen" value="0" />
            <?php
            if ($beratungsperson == "1") {
              print '<input type=submit value="Beratungsperson" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Beratungsperson" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_1.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="einsteiger_start" value="' . $einsteiger_start . '"/>';
            print '<input type=hidden name="fortgeschritten_start" value="' . $fortgeschritten_start . '"/>';
            print '<input type=hidden name="unternehmen" value="' . $unternehmen . '"/>';
            print "\n";
            ?>
            <input type=hidden name="wb_suchende" value="0" />
            <input type="hidden" name="beratungsperson" value="0" />
            <input type="hidden" name="unternehmen" value="1" />
            <?php
            if ($unternehmen == "1") {
              print '<input type=submit value="Unternehmen" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Unternehmen" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
        </tr>
      </table>
      <table class="detailstable">
        <tr>
          <td colspan="3">
            Wie schaetzt Du deine bisherigen Kenntnisse fuer dieses Lernziel ein?
          </td>
        </tr>
        <tr>
          <td>
            <?php print '<Form method="post" action="./Komfortstufe_1.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="wb_suchende" value="' . $wb_suchende . '"/>';
            print '<input type=hidden name="beratungsperson" value="' . $beratungsperson . '"/>';
            print '<input type=hidden name="unternehmen" value="' . $unternehmen . '"/>';
            print "\n";
            ?>

            <input type=hidden name="einsteiger_start" value="1" />
            <input type="hidden" name="fortgeschritten_start" value="0" />
            <input type="hidden" name="spezialist_start" value="0" />
            <?php
            if ($einsteiger_start == "1") {
              print '<input type=submit value="Einsteiger*in" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Einsteiger*in" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_1.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="wb_suchende" value="' . $wb_suchende . '"/>';
            print '<input type=hidden name="beratungsperson" value="' . $beratungsperson . '"/>';
            print '<input type=hidden name="unternehmen" value="' . $unternehmen . '"/>';
            print "\n";
            ?>
            <input type=hidden name="einsteiger_start" value="0" />
            <input type="hidden" name="fortgeschritten_start" value="1" />
            <input type="hidden" name="spezialist_start" value="0" />
            <?php
            if ($fortgeschritten_start == "1") {
              print '<input type=submit value="Fortgeschritten" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Fortgeschritten" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_1.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="wb_suchende" value="' . $wb_suchende . '"/>';
            print '<input type=hidden name="beratungsperson" value="' . $beratungsperson . '"/>';
            print '<input type=hidden name="unternehmen" value="' . $unternehmen . '"/>';
            print "\n";
            ?>
            <input type=hidden name="einsteiger_start" value="0" />
            <input type="hidden" name="fortgeschritten_start" value="0" />
            <input type="hidden" name="spezialist_start" value="1" />
            <?php
            if ($spezialist_start == "1") {
              print '<input type=submit value="Spezialist*in" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Spezialist*in" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <?php print '<a href="Komfortstufe_2.php' . $paramweiter . '">Du suchst Anregungen und Ideen fuer deine Weiterbildung?<br>' . "\n"; ?>
            Wir helfen dir bei der Suche. Dafuer brauchen wir noch ein paar Informationen mehr ueber dich.</a>
          </td>
        </tr>
        <tr>
        <td colspan="3">
          <?php print '<a href="Komfortstufe_Beratung.php' . $paramweiter . '">Du suchst ein Beratungsangebot fuer deine berufliche Entwicklung?<br>' . "\n"; ?>
          Hier geht es zu deinen Beratungsmoeglichkeiten.</a>
        </td>
        </tr>
        <tr>
          <td colspan="3">
            <?php print '<a href="Komfortstufe_Foerderung.php' . $paramweiter . '">Du suchst eine gefoerderte Weiterbildung?<br>' . "\n"; ?>
            Hier geht es zu deinen Foerdermoeglichkeiten.</a>
          </td>
        </tr>
        <tr>
      </table>
      <br><br>

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