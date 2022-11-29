<?php
//
// Place exactly one statement per line, no additional linewraps.
// Do not use any operators for string creation.
// Do not use other remarks than // at the beginning of a line.
//
// In this file the tips'n'tricks for the program are defined. Use _TIP_0000 up to _TIP_9999 for the tips. Gaps are allowed. System-specific tips should be defined in the file 'config/lang/tipsntricks', in this file you can also overwrite the tips defined here.
//
help_define('_TIP_0000',        "Das Programm bietet Ihnen an, beim jeder Anmeldung einen '''Tip oder einen Trick''' zu zeigen. Falls Sie die Tips nicht w�nschen, k�nnen Sie diese oben rechts unter \"Einstellungen / Ansicht / Tips und Tricks\" ausschalten.");
help_define('_TIP_0010',        "Sie gelangen zur Datensatz�bersicht einer Tabelle durch Klick auf eine Registerkarte am oberen Bildschrirmrand. Seltener verwendete Tabellen sind unter \"Etc.\" zusammengefa�t. \n\nUm alle Datens�tze, auf die Sie Zugriff haben zu sehen, klicken  Sie in der Tabellen�bersicht oben auf \"Alle\".");
help_define('_TIP_0020',        "Sie gelangen zur Detailansicht eines Datensatzes indem Sie in der Datensatz�bersicht auf den entsprechenden Datensatz klicken. In der Detailansicht sehen Sie alle Felder auf die Sie Zugriff haben bzw. k�nnen diese auch �ndern.");
help_define('_TIP_0030',        "Sie haben zwei M�glichkeiten einen Datensatz in der Detailansicht zu speichern:\n\n- Klicken Sie unten auf \"OK\" wird der Datensatz gespeichert und Sie gelangen zur�ck in die Datensatz�bersicht.\n- Klicken Sie unten auf \"�bernehmen\", wird der Datensatz gespeichert und Sie bleiben in der Detailansicht.\n\nAch so: \"Abbruch\" bringt Sie zur�ck zur Datensatz�bersicht ''ohne'' den Datensatz zu speichern.");
help_define('_TIP_0040',        "Durch Klick auf die meisten Spaltennamen in der Datensatz�bersicht einer Tabelle k�nnen Sie die Sortierung einstellen - Sie erkennen die momentane Sortierung an einem Pfeil neben dem Spaltennamen.\n\nDurch einen weiteren Klick auf den Spaltennamen schalten Sie zwischen auf- und absteigender Sortierung hin und her.");
help_define('_TIP_0050',        "Sie k�nnen die in der Datensatz�bersicht anzuzeigenden Spalten oben rechtes unter \"Einstellungen / Spalten\" festlegen.");
help_define('_TIP_0060',        "In der Datensatz�bersicht einer Tabellen k�nnen Sie sich nur Datens�tze anzeigen lassen, die bestimmten Kriterien gen�gen.\n\nW�hlen Sie hierzu einen Feldnamen aus dem Popup oben links aus und geben Sie den gew�nschten Wert in das danebenliegene Eingabefeld ein.\n\nEin Klick auf \"Suchen\" zeigt Ihnen dann die entsprechenden Datens�tze an. Wenn Sie wieder alle Datens�tze, auf die Sie Zugriff haben, sehen m�chten klicken Sie oben auf \"Alle\".");
help_define('_TIP_0070',        "Weiterf�hrende Informationen erhalten Sie �ber die kontext-sensitive Funktion \"Hilfe\" oben rechts.\n\nDort finden Sie unter \"Index\" auch eine �bersicht aller verf�gbaren Hilfethemen.");
help_define('_TIP_0080',        "Sie k�nnen die Suche nach Datens�tzen verfeinern indem Sie in der Datensatz�bersicht auf \"Suche erweitern\" klicken.\n\nDas Programm bietet Ihnen dann eine Liste an, die Sie mit Ihren Suchkriterien f�llen k�nnen und mit UND oder ODER verkn�pfen k�nnen.\n\nBen�tigen Sie weitere Eingabefelder, klicken Sie einfach erneut auf \"Suche erweitern\".");
help_define('_TIP_0090',        "�ber das Symbol \"Einstellungen\" oben rechts k�nnen Sie zahlreiche Einstellungen vornehmen. Die Einstellungen sind kontext-sesitiv, d. h. es werden Ihnen immer die Optionen angeboten, die in der akutellen Ansicht sinnvoll sind:\n\nSo k�nnen Sie in der Datensatz�bersicht z. B. die anzuzeigenden Spalten festlegen und in der Detailansicht die Gr��e der Eingabefelder bestimmen.\n\nDie Einstellungen werden permanent gespeichert und stehen Ihnen beim n�chsten Login sogar auf einem anderen Rechner wieder zur Verf�gung.");
help_define('_TIP_0100',        "Wenn Sie in der Datensatz�bersicht �ber einen Datensatz mit der Maus fahren wird die Zeile normalerweise optisch hervorgehoben. Sie k�nnen Sie dieses Verhalten mit der Option \"Einstellungen / Ansicht / Datensatz unter Maus hervorheben\" �ndern.");
help_define('_TIP_0110',        "Sie k�nnen einen Datensatz nur dann l�schen, wenn er nicht mehr von anderen referenziert wird, d. h. wenn keine Verweise von anderen Datens�tzen auf den zu l�schenden Datensatz existieren.\n\nUm einen Datensatz zu l�schen m�ssen also zun�chst alle Referenzen entfernt werden.\n\nEtwaige Referenzen werden k�nnen Sie sich in der Detailansicht unter \"Referenzen\" anzeigen lassen.");
help_define('_TIP_0130',        "In der Detailansicht bringen Sie die Funktionen \"<< Zur�ck\" und \"Vor >>\" zum vorherigen/n�chsten Datensatz aus dem aktuellen Ausschnitt der Datensatz�bersicht.\n\nD. h. wenn in der Datensatz�bersicht z. B. 10 von 100 Datens�tzte sichtbar sind, k�nnen Sie mit \"<< Zur�ck\" und \"Vor >>\" in den 10 Datens�tzen bl�ttern.");
help_define('_TIP_0150',        "Ein Datensatz wird i. d. R. �ber eine zweite Reihe von Karteikartenreitern logisch unterteilt.\n\nSie k�nnen Sie dieses Verhalten mit der Option \"Einstellungen / Ansicht / Datens�tze mit Karteikarten unterteilen\" �ndern: Schalten Sie die Option aus, so werden die Bereiche einfach untereinander dargestellt.");
help_define('_TIP_0160',        "Das System verf�gt �ber ein ausgekl�geltes Rechtemanagement - Sie bekommen nur die Datens�tze und Felder zu Gesicht, die f�r Sie vorgesehen sind oder die Sie selbst erstellt haben.\n\nHierzu unterscheidet das System f�r jeden Datensatz verschiedene Rechte f�r den ''Eigent�mer'' eines Datensatzes, der ''Benutzergruppe'', dem ein Datensatz zugeordnet ist und ''allen Anderen''\n\nSie k�nnen diese Rechte in der Detailansicht eines Datensatzes unter \"Rechte\" einsehen und u. U. auch �ndern.");
help_define('_TIP_0170',        "I. d. R. sind alle Benutzer - also auch Sie - einer oder mehrern ''Benutzergruppen'' zugeordnet.\n\nWelchen Benutzergruppen Sie zugeordnet sind sehen Sie unter \"Einstellungen / Filter / Benutzergruppen\". Hier k�nnen Sie auch die Datens�tze bestimmter Benutzergruppen ausblenden.");
help_define('_TIP_0180',        "Normalerweise werden Datens�tze, auf die Sie keinen Zugriff haben aus der Datensatz�bersicht einer Tabelle ausgeblendet.\n\nSie k�nnen sich die Existenz dieser Datens�tze auch anzeigen lassen, indem Sie unter \"Einstellungen / Filter / Bei fehlenden Rechten\" die Option \"Hinweis anzeigen\" w�hlen. Einsehen oder gar �ndern k�nnen Sie diese Datens�tze nat�rlich trotzdem nicht.");
help_define('_TIP_9999',        "Sie haben jetzt alle Tips gelesen und k�nnen diese nun unter \"Einstellungen / Ansicht / Tips und Tricks\" ausschalten. Andernfalls werden alle Tips von vorne angezeigt.");
help_define('_TIP_DIDYOUKNOW',  "'''Wu�ten Sie schon...'''");
help_define('_TIP_TIPSNTRICKS', "Tips'n'Tricks");
// This is the title of the tips'n'tricks window, used beside _TIP_DIDYOUKNOW.
?>