<?php if( !defined('IN_WISY') ) die('!IN_WISY');


class WISY_GLOSSAR_RENDERER_CLASS
{
    var $framework;
    var $unsecureOnly = false;
    var $db;
    
    function __construct(&$framework)
    {
        // constructor
        $this->framework =& $framework;
        $this->db = new DB_Admin;
    }
    
    function getWikipediaUrl($artikel)
    {
        if( substr($artikel, 0, 4) == 'b2b:' )
        {
            $artikel = substr($artikel, 4);
            return 'https:/'.'/b2b.kursportal.info/index.php?title='.urlencode($artikel).'';
        }
        else
        {
            return 'https:/'.'/de.m.wikipedia.org/w/index.php?title='.urlencode($artikel).''; // 28.10.2013 weiterleitung auf die Mobilversion - auch auf dem Desktop, s. Mail von Jürgen vom 26.10.2013
        }
    }
    
    // Die Funktion prueft ob der Glossarbeitrag einem Stichwort zugeordnet ist
    function getGlossarArt($glossar_id)
    {
        $ret = 0;
        $this->db->query("SELECT id FROM stichwoerter WHERE glossar = '".$glossar_id."'");
        if( $this->db->ResultNumRows){
            $ret = 1;
        }
        return $ret;
    }
    
    
    
    function render()
    {
        // wird in der index.php gesetzt
        global $wisyPortalUserGrp;
        $glossar_id = intval( $this->framework->getParam('id') );
        $glossarshowall = $this->framework->iniRead('glossarshowall', '');
        $glossarshowgrps = array_map("trim", explode(",", $this->framework->iniRead('glossarshowgrps', '')));
        $glossarshowids = array_map("trim", explode(",", $this->framework->iniRead('glossarshowids', '')));
        $glossar = $this->getGlossareintrag($glossar_id);
        
        // 404 wenn Usergruppe Portal != Usergruppe Glossar und Gloasser nicht an einem Stichwort und Portalparameter glossarshowall != 1
        if (trim($this->framework->iniRead('disable.glossar', false))
            || ($glossarshowall != 1 && $glossar['user_grp'] != $wisyPortalUserGrp && !in_array($glossar_id, $glossarshowids) && !in_array($glossar['user_grp'], $glossarshowgrps) && $this->getGlossarArt($glossar_id) == 0) ) {
                $this->framework->error404();
        }
        // Wenn es keine Erklaerung, aber eine Wikipedia-Seite gibt -> Weiterleitung auf die entspr. Wikipedia-Seite
        if( $glossar['erklaerung'] == '' && $glossar['wikipedia'] != '' )
        {
            header('Location: ' . $this->getWikipediaUrl($glossar['wikipedia']));
            exit();
        }
        
        // prologue
        headerDoCache();
        echo $this->framework->getPrologue(array(
            'id'               => $glossar_id,
            'title'            =>        $glossar['begriff'],
            'beschreibung'     => $glossar['erklaerung'],        // #socialmedia, #richtext
            'canonical'        =>        $this->framework->getUrl('g', array('id'=>$glossar_id)),
            'bodyClass'        =>        'wisyp_glossar',
        ), $glossar_id);
        
        echo $this->framework->getSearchField();
        
        $this->renderGlossareintrag($glossar_id, $glossar);
        
        echo $this->framework->getEpilogue();
        
        // $this->db->close(); // closes db connection too early - eventhough query should be done
    }
    
    function getGlossareintrag($glossar_id) {
        // SELECT um user_grp erweitert (wegen 404-Prüfung)
        $this->db->query("SELECT begriff, erklaerung, wikipedia, date_created, date_modified, user_grp FROM glossar WHERE status=1 AND id=".$glossar_id);
        if( !$this->db->next_record() )
            $this->framework->error404();
            
            $glossareintrag = array('begriff' => $this->db->fcs8('begriff'),
                'erklaerung' => $this->db->fcs8('erklaerung'),
                'wikipedia' => $this->db->fcs8('wikipedia'),
                'date_created' => $this->db->fcs8('date_created'),
                'date_modified' => $this->db->fcs8('date_modified'),
                'user_grp' => $this->db->fcs8('user_grp')); 
            
            $this->db->free();
            return $glossareintrag;
    }
    
    function renderGlossareintrag($glossar_id, $glossar)
    {
        $classes  = $this->framework->getAllowFeedbackClass();
        $classes .= ($classes? ' ' : '') . 'wisy_glossar';
        echo '<div id="wisy_resultarea" class="' .$classes. '">';
        
        // render head
        echo '<a id="top"></a>'; // make [[toplinks()]] work
        echo '<p class="noprint">';
        echo '<a class="wisyr_zurueck" href="javascript:history.back();">&laquo; Zur&uuml;ck</a>';
        echo $this->framework->getLinkList('help.link', ' &middot; ');
        echo '</p>';
        echo '<h1 class="wisyr_glossartitel">' . htmlspecialchars($this->framework->encode_windows_chars($glossar['begriff'])) . '</h1>';
        
        if( $readsp_embedurl = $this->framework->iniRead('readsp.embedurl', false) )
            echo '<div id="readspeaker_button1" class="rs_skip rsbtn rs_preserve"> <a rel="nofollow" class="rsbtn_play" accesskey="L" title="Um den Text anzuh&ouml;ren, verwenden Sie bitte ReadSpeaker webReader" href="'.$readsp_embedurl.'"><span class="rsbtn_left rsimg rspart"><span class="rsbtn_text"> <span>Vorlesen</span></span></span> <span class="rsbtn_right rsimg rsplay rspart"></span> </a> </div>';
            
            flush();
        
        // render entry
        echo '<section class="wisyr_glossar_infos clearfix">';
        
        if( $glossar['erklaerung'] != '' )
        {
            $wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework, array('selfGlossarId'=>$glossar_id));
            echo cs8($wiki2html->run($this->framework->encode_windows_chars($glossar['erklaerung'])));
        }
        
        // render wikipedia link
        
        if( $glossar['wikipedia'] != '' )
        {
            $isB2b = (substr($glossar['wikipedia'], 0, 4) == 'b2b:')? true : false;
            
            echo '<p>';
            echo 'Weitere Informationen zu diesem Thema finden Sie <a href="'.htmlspecialchars($this->getWikipediaUrl($glossar['wikipedia'])).'" target="_blank" rel="noopener noreferrer">';
            echo ' ' . ($isB2b? 'im Weiterbildungs-WIKI' : 'in der Wikipedia');
            echo '</a>';
            echo '</p>';
        }
        
        echo '</section><!-- /.wisyr_glossar_infos -->';
        
        $gerst = $this->framework->iniRead('glossarinfo.erstellt', 1);
        $gaend = $this->framework->iniRead('glossarinfo.geaendert', 1);
        
        echo '<footer class="wisy_glossar_footer">';
        echo '<div class="wisyr_glossar_meta">';
        if($gerst || $gaend) {
            echo 'Information: ';
            if($gerst)
                echo 'erstellt am ' . $this->framework->formatDatum($glossar['date_created']).', ';
            if($gaend)
                echo 'zuletzt ge&auml;ndert am ' . $this->framework->formatDatum($glossar['date_modified']);
        }
        // Copyright-Informationen werden nicht mehr angezeigt
        //$copyrightClass =& createWisyObject('WISY_COPYRIGHT_CLASS', $this->framework);
        //$copyrightClass->renderCopyright($this->db, 'glossar', $glossar_id);
        echo '</div><!-- /.wisyr_glossar_meta -->';
        echo '</footer><!-- /.wisy_glossar_footer -->';
        
        
        echo "\n</div><!-- /#wisy_resultarea -->";
    }
    
    
};