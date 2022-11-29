<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]>      <html class="no-js"> <!--<![endif]-->
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Komfortstufe_Beratung</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="winmod.css" />
    </head>
    <body>
    <div class="wrapper">
    <div class="content">
    <img class="logo" src="./Logo_KPSH.jpg" />

                <h1>Beratungs-Empfehlungen</h1>
        <?php
           require_once "./wbprofil.php";
           wbprofil($_REQUEST, "Beratungsmoeglichkeiten", "0");
        ?>
        <h2>Hier sind Deine Beratungsmoeglichkeiten</h2>
        <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="#">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->
        
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