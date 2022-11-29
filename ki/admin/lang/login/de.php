<?php
//
// This file is created and parsed by sysloc.php!
// - Place exactly one statement per line, no additional linewraps.
// - Do not use any operators nor any function other than define().
// - Do not use other remarks than // at the beginning of a line.
//
define('_LOGIN_ERR',              "Die Kombination aus Loginname und Passwort ist unbekannt. Bitte versuchen Sie es erneut. Beachten Sie sowohl beim Loginnamen als auch beim Passwort die Gro&szlig;-/Kleinschreibung und &uuml;berpr&uuml;fen Sie die Stellung der Feststelltaste.");
define('_LOGIN_FEATUREMISSING',   "Um auf das Redaktionssystem zuzugereifen, aktivieren Sie bitte die folgenden Features: \$1");
// $1: missing feature
define('_LOGIN_HELP',             "Um sich im System anzumelden, geben Sie im Feld <b>Loginname</b> Ihren Namen, Ihr K�rzel o.�. ein, den Sie vom Systemadministrator f�r diesen Zweck erhalten haben.\n\nIm Feld <b>Passwort</b> geben Sie Ihr pers�nliches Passwort oder Kennwort ein. Wenn Sie sich das erste Mal anmelden, haben Sie dieses ebenfalls vom Systemadministrator erhalten; Sie k�nnen Ihr Passwort nach erfolgreicher Anmeldung frei w�hlen.\n\nAchtung: Die Gro�- und Kleinschreibung ist in beiden F�llen relevant.\n\nWenn Sie dann auf <b>OK</b> klicken, pr�ft das Programm Ihre Angaben und erlaubt Ihnen bei Erfolg den Zugriff auf die vorgesehenen Daten. Anderfalls erhalten Sie eine Fehlermeldung und k�nnen es erneut probieren.\n\nSollten Sie immer noch Probleme haben, wenden Sie sich an den Systemadministrator, der Ihnen Ihren Loginnamen und Ihr Passwort gegeben hat.");
define('_LOGIN_INSECURE',         "Normale Verbindung");
define('_LOGIN_PASSWORDCHANGED',  "Das Passwort wurde ge&auml;ndert. Melden Sie sich bitte mit dem neuen Passwort an.");
define('_LOGIN_SECURE',           "Sichere Verbindung");
define('_LOGIN_TITLE',            "Login");
define('_LOGIN_WARNING',          "Eine der folgenden Situationen wurde festgestellt:\n\n - Sie haben vergessen sich nach Ihrer letzten Sitzung abzumelden\n - Sie sind bereits angemeldet\n - Es wurde vergebens versucht sich unter Ihrem Namen anzumelden (\$1)\n\nDenken Sie immer daran, sich nach einer Sitzung abzumelden, damit Ihr &quot;virtuelles T&uuml;rchen&quot; geschlossen werden kann. Verwenden Sie zum Abmelden das kleine Symbol oben rechts im Men&uuml;.");
define('_LOGIN_WELCOME',          "Willkommen \$1! Sie waren zuletzt angemeldet \$2.");
// $1: user name, $2: last login date