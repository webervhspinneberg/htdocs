<?php
error_reporting(0);
function wbprofil($param, $reason, $level2)
{
    if ($param['suchcat'] == "1") {
        print '<div id="bb">Ich bin ein*e Weiterbildungssuchende*r und suche ' . $reason . ' ' . "\n";
        searchtarget($param);
        competence($param, "wb_suchende");
        knowhow($param, "wb_suchende", $level2);
    } elseif ($param['suchcat'] == "2") {
        print '<div id="bb">Ich bin eine Beratungsperson und suche fuer meine Klient*en/innen ' . $reason . ' ' . "\n";
        searchtarget($param);
        competence($param, "beratungsperson");
        knowhow($param, "beratungsperson", $level2);
    } else {
        print '<div id="bb">Ich suche für mein Unternehmen ' . $reason . ' ' . "\n";
        searchtarget($param);
        competence($param, "unternehmen");
        knowhow($param, "unternehmen", $level2);
    }
}

function searchtarget($param)
{
    if (!empty($param['Kurssuche'])) {
        print "zu " . "\"" . $param['Kurssuche'] . "\". ";
    } elseif (!empty($param['thema'])) {
        print "zum Thema " . "\"" . $param['thema'] . "\".";
    }
    if (!empty($param['region'])) {
        print "im Bereich " . "\"" . $param['region'] . "\". ";
    }
}

function competence($param, $who)
{
    if (!empty($param['kompetenz_start'])) {
        print "<br>";
        if ($param['kompetenz_start'] == "1") {
            if ($who == "wb_suchende") {
                print "Ich bin Einsteiger*in.";
            } elseif ($who == "beratungsperson") {
                print "Meine Klient*en/innen sind Einsteiger*innen.";
            } elseif ($who == "unternehmen") {
                print "Meine Mitarbeiter*innen sind Einsteiger*innen.";
            }
        } elseif ($param['kompetenz_start'] == "2") {
            if ($who == "wb_suchende") {
                print "Ich bin fortgeschritten.";
            } elseif ($who == "beratungsperson") {
                print "Meine Klient*en/innen sind fortgeschritten.";
            } elseif ($who == "unternehmen") {
                print "Meine Mitarbeiter*innen sind fortgeschritten.";
            }
        } else {
            if ($who == "wb_suchende") {
                print "Ich bin Spezialist*in.";
            } elseif ($who == "beratungsperson") {
                print "Meine Klient*en/innen sind Spezialist*en/innen.";
            } elseif ($who == "unternehmen") {
                print "Meine Mitarbeiter*innen sind Spezialist*en/innen.";
            }
        }
    }
}
function knowhow($param, $who, $level2)
{
    if ($level2 == "1") {
        if (!empty($param['komp_ausbstud'])) {
            print "<br>\n";
            if ($who == "wb_suchende") {
                print "Ich habe durch Ausbildung/Studium die folgenden Ausbildungsziele erreicht: ";
            } elseif ($who == "beratungsperson") {
                print "Meine Klient*innen/en haben durch Ausbildung/Studium die folgenden Ausbildungsziele erreicht: ";
            } elseif ($who == "unternehmen") {
                print "Meine Mitarbeiter*innen haben durch Ausbildung/Studium die folgenden Ausbildungsziele erreicht: ";
            } else {
                print "Erreichte Ausbildungsziele: ";
            }
            if ($param['komp_ausbstud'] == "1") {
                print "Meister.";
            } elseif ($param['komp_ausbstud'] == "2") {
                print "Bachelor";
            } elseif ($param['komp_ausbstud'] == "3") {
                print "Master";
            }
        }
        if (!empty($param['komp_beruf'])) {
            print "<br>\n";
            if ($who == "wb_suchende") {
                print "Meine Praxiserfahrung in dem gesuchten Weiterbildungsbereich: ";
            } elseif ($who == "beratungsperson") {
                print "Die von Klient*innen/en in dem gesuchten Weiterbildungsbereich erreichte Praxiserfahrung ist: ";
            } elseif ($who == "unternehmen") {
                print "Meine Mitarbeiter*innen haben  in dem gesuchten Weiterbildungsbereich folgende Praxiserfahrung erreicht: ";
            } else {
                print "Praxiserfahrung: ";
            }
            if ($param['komp_beruf'] == "1") {
                print "0 - 2 Jahre Praxis.";
            } elseif ($param['komp_beruf'] == "2") {
                print "3 - 10 Jahre Praxis";
            } elseif ($param['komp_beruf'] == "3") {
                print "Mehr als 10 Jahre Praxis";
            }
        }
        if (!empty($param['komp_wb'])) {
            print "<br>\n";
            if ($who == "wb_suchende") {
                print "Ich habe durch Weiterbildung im gesuchten Weiterbildungsbereich folgendes Zertifikat erworben: ";
            } elseif ($who == "beratungsperson") {
                print "Meine Klient*innen/en haben durch Weiterbildung im gesuchten Weiterbildungsbereich folgendes Zertifikat erworben: ";
            } elseif ($who == "unternehmen") {
                print "Meine Mitarbeiter*innen haben durch Weiterbildung im gesuchten Weiterbildungsbereich folgendes Zertifikat erworben: ";
            } else {
                print "Weiterbildungszertifikat: ";
            }
            if ($param['komp_ausbstud'] == "1") {
                print "VHS-Zertifikat Stufe A";
            } elseif ($param['komp_ausbstud'] == "2") {
                print "VHS-Zertifikat Stufe B";
            } elseif ($param['komp_ausbstud'] == "3") {
                print "VHS-Zertifikat Stufe C";
            }
        }
    }
    print   "</div>\n";
}
?>