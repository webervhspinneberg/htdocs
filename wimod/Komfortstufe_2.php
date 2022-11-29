<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]>      <html class="no-js"> <!--<![endif]-->
<html>

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Komfortstufe_2</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="winmod.css" />
</head>

<body>
  <div class="wrapper">
    <div class="content">
      <img class="logo" src="./Logo_KPSH.jpg" />
      <h1>Komforsuche Stufe 2</h1>
      <?php
      require_once "./wbprofil.php";

      ?>
      <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
      <?php
      if (isset($_GET["Kurssuche"]) or isset($_GET["thema"])) {
        wbprofil($_GET, "Weiterbildungsmoeglichkeiten", "0");
        $Kurssuche = $_GET["Kurssuche"];
        $thema = $_GET["thema"];
        $region = $_GET["region"];
        $suchcat = $_GET["suchcat"];
        $kompetenz_start = $_GET["kompetenz_start"];
      } else {
        wbprofil($_REQUEST, "Weiterbildungsmoeglichkeiten", "0");
        $Kurssuche = $_REQUEST["Kurssuche"];
        $thema = $_REQUEST["thema"];
        $region = $_REQUEST["region"];
        $suchcat = $_REQUEST["suchcat"];
        $kompetenz_start = $_REQUEST["kompetenz_start"];
        $komp_ausbstud = $_REQUEST["komp_ausbstud"];
        $komp_beruf = $_REQUEST["komp_beruf"];
        $komp_wb = $_REQUEST["komp_wb"];
      }
      $paramweiter = "?Kurssuche=" . $Kurssuche . "&thema=" . $thema .
        "&region=" . $region . "&suchcat=" . $suchcat .
        "&kompetenz_start=" . $kompetenz_start . 
        "&komp_ausbstud=" . $komp_ausbstud . 
        "&komp_beruf=" . $komp_beruf . 
        "&komp_wb=" . $komp_wb;
      ?>
      <table class="detailstable">
        <tr>
          <td colspan="3">
            Welche Kompetenzen hast du durch eine Ausbildung oder ein Studium?
          </td>
        </tr>
        <tr>
          <td>
            <?php print '<Form method="post" action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_beruf" value="' . $komp_beruf . '"/>';
            print '<input type=hidden name="komp_wb" value="' . $komp_wb . '"/>';
            print "\n";
            ?>

            <input type=hidden name="komp_ausbstud" value="1" />
            <?php
            if ($komp_ausbstud == "1") {
              print '<input type=submit value="Meister" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Meister" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_beruf" value="' . $komp_beruf . '"/>';
            print '<input type=hidden name="komp_wb" value="' . $komp_wb . '"/>';
            print "\n";
            ?>
            <input type=hidden name="komp_ausbstud" value="2" />
            <?php
            if ($komp_ausbstud == "2") {
              print '<input type=submit value="Bachelor" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Bachelor" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_beruf" value="' . $komp_beruf . '"/>';
            print '<input type=hidden name="komp_wb" value="' . $komp_wb . '"/>';
            print "\n";
            ?>
            <input type=hidden name="komp_ausbstud" value="3" />
            <?php
            if ($komp_ausbstud == "3") {
              print '<input type=submit value="Master" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Master" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
        </tr>
      </table>
      <table class="detailstable">
        <tr>
          <td colspan="3">
            Welche Kompetenzen hast du aufgrund von Berufserfahrung?
          </td>
        </tr>
        <tr>
          <td>
            <?php print '<Form method="post" action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_ausbstud" value="' . $komp_ausbstud . '"/>';
            print '<input type=hidden name="komp_wb" value="' . $komp_wb . '"/>';
            print "\n";
            ?>

            <input type="hidden" name="komp_beruf" value="1" />
            <?php
            if ($komp_beruf == "1") {
              print '<input type=submit value="0 - 2 Jahre Praxis" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="0 - 2 Jahre Praxis" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_ausbstud" value="' . $komp_ausbstud . '"/>';
            print '<input type=hidden name="komp_wb" value="' . $komp_wb . '"/>';
            print "\n";
            ?>
            <input type="hidden" name="komp_beruf" value="2" />
            <?php
            if ($komp_beruf == "2") {
              print '<input type=submit value="3 - 10 Jahre Praxis" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="3 - 10 Jahre Praxis" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_ausbstud" value="' . $komp_ausbstud . '"/>';
            print '<input type=hidden name="komp_wb" value="' . $komp_wb . '"/>';
            print "\n";
            ?>
            <input type="hidden" name="komp_beruf" value="3" />
            <?php
            if ($komp_beruf == "3") {
              print '<input type=submit value="Mehr als 10 Jahre Praxis" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="Mehr als 10 Jahre Praxis" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
        </tr>
      </table>
      <table class="detailstable">
        <tr>
          <td colspan="3">
            Welche Kompetenzen hast du aufgrund von Qualifikationen/Weiterbildung?
          </td>
        </tr>
        <tr>
          <td>
            <?php print '<Form method="post" action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_ausbstud" value="' . $komp_ausbstud . '"/>';
            print '<input type=hidden name="komp_beruf" value="' . $komp_beruf . '"/>';
            print "\n";
            ?>

            <input type="hidden" name="komp_wb" value="1" />
            <?php
            if ($komp_wb == "1") {
              print '<input type=submit value="VHS-Zertifikat Stufe A" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="VHS-Zertifikat Stufe A" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_ausbstud" value="' . $komp_ausbstud . '"/>';
            print '<input type=hidden name="komp_beruf" value="' . $komp_beruf . '"/>';
            print "\n";
            ?>
            <input type="hidden" name="komp_wb" value="2" />
            <?php
            if ($komp_wb == "2") {
              print '<input type=submit value="VHS-Zertifikat Stufe B" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="VHS-Zertifikat Stufe B" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
          <td>
            <?php print '<Form method="post"  action="./Komfortstufe_2.php">';

            print '<input type=hidden name="Kurssuche" value="' . $Kurssuche . '"/>';
            print '<input type=hidden name="thema" value="' . $thema . '"/>';
            print '<input type=hidden name="region" value="' . $region . '"/>';
            print '<input type=hidden name="suchcat" value="' . $suchcat . '"/>';
            print '<input type=hidden name="kompetenz_start" value="' . $kompetenz_start . '"/>';
            print '<input type=hidden name="komp_ausbstud" value="' . $komp_ausbstud . '"/>';
            print '<input type=hidden name="komp_beruf" value="' . $komp_beruf . '"/>';
            print "\n";
            ?>
            <input type="hidden" name="komp_wb" value="3" />
            <?php
            if ($komp_wb == "3") {
              print '<input type=submit value="VHS-Zertifikat Stufe C" class="auswahlkachel_selected"/>';
            } else {
              print '<input type=submit value="VHS-Zertifikat Stufe C" class="auswahlkachel"/>';
            }
            ?>
            </form>
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <?php print '<a href="komfortresult.php' . $paramweiter . '">Hier gehts zu unseren Empfehlungen.<br>' . "\n"; ?>
          </td>
        </tr>
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