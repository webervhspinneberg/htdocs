<?php
  error_reporting(0);
?>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="winmod.css" />
    <img class="logo" src="./Logo_KPSH_mit_Claim.jpg" />
    <?php echo "<title>Details zur Suche</title>" ?>
</head>

<body>

    <?php
    if (isset($_GET["thema"]) and isset($_GET["region"])) {
        $thema = $_GET["thema"];
        $region = $_GET["region"];
    } else {
        $thema = $_REQUEST["thema"];
        $region = $_REQUEST["region"];
    }
    print '<h1>Um die Suche zum Thema ' . $thema . ' im Bereich ' . $region . ' zu verbessern, benötigen wir noch einige Angaben von Ihnen.</h1><br><br>\n';

    $einsteiger_start = $_REQUEST["einsteiger_start"];
    $fortgeschritten_start = $_REQUEST["fortgeschritten_start"];
    $spezialist_start = $_REQUEST["spezialist_start"];
    $einsteiger_end = $_REQUEST["einsteiger_end"];
    $fortgeschritten_end = $_REQUEST["fortgeschritten_end"];
    $spezialist_end = $_REQUEST["spezialist_end"];

    ?>
    <Table class="detailstable">
        <tr>
            <td colspan="3">
                <?php print "<h2>Wie würden Sie Ihre Kenntnisse zum Thema " . $_GET['thema'] . " im Bereich " . $_GET["region"] . " selbst einschätzen: <h2>"; ?>
            </td>
            <td colspan="3">
                <?php print "<h2>Wo möchten Sie sich nach erfolgreichem Abschluss der Weiterbildung im Bereich " . $_GET["region"] . " sehen: <h2>"; ?>
            </td>
            <td>
                <?php print '<Form action="./Details.php">';
                print '<input type=hidden name="thema" value="' . $thema . '"/>';
                print '<input type=hidden name="region" value="' . $region . '"/>';
                print '<input type=hidden name="einsteiger_end" value="' . $einsteiger_end . '"/>';
                print '<input type=hidden name="fortgeschritten_end" value="' . $fortgeschritten_end . '"/>';
                print '<input type=hidden name="spezialist_end" value="' . $spezialist_end . '"/>';
                ?>
                <input type=hidden name="einsteiger_start" value="1" />
                <input type="hidden" name="fortgeschritten_start" value="0" />
                <input type="hidden" name="spezialist_start" value="0" />
                <?php
                if ($einsteiger_start == "1") {
                    print '<input type=submit value="Einsteiger" class="kompetenzkachel_selected"/>';
                } else {
                    print '<input type=submit value="Einsteiger" class="kompetenzkachel"/>';
                }
                ?>
                </Form>
            </td>
            <td>
                <?php print '<Form action="./Details.php">';

                print '<input type=hidden name="thema" value="' . $thema . '"/>';
                print '<input type=hidden name="region" value="' . $region . '"/>';
                print '<input type=hidden name="einsteiger_end" value="' . $einsteiger_end . '"/>';
                print '<input type=hidden name="fortgeschritten_end" value="' . $fortgeschritten_end . '"/>';
                print '<input type=hidden name="spezialist_end" value="' . $spezialist_end . '"/>';
                ?>
                <input type=hidden name="einsteiger_start" value="0" />
                <input type="hidden" name="fortgeschritten_start" value="1" />
                <input type="hidden" name="spezialist_start" value="0" />
                <?php
                if ($fortgeschritten_start == "1") {
                    print '<input type=submit value="Fortgeschritten" class="kompetenzkachel_selected"/>';
                } else {
                    print '<input type=submit value="Fortgeschritten" class="kompetenzkachel"/>';
                }
                ?>
                </Form>
            </td>
            <td>
                <?php print '<Form action="./Details.php">';

                print '<input type=hidden name="thema" value="' . $thema . '"/>';
                print '<input type=hidden name="region" value="' . $region . '"/>';
                print '<input type=hidden name="einsteiger_end" value="' . $einsteiger_end . '"/>';
                print '<input type=hidden name="fortgeschritten_end" value="' . $fortgeschritten_end . '"/>';
                print '<input type=hidden name="spezialist_end" value="' . $spezialist_end . '"/>';
                ?>
                <input type=hidden name="einsteiger_start" value="0" />
                <input type="hidden" name="fortgeschritten_start" value="0" />
                <input type="hidden" name="spezialist_start" value="1" />
                <?php
                if ($spezialist_start == "1") {
                    print '<input type=submit value="Spezialist" class="kompetenzkachel_selected"/>';
                } else {
                    print '<input type=submit value="Spezialist" class="kompetenzkachel"/>';
                }
                ?>
                </Form>
            </td>
            <td>
                <?php print '<Form action="./Details.php">';

                print '<input type=hidden name="thema" value="' . $thema . '"/>';
                print '<input type=hidden name="region" value="' . $region . '"/>';
                print '<input type=hidden name="einsteiger_start" value="' . $einsteiger_start . '"/>';
                print '<input type=hidden name="fortgeschritten_start" value="' . $fortgeschritten_start . '"/>';
                print '<input type=hidden name="spezialist_start" value="' . $spezialist_start . '"/>';
                ?>
                <input type=hidden name="einsteiger_end" value="1" />
                <input type="hidden" name="fortgeschritten_end" value="0" />
                <input type="hidden" name="spezialist_end" value="0" />
                <?php
                if ($einsteiger_end == "1") {
                    print '<input type=submit value="Einsteiger" class="kompetenzkachel_selected"/>';
                } else {
                    print '<input type=submit value="Einsteiger" class="kompetenzkachel"/>';
                }
                ?>
                </Form>
            </td>
            <td>
                <?php print '<Form action="./Details.php">';

                print '<input type=hidden name="thema" value="' . $thema . '"/>';
                print '<input type=hidden name="region" value="' . $region . '"/>';
                print '<input type=hidden name="einsteiger_start" value="' . $einsteiger_start . '"/>';
                print '<input type=hidden name="fortgeschritten_start" value="' . $fortgeschritten_start . '"/>';
                print '<input type=hidden name="spezialist_start" value="' . $spezialist_start . '"/>';
                ?>
                <input type=hidden name="einsteiger_end" value="0" />
                <input type="hidden" name="fortgeschritten_end" value="1" />
                <input type="hidden" name="spezialist_end" value="0" />
                <?php
                if ($fortgeschritten_end == "1") {
                    print '<input type=submit value="Fortgeschritten" class="kompetenzkachel_selected"/>';
                } else {
                    print '<input type=submit value="Fortgeschritten" class="kompetenzkachel"/>';
                }
                ?>
                </Form>
            </td>
            <td>
                <?php print '<Form action="./Details.php">';

                print '<input type=hidden name="thema" value="' . $thema . '"/>';
                print '<input type=hidden name="region" value="' . $region . '"/>';
                print '<input type=hidden name="einsteiger_start" value="' . $einsteiger_start . '"/>';
                print '<input type=hidden name="fortgeschritten_start" value="' . $fortgeschritten_start . '"/>';
                print '<input type=hidden name="spezialist_start" value="' . $spezialist_start . '"/>';
                ?>
                <input type=hidden name="einsteiger_end" value="0" />
                <input type="hidden" name="fortgeschritten_end" value="0" />
                <input type="hidden" name="spezialist_end" value="1" />
                <?php
                if ($spezialist_end == "1") {
                    print '<input type=submit value="Spezialist" class="kompetenzkachel_selected"/>';
                } else {
                    print '<input type=submit value="Spezialist" class="kompetenzkachel"/>';
                }
                ?>
                </Form>
            </td>
        </tr>
    </Table>
    <?php
      $uebergabe =
      "?thema=" . $thema .
      "&region=" . $region .
      "&einsteiger_start=" . $einsteiger_start .
      "&fortgeschritten_start=" . $fortgeschritten_start .
      "&spezialist_start=" . $spezialist_start .
      "&einsteiger_end=" . $einsteiger_end .
      "&fortgeschritten_end=" . $fortgeschritten_end .
      "&spezialist_end=" . $spezialist_end;
      ?>
     <h2><br><br>Ich benötige Förderung: <br> </h2>
    <h2><br><br>Ich benötige Beratung: <br> </h2>
    <?php print '<a href="endsearch.php' . $uebergabe . '"><br> ';?>
        <h1>>>Weiter zur Textsuche</h1>
    </a>
    <?php echo "               "; ?>
    <?php print '<a href="result.php' . $uebergabe . '"><br> ';?>
        <h1>>>Weiter zu unseren Empfehlungen</h1>
    </a>
</body>

</html>