<?php if( !defined('IN_WISY') ) die('!IN_WISY');

loadWisyClass('WISY_ANBIETER_RENDERER_CLASS');


class WISY_ANBIETER_RENDERER_CLASS_HESSEN extends WISY_ANBIETER_RENDERER_CLASS
{
        var $framework;
        var $unsecureOnly = false;

        public function render()
        {
            $anbieter_id = intval( $this->framework->getParam('id') );

                if(trim($this->framework->iniRead('disable.anbieter', false)))
                    $this->framework->error404();

                $db = new DB_Admin();

                // link to another anbieter?
                $db->query("SELECT attr_id FROM anbieter_verweis WHERE primary_id=$anbieter_id ORDER BY structure_pos");
                if( $db->next_record() ) {
                        $anbieter_id = intval($db->f('attr_id'));
                }

                // load anbieter
                $db->query("SELECT * FROM anbieter WHERE id=$anbieter_id");
                if( !$db->next_record() || $db->fcs8('freigeschaltet')!=1 ) {
                    $this->framework->error404(); // record does not exist/is not active, report a normal 404 error, not a "Soft 404", see  http://goo.gl/IKMnm -- fuer nicht-freigeschaltete Datensaetze, s. [here]
                }
                $din_nr                        = htmlentities(cs8($db->fs('din_nr')));
                $suchname                = $db->fcs8('suchname');
                $typ            = intval($db->f('typ'));
                $firmenportraet        = trim($db->fcs8('firmenportraet'));
                $date_created        = cs8($db->fs('date_created'));
                $date_modified        = cs8($db->fs('date_modified'));
                //$stichwoerter        = $this->framework->loadStichwoerter($db, 'anbieter', $anbieter_id);
                $vollst                        = cs8($db->fs('vollstaendigkeit'));
                $anbieter_settings = explodeSettings(cs8($db->fs('settings')));

                $logo = cs8($db->fs('logo'));
                $logo_rights = cs8($db->fs('logo_rechte'));
                $logo_position = cs8($db->fs('logo_position'));
                $ob = new G_BLOB_CLASS($logo);
                $logo_name                = $ob->name;
                $logo_w                        = $ob->w;
                $logo_h                        = $ob->h;

                // #404gesperrteseiten
                $freigeschaltet404 = array_map("trim", explode(",", $this->framework->iniRead('seo.set404_anbieter_freigeschaltet', "")));

                // check for existance, get title, #socialmedia
                $db->query("SELECT suchname, ort, firmenportraet, freigeschaltet FROM anbieter WHERE id=$anbieter_id");
                if( !$db->next_record() || in_array($db->f('freigeschaltet'), $freigeschaltet404) ) {
                    $this->framework->error404(); // record does not exist, reporta normal 404 error, not a "Soft 404", see  http://goo.gl/IKMnm -- für nicht-freigeschaltete Datensätze, s. [here]
                }
                $anbieter_suchname = cs8($db->fs('suchname'), "UTF-8");

                $anbieter_ort = cs8($db->fs('ort'), "UTF-8");
                $anbieter_portraet = cs8($db->fs('firmenportraet'), "UTF-8");

                // promoted?
                if( intval( $this->framework->getParam('promoted') ) > 0 )
                {
                    $promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
                    $promoter->logPromotedRecordClick(intval( $this->framework->getParam('promoted') ), $anbieter_id);
                }

                // page out
                headerDoCache();

                $bodyClass = 'wisyp_anbieter';
                if( $typ == 2 )
                {
                        $bodyClass .= ' wisyp_anbieter_beratungsstelle';
                }

                // #socialmedia
                echo $this->framework->getPrologue(array(
                    'id'        =>  $anbieter_id,
                    'title'                =>        $anbieter_suchname,
                    'ort'                =>        $anbieter_ort,
                    'beschreibung' => $anbieter_portraet,
                    'anbieter_id' => $anbieter_id,
                    'canonical'        =>        $this->framework->getUrl('a', array('id'=>$anbieter_id)),
                    'bodyClass'        =>        $bodyClass,
                ));

                echo $this->framework->getSearchField();

                $this->tagsuggestorObj =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);
                $tag_suchname = $this->tagsuggestorObj->keyword2tagName($suchname);
                $this->tag_suchname_id = $this->tagsuggestorObj->getTagId($tag_suchname);

                echo "\n\n" . '<div id="wisy_resultarea" class="'.$this->framework->getAllowFeedbackClass().'">';

                echo '<p class="noprint"><a class="wisyr_zurueck" href="javascript:history.back();">&laquo; Zur&uuml;ck</a></p>';

                echo '<div class="wisyr_anbieter_kopf '.($logo_name? "" : "nologo").'">';
                echo "\n\n" . '<h1 class="wisyr_anbietertitel">';
                        if( $typ == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratungsstelle<span class="dp">:</span></span> ';
                        echo htmlentities($suchname);
                echo '</h1>';

                if( $readsp_embedurl = $this->framework->iniRead('readsp.embedurl', false) )
                    echo '<div id="readspeaker_button1" class="rs_skip rsbtn rs_preserve"> <a rel="nofollow" class="rsbtn_play" accesskey="L" title="Um den Text anzuh&ouml;ren, verwenden Sie bitte den webReader" href="'.$readsp_embedurl.'"><span class="rsbtn_left rsimg rspart"><span class="rsbtn_text"> <span>Vorlesen</span></span></span> <span class="rsbtn_right rsimg rsplay rspart"></span> </a> </div>';

                if( $logo_w && $logo_h && $logo_name != '')
                {
                    echo "\n" . '<div class="wisyr_anbieter_logo">';
                    if(!$logo_position) {
                        $this->fit_to_rect($logo_w, $logo_h, 128, 64, $logo_w, $logo_h);
                        echo "<div class=\"logo\"><img src=\"{$wisyPortal}admin/media.php/logo/anbieter/$anbieter_id/".urlencode($logo_name)."\" style=\"width: ".$logo_w."px; height: ".$logo_h."px;\" alt=\"Anbieter Logo\" title=\"\" id=\"anbieterlogo\"/></div>";
                        echo '<div id="logo_bildrechte" style="color: #aaa; font-size:.8em;">'.$logo_rights.'</div>';
                    }
                    echo '</div>';
                }
                echo '</div><!-- /#wisyr_anbieter_kopf -->';

                flush();

                echo "\n\n" . '<section class="wisyr_anbieterinfos clearfix">';
                echo "\n" . '<article class="wisyr_anbieter_firmenportraet wisy_anbieter_inhalt" data-tabtitle="Über">' . "\n";
                echo '<h1>&Uuml;ber den Anbieter</h1>';

                if($logo_position) {
                    echo "<img src=\"{$wisyPortal}admin/media.php/logo/anbieter/$anbieter_id/".urlencode($logo_name)."\" alt=\"Anbieter Logo: {$anbieter_suchname}\" title=\"{$anbieter_suchname}\" id=\"logo_big\">";
                    echo '<div id="logo_bildrechte_big">'.$logo_rights.'</div>';
                }

                // firmenportraet
                if( $firmenportraet != '' ) {
                        $wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
                        echo $wiki2html->run($firmenportraet);
                }

                echo "\n</article><!-- /.wisyr_anbieter_firmenportraet -->\n\n";
                echo "\n" . '<article class="wisyr_anbieter_steckbrief" data-tabtitle="Kontakt">' . "\n";

                echo "\n" . '<div class="wisy_steckbrief clearfix">';
                        echo '<div class="wisy_steckbriefcontent" itemscope itemtype="https://schema.org/Organization">';
                                echo $this->renderCard($db, $anbieter_id, 0, array(), true);
                        echo '</div>';
                echo "\n</div><!-- /.wisy_steckbrief -->\n";

                echo "\n</article><!-- /.wisyr_anbieter_steckbrief -->\n\n";

                echo "\n\n" . '<article class="wisy_anbieter_kursangebot '.($logo_name? "" : "nologo").'" data-tabtitle="Kurse"><h1>Angebot</h1>' . "\n";

                // link "show all offers"
                $freq = $this->tagsuggestorObj->getTagFreq(array($this->tag_suchname_id)); if( $freq <= 0 ) $freq = '';
                $searchlink = $this->framework->getUrl('search');

                require_once('admin/config/codes.inc.php');
                $tag_pseudoOffer = $this->framework->iniRead('angebote_einrichtungsort', TAG_EINRICHTUNGSORT);

                // ok?
                /* if($this->checkOffersSameTag($tag_suchname, $freq, $tag_pseudoOffer)) { // Einrichtungsort only
                 echo '<h1>&nbsp;</h1>' . "\n";
                 } else {
                 echo '<h1>Angebot</h1>' . "\n";
                 } */


                if( ($freq == "" && $typ == 2) || ($freq == 1 && $typ == 2) ) {
                    echo '<h2></h2>'
                        .        '<p>'
                            .                '&nbsp;&nbsp;Beratung'
                                .         '</p>';
                } elseif($this->checkOffersSameTag($tag_suchname, $freq, $tag_pseudoOffer)) { // Einrichtungsort only
                    echo '';
                }
                else {
                    echo '<h2>'.$freq.($freq==1? ' aktuelles Angebot' : ' aktuelle Angebote').'</h2>'
                        . '<p>'
                        . '<a class="wisyr_anbieter_kurselink" href="' . $searchlink . ((strpos($searchlink, '?') === FALSE) ? '?' : '&') .'qs=zeige:kurse&filter_anbieter=' .urlencode(str_replace(',', ' ', $tag_suchname)) . '">'
                        . 'Alle '. $freq . ' Angebote des Anbieters</a>';
                    // STTI 11-2021 Back-Link wenn von Kursdetail aufgerufen
                    if(isset($_SERVER['HTTP_REFERER'])) {
                      if (strstr($_SERVER['HTTP_REFERER'], $wisyPortal.'/k')) {
                         echo      '<a class="wisyr_anbieter_kurselink" href="' .$_SERVER['HTTP_REFERER']. '">Zur&uuml;ck zum Kurs</a>';
                      }
                    }
                    echo '</p>';
                }

                // current offers overview
                if( $this->framework->iniRead('anbieter.angebotsuebersicht', 1) )
                {
                $this->writeOffersOverview($anbieter_id, $tag_suchname);
                }

                echo "\n</article><!-- /.wisy_anbieter_kursangebot -->\n\n";

                echo "\n</section><!-- /.wisyr_anbieterinfos -->\n\n";


                echo "\n" . '<footer class="wisy_anbieter_footer">';
                $aerst = $this->framework->iniRead('anbieterinfo.erstellt', 1);
                $aaend = $this->framework->iniRead('anbieterinfo.geaendert', 1);
                $avollst = $this->framework->iniRead('anbieterinfo.vollstaendigkeit', 1);
                echo "\n" . '<div class="wisyr_anbieter_meta">';
                        if($aerst || $aaend || $avollst) {
                            echo ' Anbieterinformation: ';
                            if($aerst)
                                echo 'erstellt am ' . $this->framework->formatDatum($date_created).', ';
                            if($aaend)
                                echo 'zuletzt ge&auml;ndert am ' . $this->framework->formatDatum($date_modified).', ';
                            if($avollst) {
                                echo $vollst . '% Vollst&auml;ndigkeit';
                                echo '<div class="wisyr_vollst_info"><span class="info">Hinweise zur f&ouml;rmlichen Vollst&auml;ndigkeit der Informationen sagen nichts aus &uuml;ber die Qualit&auml;t der Angebote selbst. <a href="' . $this->framework->getHelpUrl(3369) . '">Mehr erfahren</a></span></div>';
                            }
                          }
                                $copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
                                $copyrightClass->renderCopyright($db, 'anbieter', $anbieter_id);
                        echo "\n</div><!-- /.wisyr_anbieter_meta -->\n\n";

                        echo "\n" . '<div class="wisyr_anbieter_edit">';
                                if( $this->framework->getEditAnbieterId() == $anbieter_id )
                                {
                                        echo '<br /><div class="wisy_edittoolbar">';
                                                if( $vollst >= 1 ) {
                                                        echo '<p>Hinweis f&uuml;r den Anbieter:</p><p>Die <b>Vollst&auml;ndigkeit</b> Ihrer '.$freq.' aktuellen Angebote liegt durchschnittlich bei <b>'.$vollst.'%</b> ';

                                                        $min_vollst = intval($anbieter_settings['vollstaendigkeit.min']);
                                                        $max_vollst = intval($anbieter_settings['vollstaendigkeit.max']);
                                                        if( $min_vollst >= 1 && $max_vollst >= 1 ) {
                                                                echo ' im <b>Bereich von ' . $min_vollst .'-'.$max_vollst . '%</b>';
                                                        }
                                                        echo '.';
                                                }
                                                echo ' Um die Vollst&auml;ndigkeit zu erh&ouml;hen klicken Sie oben links auf &quot;alle Kurse&quot; und bearbeiten
                                                 Sie die Angebote, v.a. die mit den schlechteren Vollst&auml;ndigkeiten.</p>';
                                                echo '<p>Die Vollst&auml;ndigkeiten werden ca. einmal t&auml;glich berechnet; ab 50% Vollst&auml;ndigkeit werden entspr. Logos an dieser Stelle eingeblendet.</p>';
                                        echo '</div>';
                                }
                        echo "\n</div><!-- /.wisyr_anbieter_edit -->\n\n";
                echo "\n</footer><!-- /.wisy_anbieter_footer -->\n\n";

                echo "\n</div><!-- /#wisy_resultarea -->";

                // $db->close();

                echo $this->framework->getEpilogue();
        }



};

registerWisyClass('WISY_ANBIETER_RENDERER_CLASS_HESSEN');