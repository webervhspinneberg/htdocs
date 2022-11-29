<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************
 WISY 2.0
 ******************************************************************************
 Globale Seite, hiervon existiert genau 1 Objekt
 ******************************************************************************/

require_once('admin/config/codes.inc.php');


// Funktionen, die ohne irgendwelche instanzen laufen sollten
function g_sync_removeSpecialChars($str)
{
	$str = strtr($str, ',:', '  ');
	while( strpos($str, '  ')!==false ) $str = str_replace('  ', ' ', $str);
	$str = trim($str);
	return $str;
}



// die globale Framework-Klasse
class WISY_FRAMEWORK_CLASS
{
	var $includeVersion;
	
	var $editCookieName;
	var $editSessionStarted;
	var $qtrigger;
	var $force;

	function __construct($baseObject, $addParam)
	{
		// constructor
		$this->includeVersion = '?iv=115'; // change the number on larger changes in included CSS and/or JS files.  May be empty.
		
		// init edit stuff
		$this->editCookieName		= 'wisyEdit20';
		$this->editSessionStarted	= false;
		
		// spalten initialisiern (aus historischen Gruenden so ...)
		$GLOBALS['wisyPortalSpalten'] = 0;
		$temp = $this->iniRead('spalten'); if( $temp == '' ) $temp = 'anbieter,termin,dauer,art,preis,ort,bemerkungen';
		$temp = str_replace(' ', '', $temp) . ',';
		if( strpos($temp, 'anbieter'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 1;
		if( strpos($temp, 'termin'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 2;
		if( strpos($temp, 'dauer'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 4;
		if( strpos($temp, 'art'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 8;
		if( strpos($temp, 'preis'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 16;
		if( strpos($temp, 'ort'				)!==false ) $GLOBALS['wisyPortalSpalten'] += 32;
		if( strpos($temp, 'kursnummer'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 64;
		if( strpos($temp, 'bemerkungen'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 128;
		// if( strpos($temp, 'bunummer'			)!==false ) $GLOBALS['wisyPortalSpalten'] += 256;
		
		$this->qtrigger = intval( $this->getParam('qtrigger', '') ); // anti-xss & sanity
		$this->force = intval( $this->getParam('force', '') ); // anti-xss & sanity
	}

	/******************************************************************************
	 Read/Write Settings, Cache & Co.
	 ******************************************************************************/

	function iniRead($key, $default='')
	{
		global $wisyPortalEinstellungen;
		$value = $default;
		if( isset( $wisyPortalEinstellungen[ $key ] ) )
		{
			$value = $wisyPortalEinstellungen[ $key ];
		}
		return $value;
	}

	function cacheRead($key, $default='')
	{
		global $wisyPortalEinstcache;
		$value = $default;
		if( isset( $wisyPortalEinstcache[ $key ] ) )
		{
			$value = $wisyPortalEinstcache[ $key ];
		}
		return $value;
	}
	
	function cacheWrite($key, $value)
	{
		global $wisyPortalEinstcache;
		global $s_cacheModified;
		if( $wisyPortalEinstcache[ $key ] != $value )
		{
			$wisyPortalEinstcache[ $key ] = $value;
			$s_cacheModified = true;
		}
	}
	
	function cacheFlush()
	{
		global $s_cacheModified;
		if( $s_cacheModified )
		{
			global $wisyPortalEinstcache;
			global $wisyPortalId;
			$this->cacheFlushInt($wisyPortalEinstcache, $wisyPortalId);
			$s_cacheModified = false;
		}
	}
	
	function cacheFlushInt(&$values, $portalId)
	{
		$ret = '';
		ksort($values);
		reset($values);
		foreach($values as $regKey => $regValue)
		{
			$regKey		= strval($regKey);
			$regValue	= strval($regValue);
			if( $regKey!='' ) 
			{
				$regValue = strtr($regValue, "\n\r\t", "   ");
				$ret .= "$regKey=$regValue\n";
			}
		}
	
		$db = new DB_Admin;
		$db->query("UPDATE portale SET einstcache='".addslashes($ret)."' WHERE id=$portalId;");
		$db->free();
	}
	

	/******************************************************************************
	 SEO
	 ******************************************************************************/
	
	/**
	 * #metadescription
	 *
	 * Gibt je Portal-Seitentyp aus uebergebenem $description-String formatierten Metadescription-String zur�ck
	 * Sonst: den Default-Portalbeschreibungstext aus den Portaleinstellungen.
	 * Max. 160 Zeichen
	 **/
	function getMetaDescription($title = "", $description = "") {
	    $ret = '';
	    
	    if(intval(trim($this->iniRead('meta.description'))) != 1)
	        return $ret;
	        
	        $description_parsed = "";
	        $skip_contentdescription = false;
	        
	        switch($this->getPageType()) {
	            case 'kurs':
	                $description_parsed = $this->generate_page_description($description, 160);
	                break;
	            case 'anbieter':
	                $description_parsed = $this->generate_page_description($description, 160);
	                break;
	            case 'suche':
	                // getTitleStrings Ort, Anbietername kann leer bleiben, weil Suche die params nicht braucht aber force muss sein, um enrichtitles_Einstellung zu ignorieren
	                // $this->getTitleString($title, null, null, true);
	                // Google-Linkbeschreibung als Tabellen-Ueberschrift misbrauchen = optisch sinnvoll:
	                // $description_parsed = "Ergebnis: Termin | Titel | Anbieter"; // wird nicht �bernommen, sollte unterschiedlich sein pro Seite
	                $skip_contentdescription = true;
	                break;
	            case 'glossar':
	                $description_parsed = $this->generate_page_description($description, 160);
	                break;
	            case 'startseite':
	                $description_parsed = trim($this->iniRead('meta.description_default', ""));
	                break;
	            default:
	                $description_parsed = trim($this->iniRead('meta.description_default', ""));
	        } // Ende: switch pageTypes
	        
	        if($skip_contentdescription) {
	            ;
	        } else {
	            $ret .= ($description_parsed == "") ? "\n".'<meta name="description" content="'.trim($this->iniRead('meta.description_default', "")).'">'."\n" : "\n".'<meta name="description" content="'.$description_parsed.'">'."\n";
	        }
	        
	        return $ret;
	}
	
	/**
	 * #metasocialmedia
	 * #metadescription
	 *
	 * Gibt aus $description generierten, metadescription-konformen Text von $charlength Zeichen zurueck.
	 * -> landet in Linkbeschreibung (=unter Links auf SERP) sowie Beschreibung bei geteilten Links in Social-Media-Portalen
	 **/
	function generate_page_description($description, $charlength) {
	    
	    // Wenn Wikitext in Seitentext auftaucht (Glossarseiten): erst mal parsen
	    // dann -> HTML + echte ISO8859-Umlaute (keine Entities), und Umbrueche durch Punkt ersetzen (ist ja Linkbeschreibung) -> sp�ter strip_tags
	    $wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this);
	    $description = html_entity_decode(preg_replace("/<br.{0,5}>/i", ". ", $wiki2html->run($description)));
	    
	    // Line breaks in Leerzeichen umwandeln -> Beschreibung einzeilig machen.
	    $description = preg_replace("/[\n\r]/", " ", strip_tags(html_entity_decode($description)));
	    
	    // Nur erlaubte Zeichen behalten:
	    // alphanumerische + Umlaute + wenige Sonderzeichen + Satzendzeichen. Hex-Nummern -> nach ISO8859
	    $description = preg_replace("/[^a-z\xA4\xBD\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xCB\xCC\xCD\xCE\xD6\xD8\xDC\xDF\xE0\xE1\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xEB\xF1\xF6\xF8\xFC0-9.:,\- ]+/i", "", $description);
	    
	    // Max 1 Leerzeichen hintereinander
	    $description = preg_replace('/\s+/', ' ', $description);
	    
	    return mb_substr(trim($description), 0, $charlength);
	}
	
	// #languagedefintion
	function getHreflangTags() {
	    $ret = '';
	    
	    if(intval(trim($this->iniRead('seo.enablelanguagedefinition'))) != 1)
	        return $ret;
	        
	        $defaultlang = trim($this->iniRead('seo.defaultlanguage', "de"));
	        
	        if(!$defaultlang)
	            return $ret;
	            
	            $ret .= '<link rel="alternate" hreflang="'.$defaultlang.'" href="//'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].'">';
	            
	            return $ret;
	}
	
	// #metasocialmedia
	// #metadescription
	// #enrichtitles
	function getPageType() {
	    
	    // Der Konstruktor ist jeweils sehr leichtgewichtig,
	    // darum koennen ruhig neue Objekte erzeugt werden.
	    // Andernfalls muesste man hier den getRenderercode mehr oder weniger duplizieren...
	    $result = $this->getRenderer();
	    
	    if(!is_object($result))
	        return false;
	        
	        if($_SERVER['REQUEST_URI'] == "/") {
	            return 'startseite';
	        }
	        
	        // Dieser sollte beim Ueberschreiben von Kernfunktionen immer gleich sein:
	        switch(str_replace(array("CUSTOM_", "DEV_", "ALPHA_", "BETA_"), "", get_class($result))) {
	            case 'WISY_SEARCH_RENDERER_CLASS':
	                return "suche";
	            case 'WISY_ADVANCED_RENDERER_CLASS':
	                return "advanced";
	            case 'WISY_KURS_RENDERER_CLASS':
	                return "kurs";
	            case 'WISY_ANBIETER_RENDERER_CLASS':
	                return "anbieter";
	            case 'WISY_GLOSSAR_RENDERER_CLASS':
	                return "glossar";
	            default:
	                return false;
	        }
	        
	}
	
	/******************************************************************************
	 Various Tools
	 ******************************************************************************/

	function error404($custom_title = "", $add_info = "", $skip_standardmsg = false)
	{
	    /* show an error page. For simple errors, eg. a bad or expired ID, we use an error message in the layout of the portal.
	     However, these messages only work in the root directory (remember, we use relative paths, so whn requesting /abc/def/ghi.html all images, css etc. won't work).
	     So, for errors outside the root directory, we use the global error404() function declared in /index.php */
	    $uri = $_SERVER['REQUEST_URI']; //
	    if( substr_count($uri, '/') == 1 )
	    {
	        global $wisyCore;
	        header("HTTP/1.1 404 Not Found");
	        
	        $title = $custom_title ? $custom_title : 'Fehler 404 - Seite nicht gefunden';
	        
	        echo $this->getPrologue(array('title'=>$title, 'bodyClass'=>'wisyp_error'));
	        echo $this->getSearchField();
	        
	        echo '
						<div class="wisy_topnote">
							<p><b>'.$title.'</b></p>';
	        
	        if(!$skip_standardmsg)		{
	            echo '<p>Entschuldigung, aber die von Ihnen gew&uuml;nschte Seite konnte leider nicht gefunden werden. Sie k&ouml;nnen jedoch ...
							<ul>
								<li><a href="http://'.$_SERVER['HTTP_HOST'].'">Die Startseite von '.$_SERVER['HTTP_HOST'].' aufrufen ...</a></li>
								<li><a href="javascript:history.back();">Zur&uuml;ck zur zuletzt besuchten Seite wechseln ...</a></li>
							</ul>';
	        }
	        
	        echo $add_info.'
							</p>
							<p><br><br><small>(Technischer Hinweis: die angeforderte Seite war <i>'.htmlspecialchars($uri).'</i> in <i>/'.htmlspecialchars($wisyCore).'</i> auf <i>' .$_SERVER['HTTP_HOST']. '</i>)</small></p>
						</div>';
	        
	        echo $this->getEpilogue();
	        exit();
	    }
	    else
	    {
	        error404();
	    }
	}

	function log($file, $msg)
	{
		// open the file
		$fullfilename = 'files/logs/' . strftime("%Y-%m-%d") . '-' . $file . '.txt';
		$fd = @fopen($fullfilename, 'a');
		
		if( $fd )
		{
			$line = strftime("%Y-%m-%d %H:%M:%S") . ": " . $msg . "\n";	
			@fwrite($fd, $line);
			@fclose($fd);
		}
	}

	function microtime_float()
	{
		// returns the number of seconds needed (as float)
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	function stopItem($title)
	{	
		$now = $this->microtime_float();
		$secneeded = isset($this->start_sec)? $now - $this->start_sec : 0.0;
		$this->start_sec = $now;
		echo "----------- stopItem: $title: ".sprintf("%1.3f", $secneeded)." -----------<br />";
	}

	function normalizeNatsortCallback($matches)
	{
		// add leading zeros to a number to allow a 'natural' ordering
		if( strlen($matches[0]) < 10 ) {
			return str_pad($matches[0], 10, '0', STR_PAD_LEFT);
		}
		else {
			return $matches[0];
		}
	}
	function normalizeNatsort($str)
	{
		// lower string
		$str = strtolower($str);
	
		// convert accented characters
		$str = strtr($str,	'��������������������������',
							'aaaaaaceeeeiiiinooooouuuyy');
	
		// convert german umlaute
		$str = strtr($str,	array('�'=>'ae', '�'=>'oe', '�'=>'ue', '�'=>'ss'));
	
		// convert numbers to a 'natural' sorting order
		$str = preg_replace_callback('/[0-9]+/', array($this, 'normalizeNatsortCallback'), $str);
	
		// strip special characters
		$str = strtr($str,	'\'\\!"�$%&/(){}[]=?+*~#,;.:-_<>|@�����  ',
							'                                        ');
	
		// remove spaces
		$str = str_replace(' ', '', $str);
	
		// done
		return $str;
	}

	function getParam($name, $default = false)
	{
		if( isset($_GET[$name]) )
		{
			$param = $_GET[$name];
			if( strtoupper($_GET['ie'])=='UTF-8' )
				$param = utf8_decode($param);
			return $param;
		}
		
		return $default;
	}

	function getUrl($page, $param = 0)
	{
	    // create any url; addparam is an array of additional parameters
	    // parameters are encoded using urlencode, however, the whole URL is _not_ HTML-save, you need to call htmlentities() to convert & to &amp;
	    
	    // if $param is no array, create an empty one
	    if( !is_array($param) )
	    {
	        $param = array();
	    }
	    
	    // base base page; page names with only one character are followed directly by the ID (true for k12345, a123, g456 etc.)
	    $ret = $page;
	    if( strlen($page) == 1 && isset($param['id']) )
	    {
	        $ret .= intval($param['id']);
	        unset($param['id']);
	    }
	    
	    // append all additional parameters, for the parameter q= we remove trailing spaces and commas
	    $i = 0;
	    reset($param);
	    foreach($param as $key => $value)
	    {
	        if( $key == 'q' )
	        {
	            if( $value == '' )
	                continue;
	                $value = rtrim($value, ', '); // remove trailing ", "
	        }
	        
	        $ret .= ($i? '&' : '?') . $key . '=' . urlencode($value);
	        $i++;
	    }
	    
	    if(strpos($ret, 'offset=') === FALSE) {
	        // human trigger of page
	        // code not beautiful:
	        if($i > 0)
	            $ret .= ($this->qtrigger ? '&qtrigger='.$this->qtrigger : '') . ($this->force ? '&force='.$this->force : '') . ($this->showcol ? '&showcol='.$this->showcol : '');
	            else
	                $ret .= ($this->qtrigger ? '?qtrigger='.$this->qtrigger : '') . ($this->force && !$this->qtrigger ? '?force='.$this->force : '')  . (!$this->force && !$this->qtrigger && $this->showcol ? '?showcol='.$this->showcol : '');
	    } else {
	        if( $this->qtrigger )
	            $ret = str_replace('offset=', 'qtrigger='.$this->qtrigger.'&offset=', $ret);
	        if( $this->force )
	            $ret = str_replace('offset=', 'force='.$this->force.'&offset=', $ret);
	        if($this->showcol)
	            $ret = str_replace('offset=', 'showcol='.$this->showcol.'&offset=', $ret);
	    }
	    
	    return $ret;
	}

	function getHelpUrl($id)
	{
		// calls getUrl() together with q= parameter -- you should not use this for 
		// retrieving the canoncial URL, use getUrl('g', array('id'=>$id)) instead!
		return $this->getUrl('g',
			array(
				'id'	=>	$id,
				'q'		=>	$this->getParam('q', ''),
			));
	}

	function replacePlaceholders_Callback($matches)
	{
		global $wisyPortalName;
		global $wisyPortalKurzname;

		$placeholder = $matches[0];
		if( $placeholder == '__NAME__' )
		{
			return $wisyPortalName;
		}
		else if( $placeholder == '__KURZNAME__' )
		{
			return $wisyPortalKurzname;
		}
		else if( $placeholder == '__ANZAHL_KURSE__' || $placeholder == '__ANZAHL_KURSE_G__' )
		{
			return intval($this->cacheRead('stats.anzahl_kurse'));
		}
		else if( $placeholder == '__ANZAHL_DURCHFUEHRUNGEN__' )
		{
			return intval($this->cacheRead('stats.anzahl_durchfuehrungen'));
		}
		else if( $placeholder == '__ANZAHL_ANBIETER__' || $placeholder == '__ANZAHL_ANBIETER_G__' )
		{
			return intval($this->cacheRead('stats.anzahl_anbieter'));
		}
		else if( $placeholder == '__ANZAHL_ABSCHLUESSE__' )
		{
			return intval($this->cacheRead('stats.anzahl_abschluesse')); // Anzahl von Kursen, die zu einem Abschluss fuehren
		}
		else if( $placeholder == '__ANZAHL_ZERTIFIKATE__' )
		{
			return intval($this->cacheRead('stats.anzahl_zertifikate')); // Anzahl verschiedener Abschluesse
		}
		else if( $placeholder == '__A_PRINT__' )
		{
			return ' href="javascript:window.print();"';
		}
		else if( $placeholder == '__DATUM__' )
		{
			$format = '%u, %d.%m.%Y';
			$weekdays = array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag');
			$format = str_replace('%u', $weekdays[ date('w') /*strftime() does not support %u on all system*/ ], $format);
			return strftime($format);
		}
		else if( substr($placeholder, 0, 6) == '__MENU' )
		{
			$prefix = strtolower(str_replace('_', '', $placeholder));
			$menuClass =& createWisyObject('WISY_MENU_CLASS', $this, array('prefix'=>$prefix));
			return $menuClass->getHtml();
		}
		else if( $placeholder == '__Q_HTMLENCODE__' )
		{
			return isohtmlspecialchars( rtrim( $this->getParam('q', ''), ', ') );
		}
		else if( $placeholder == '__Q_URLENCODE__' )
		{
			return urlencode( rtrim( $this->getParam('q', ''), ', ') );
		}
		else if( $placeholder == '__CONTENT__' )
		{
			return '__CONTENT__'; // just leave  this as it is, __CONTENT__ is handled separately
		}
		
		return "Unbekannt: $placeholder";
	}

	function replacePlaceholders($snippet)
	{
		return preg_replace_callback('/__[A-Z0-9_]+?__/', array($this, 'replacePlaceholders_Callback'), $snippet);
	}	

	


	/******************************************************************************
	 Edit Tools
	 ******************************************************************************/

	function startEditSession()
	{
		if( !$this->editSessionStarted )
		{
			ini_set('session.use_cookies', 1);
			session_name($this->editCookieName);
			session_start();
			if( intval($_SESSION['loggedInAnbieterId']) )
			{
				$this->editSessionStarted = true;
			}
		}
	}
	
	function getEditAnbieterId()
	{
	    return $this->editSessionStarted? intval($_SESSION['loggedInAnbieterId']) : -1;
	    // nicht "0" zurueckgeben, da es kurse gibt, die "0" als anbieter haben;
	    // ein Vergleich mit kursId==getEditAnbieterId() wuerde dann eine unerwartete uebereinstimmung bringen ...
	}
	

	/******************************************************************************
	 Formatting Tools
	 ******************************************************************************/


	function formatDatum($sqldatum)
	{
		// Datum formatieren
		if( $sqldatum == '' || $sqldatum == '0000-00-00 00:00:00' )
		{
			return '';
		}
		else
		{
			$sqldatum = explode(' ', strtr($sqldatum, '-:', '  '));
			return $sqldatum[2] . '.' . $sqldatum[1] . '.' . substr($sqldatum[0], 2, 2);
		}
	}

	function getSeals(&$db, $vars)
	{
		// get all seals
		$seals = array();
		$db->query("SELECT a.attr_id AS sealId, s.glossar AS glossarId FROM anbieter_stichwort a, stichwoerter s WHERE a.primary_id=" . intval($vars['anbieterId']) . " AND a.attr_id=s.id AND s.eigenschaften=" .DEF_STICHWORTTYP_QZERTIFIKAT. " ORDER BY a.structure_pos;");
		while( $db->next_record() )
			$seals[] = array($db->f('sealId'), $db->f('glossarId'));
	
		// no seals? -> done.
		if( sizeof((array) $seals) == 0 )
			return '';
	
		// get common seal information
		$seit = intval(substr($vars['seit'], 0, 4));
		if( $seit == 0 )
		{
			$db->query("SELECT pruefsiegel_seit FROM anbieter WHERE id=" . intval($vars['anbieterId']));
			$db->next_record();
			$seit = intval(substr($db->f('pruefsiegel_seit'), 0, 4));
		}
		$title = $seit? "Gepr&uuml;fte Weiterbildungseinrichtung seit $seit" : "Gepr&uuml;fte Weiterbildungseinrichtung";
	
		// render the seals
		if( !isset($vars['break']) ) $vars['break'] = '<br />&nbsp;<br />';
		
		$ret = '';
		$sealsOut = 0;
		for( $i = 0; $i < sizeof((array) $seals); $i++ )
		{
			$sealId    = $seals[$i][0];
			$glossarId = $seals[$i][1];
	
			if( $vars['size'] == 'small' )
			{
				$img = "files/seals/$sealId-small.gif";
				if( @file_exists($img) )
				{
					$ret .= '<a href="' . $this->getHelpUrl($glossarId) . '" class="help">';
						$ret .= "<img ".html3('align="right"')." src=\"$img\" ".html3('border="0"')." alt=\"Pr&uuml;siegel\" title=\"$title\" class=\"seal_small\"/>";
					$ret .= '</a>';
					$sealsOut++;
					break; // only one logo in small view
				}
			}
			else
			{
				$img = "files/seals/$sealId-large.gif";
				if( @file_exists($img) )
				{
					$ret .= $sealsOut? $vars['break'] : '';
					$ret .= "<img src=\"$img\" ".html3('border="0"')." alt=\"Pr&uuml;siegel\" title=\"$title\" class=\"seal\" />";
					$sealsOut++;
				}
			}
		}
	
		return $ret;
	}

	function glossarDb(&$db, $table, $id)
	{
		// get Glossary ID from a database entry
		$glossarId = 0;
		$field = $table=='stichwoerter'? 'stichwort' : 'thema';
		$db->query("SELECT glossar, $field FROM $table WHERE id=$id");
		if( $db->next_record() ) {
			if( !($glossarId=$db->f('glossar')) ) {
				/* $db->query("SELECT id FROM glossar WHERE begriff='" .addslashes($db->fs($field)). "'");
				if( $db->next_record() ) {
					$glossarId = $db->f('id');
				} */
			}
		}
		
		return $glossarId;
	}


	function loadStichwoerter(&$db, $table, $id)
	{
	    // Stichwoerter laden
	    $ret = array();
	    
	    require_once('admin/config/codes.inc.php'); // fuer hidden_stichwort_eigenschaften
	    global $hidden_stichwort_eigenschaften;
	    
	    $sql = "SELECT id, stichwort, eigenschaften, zusatzinfo FROM stichwoerter LEFT JOIN {$table}_stichwort ON id=attr_id WHERE primary_id=$id AND (eigenschaften & $hidden_stichwort_eigenschaften)=0 ORDER BY structure_pos;";
	    $db->query($sql);
	    while( $db->next_record() )
	    {
	        $ret[] = $db->Record;
	    }
	    
	    return $ret;
	}
	
	function loadSynonyme(&$db, $sw_id)
	{
	    $ret = array();
	    $sql = "SELECT DISTINCT id, stichwort, eigenschaften, zusatzinfo FROM stichwoerter, stichwoerter_verweis WHERE stichwoerter_verweis.primary_id = stichwoerter.id and stichwoerter_verweis.attr_id = ".$sw_id;
	    $db->query($sql);
	    while( $db->next_record() )
	    {
	        $ret[] = $db->Record;
	    }
	    
	    return $ret;
	}
	
	function loadDescendants(&$db, $sw_id)
	{
	    $ret = array();
	    $sql = "SELECT DISTINCT id, stichwort, eigenschaften, zusatzinfo FROM stichwoerter, stichwoerter_verweis2 WHERE stichwoerter_verweis2.attr_id = stichwoerter.id and stichwoerter_verweis2.primary_id = ".$sw_id;
	    $db->query($sql);
	    while( $db->next_record() )
	    {
	        $ret[] = $db->Record;
	    }
	    
	    return $ret;
	}
	
	function loadAncestors(&$db, $sw_id)
	{
	    $ret = array();
	    $sql = "SELECT DISTINCT id, stichwort, eigenschaften, zusatzinfo FROM stichwoerter, stichwoerter_verweis2 WHERE stichwoerter_verweis2.primary_id = stichwoerter.id and stichwoerter_verweis2.attr_id = ".$sw_id;
	    $db->query($sql);
	    while( $db->next_record() )
	    {
	        $ret[] = $db->Record;
	    }
	    
	    return $ret;
	}
	
	function writeDerivedStichwoerter($derivedStichwoerter, $filtersw, $typ_name, $originalsw) {
	    $ret = '';
	    for($i = 0; $i < count($derivedStichwoerter); $i++)
	    {
	        
	        $derivedStichwort = $derivedStichwoerter[$i];
	        if(!in_array($derivedStichwort['eigenschaften'], $filtersw))
	            $ret .= '<span class="typ_'.$derivedStichwort['eigenschaften'].'  orginal_'.$originalsw.' '.strtolower($typ_name).'_raw"><a href="/search?q='.$derivedStichwort['stichwort'].'">'.$derivedStichwort['stichwort'].'</a></span>, ';
	    }
	    return $ret;
	}
	
	function getTagFreq(&$db, $tag) {
	    $db->query("SELECT tag_freq FROM x_tags, x_tags_freq WHERE x_tags.tag_name = \"".$tag."\" AND x_tags.tag_id = x_tags_freq.tag_id");
	    if( $db->next_record() )
	        return $db->f8('tag_freq');
	}

	#richtext
	function writeStichwoerter($db, $table, $tags, $richtext = false)
	{
		// Stichwoerter ausgeben
		// load codes
		$ret = '';
		global $codes_stichwort_eigenschaften;
		global $hidden_stichwort_eigenschaften;
		require_once("admin/config/codes.inc.php");
		$codes_array = explode('###', $codes_stichwort_eigenschaften);
		
		// go through codes and stichwoerter
		for( $c = 0; $c < sizeof($codes_array); $c += 2 ) 
		{
			if( $codes_array[$c] == 0 )
				continue; // sachstichwoerter nicht darstellen - aenderung vom 30.03.2010 (bp)
			
			if( $codes_array[$c] & $hidden_stichwort_eigenschaften )
				continue; // explizit verborgene Stichworttypen nicht darstellen
				
			$anythingOfThisCode = 0;
			
			for( $s = 0; $s < sizeof((array) $stichwoerter); $s++ )
			{
				$glossarLink = '';
				$glossarId = $this->glossarDb($db, 'stichwoerter', $tags[$s]['id']);
				if( $glossarId ) {
					$glossarLink = ' <a href="' . $this->getHelpUrl($glossarId) . '" class="wisy_help" title="Ratgeber">i</a>';
				}
				
				if( ($tags[$s]['eigenschaften']==0 && intval($codes_array[$c])==0 && $glossarLink)
				 || ($tags[$s]['eigenschaften'] & intval($codes_array[$c])) )
				{
				    // #richtext
				    if(stripos($codes_array[$c+1], "Qualit") !== FALSE) {
				        $award_sw = ($richtext) ? preg_replace("/.Anbietermerkmal./i", "", $tags[$s]['stichwort']) : $tags[$s]['stichwort'];
				        $award1 = ($richtext) ? '<span itemprop="award" content="'.$award_sw.'">' : '';
				        $award2 = ($richtext) ? '</span>' : '';
				    }
				    
				    if( !$anythingOfThisCode ) {
						$ret .= '<tr class="wisy_stichwtyp'.$tags[$s]['eigenschaften'].'"><td'.html3(' valign="top"').'><span class="text_keyword">' . $codes_array[$c+1]
						. '<span class="dp">:</span></span>&nbsp;</td><td'.html3(' valign="top"').'>';
					}
					else {
						$ret .= '<br />';
					}
					
					$writeAend = false;
					/* 
					// lt. Liste "WISY-Baustellen" vom 5.9.2007, Punkt 8. in "Kursdetails", sollen hier kein Link angezeigt werden.
					// Zitat: "Anzeige der Stichworte ohne Link einblenden" (bp)
					$ret .= '<a title="alle Kurse mit diesem Stichwort anzeigen" href="' .wisy_param('index.php', array('sst'=>"\"{$tags[$s]['stichwort']}\"", 'skipdefaults'=>1, 'snew'=>2)). '">';
					$writeAend = true;
					*/
					
					// #richtext
					$ret .= $award1.$tags[$s]['stichwort'].$award2;
					
					if( $writeAend ) {
						$ret .= '</a>';
					}
					
					if( $tags[$s]['zusatzinfo'] != '' ) {
						$ret .= ' <span class="ac_tag_type">(' . isohtmlspecialchars($tags[$s]['zusatzinfo']) . ')</span>';
					}

					$ret .= $glossarLink;
					
					$anythingOfThisCode	= 1;
				}
			}
			
			if( $anythingOfThisCode ) {
				$ret .= '</td></tr>';
			}
		}
		
		return $ret;
	}

	function getVollstaendigkeitMsg(&$db, $recordId, $scope = '')
	{
		// Einstellungen der zug. Gruppe und Kursvollstaendigkeit laden
		// die Einstellungen koennen etwa wie folgt aussehen:
		/*
		quality.portal.warn.percent= 80
		quality.portal.warn.msg    = Informationen lueckenhaft (nur __PERCENT__% Vollstaendigkeit)
		quality.portal.bad.percent = 50
		quality.portal.bad.msg     = Informationen unzureichend (nur __PERCENT__% Vollstaendigkeit)
		quality.edit.warn.percent  = 80
		quality.edit.warn.msg      = Informationen lueckenhaft (nur __PERCENT__% Vollstaendigkeit)
		quality.edit.bad.percent   = 50
		quality.edit.bad.msg       = Informationen unzureichend (nur __PERCENT__% Vollstaendigkeit)
		quality.edit.bad.banner    = Informationen unzureichend (nur __PERCENT__% Vollstaendigkeit) - gelistet aus Gruenden der Marktuebersicht
		*/
	
		
		$sql = "SELECT settings s, vollstaendigkeit v FROM user_grp g, kurse k
				WHERE k.user_grp=g.id AND k.id=$recordId";
		$db->query($sql); if( !$db->next_record() ) return array();
	
		$settings			= explodeSettings($db->fs('s'));
		$vollstaendigkeit	= intval($db->f('v'));  if( $vollstaendigkeit <= 0 ) return;
		$ret				= array();
	
		if( $vollstaendigkeit <= intval($settings["$scope.bad.percent"]) )
		{
			$ret['msg'] = $settings["$scope.bad.msg"];
			$ret['banner'] = $settings["$scope.bad.banner"];
		}
		else if( $vollstaendigkeit <= intval($settings["$scope.warn.percent"]) )
		{
			$ret['msg'] = $settings["$scope.warn.msg"];
		}
		
		if( $ret['msg'] != '' ) { $ret['msg'] = str_replace('__PERCENT__', $vollstaendigkeit, $ret['msg']); }
		if( $ret['banner'] != '' ) { $ret['banner'] = str_replace('__PERCENT__', $vollstaendigkeit, $ret['banner']); }
		
		return $ret;
	}

	/******************************************************************************
	 Construct pages
	 ******************************************************************************/
	
	function getAllowFeedbackClass()
	{
		if( !$this->iniRead('feedback.disable', 0) 
		 && !$this->editSessionStarted /*keine Feedback-Funktion fuer angemeldete Anbieter - die Anbieter sind die Adressaten, nicht die Absender*/ )
		{
			return 'wisy_allow_feedback';
		}
		else
		{	
			return '';
		}
	}
	
	function getLinkList($iniPrefix, $sep)
	{
		$ret = '';
		$testI = 1;
		$menuClass = 0;
		while( 1 )
		{
			$value = $this->iniRead("$iniPrefix$testI", '');
			if( $value == '' )
			{
				break;
			}
			else
			{
				if( !is_object($menuClass) )
					$menuClass =& createWisyObject('WISY_MENU_CLASS', $this);
				$menuClass->explodeMenuParam($value, $title, $url, $aparam);
			
				if( $title == '' )
					$title = $url;
				
				$ret .= " $sep <a href=\"$url\"$aparam>$title</a>";
			}
			
			$testI++;
		}
		
		return $ret;
	}
	
	function getTitleString($pageTitleNoHtml)
	{
		// get the title as a no-html-string
		global $wisyPortalKurzname;
		$fullTitleNoHtml  = $pageTitleNoHtml;
		$fullTitleNoHtml .= $fullTitleNoHtml? ' - ' : '';
		$fullTitleNoHtml .= $wisyPortalKurzname;
		return $fullTitleNoHtml;
	}
	
	function getTitleTags($pageTitleNoHtml)
	{
		// get the <title> tag
		return "<title>" .isohtmlspecialchars($this->getTitleString($pageTitleNoHtml)). "</title>\n";
	}
	
	function getFaviconFile()
	{
		// get the favicon file
		return $this->iniRead('img.favicon', '');
	}
	
	function getFaviconTags()
	{
		// get the favicon tag(s) (if any)
		$ret = '';
		
		$favicon = $this->getFaviconFile();
		if( $favicon != '' ) 
		{
			$ret .= '<link rel="shortcut icon" type="image/ico" href="' .$favicon. '" />' . "\n"; 
		}
		
		return $ret;
	}

	function getOpensearchFile()
	{
		// get the OpenSearchDescription file
		return 'opensearch';
	}

	function getOpensearchTags()
	{
		// get the OpenSearchDescription Tags (if any)
		global $wisyPortalKurzname;
		$ret = '';
		
		$opensearchFile = $this->getOpensearchFile();
		if( $opensearchFile )
		{
			$ret .= '<link rel="search" type="application/opensearchdescription+xml" href="' . $opensearchFile . '" title="' .isohtmlspecialchars($wisyPortalKurzname). '" />' . "\n";
		}
		
		return $ret;
	}

	function getRSSFile()
	{
		// get the main RSS file
		$q = rtrim($this->getParam('q', ''), ', ');
		return 'rss?q=' . urlencode($q);
	}

	function getRSSTags()
	{
		// get the RSS tag (if there is no query, "alle Kurse" is returned)
		$ret = '';
	
		if( $this->iniRead('rsslink', 0) )
		{
			global $wisyPortalKurzname;
			$q = rtrim($this->getParam('q', ''), ', ');
			$title = $wisyPortalKurzname . ' - ' . ($q==''? 'aktuelle Kurse' : $q);
			$ret .= '<link rel="alternate" type="application/rss+xml" title="'.isohtmlspecialchars($title).'" href="' .$this->getRSSFile(). '" />' . "\n";
		}
		
		return $ret;
	}

	function getRSSLink()
	{
		$ret = '';
	
		if( $this->iniRead('rsslink', 0) )
		{
			$ret .= ' <a href="'.$this->getRSSFile().'" class="wisy_rss_link" title="Suchauftrag als RSS-Feed abonnieren">Updates abonnieren</a> ';
			
			$glossarId = intval($this->iniRead('rsslink.help', 2953));
			if( $glossarId )
			{
				$ret .= ' <a href="' .$this->getHelpUrl($glossarId). '" class="wisy_help" title="Hilfe">i</a>';
			}
		}
		
		return $ret;
	}


	function getCSSFiles()
	{
		// return all CSS as an array
		global $wisyPortalCSS;
		global $wisyPortalId;
		
		$ret = array();

		// core 2.0 styles
		$ret[] = 'core.css' . $this->includeVersion;
		
		// the portal may overwrite everything ...
		if( $wisyPortalCSS )
		{
			$ret[] = 'portal.css'. $this->includeVersion;
		}
		
		$ret[] = 'core20/lib/cookieconsent/cookieconsent.min.css';
		
		if( ($tempCSS=$this->iniRead('head.css', '')) != '')
		{
			$addCss = explode(",", $tempCSS);
			
			foreach($addCss AS $cssFile) {
				$ret[] = trim($cssFile);
			}
		}
		
		return $ret;
	}

	function getCSSTags()
	{
		// get CSS tags
		$ret = '';
		
		$css = $this->getCSSFiles();
		for( $i = 0; $i < sizeof((array) $css); $i++ )
		{	
			$ret .= '<link rel="stylesheet" type="text/css" href="'.$css[$i].'" />' . "\n";
		}
		
		return $ret;
	}
	
	function getJSFiles()
	{
	    // return all JavaScript files as an array
	    $ret = array();
	    
	    if($this->iniRead('search.suggest.v2') == 1)
	    {
	        $ret[] = '/admin/lib/jquery/js/jquery-1.10.2.min.js';
	        $ret[] = '/admin/lib/jquery/js/jquery-ui-1.10.4.custom.min.js';
	    }
	    else
	    {
	        // $ret[] = 'jquery-1.4.3.min.js';
	        global $wisyCore;
	        $ret[] = $wisyCore.'/lib/jquery/jquery-1.12.4.min.js';
	        //$ret[] = $wisyCore.'/lib/jquery/jquery-ui-1.12.1.custom.min.css';
	        
	        $ret[] = 'jquery.autocomplete.min.js';
	    }
	    $ret[] = 'jquery.wisy.js' . $this->includeVersion;
		
		if( ($tempJS=$this->iniRead('head.js', '')) != '')
		{
			$addJs = explode(",", $tempJS);
			
			foreach($addJs AS $jsFile) {
				$ret[] = trim($jsFile);
			}
		}
		
		return $ret;
	}
	
	function getJSHeadTags()
	{
	    // JavaScript tags to include to the header (if any)
	    $ret = '';
	    
	    $js = $this->getJSFiles();
	    for( $i = 0; $i < sizeof((array) $js); $i++ )
	    {
	        $ret .= '<script type="text/javascript" src="'.$js[$i].'"></script>' . "\n";
	    }
	    
	    global $wisyCore;
	    $ret .= '<script src="'.$wisyCore.'/lib/cookieconsent/cookieconsent.min.js'.'"></script>' . "\n";
	    
	    /* ! if($this->iniRead('cookiebanner', '') == 1) {
	     $ret .= '<script type="text/javascript">window.cookiebanner_html = \''.$this->iniRead('cookiebanner.html', '').'\'; window.cookiebanner_gueltig = '.$this->iniRead('cookiebanner.gueltig', '').';</script>' . "\n";
	     }  */
	    
	    
	    // Cookie Banner settings
	    if($this->iniRead('cookiebanner', '') == 1) {
	        
	        $ret .= "<script>\n";
	        $ret .= "window.cookiebanner = {};\n";
	        $ret .= "window.cookiebanner.optoutCookies = \"{$this->iniRead('cookiebanner.cookies.optout', '')},fav,fav_init_hint\";\n";
	        $ret .= "window.cookiebanner.optedOut = false;\n";
	        $ret .= "window.cookiebanner.favOptoutMessage = \"{$this->iniRead('cookiebanner.fav.optouthinweis', 'Ihr Favorit konnte auf diesem Computer nicht gespeichert gewerden da Sie die Speicherung von Cookies abgelehnt haben. Sie k&ouml;nnen Ihre Cookie-Einstellungen in den Datenschutzhinweisen anpassen.')}\";\n";
	        $ret .= "window.cookiebanner.piwik = \"{$this->iniRead('analytics.piwik', '')}\";\n";
	        $ret .= "window.cookiebanner.uacct = \"{$this->iniRead('analytics.uacct', '')}\";\n";
	        
	        $ret .= 'window.addEventListener("load",function(){window.cookieconsent.initialise({';
	        
	        $cookieOptions = array();
	        $cookieOptions['type'] = 'opt-out';
	        $cookieOptions['revokeBtn'] = '<div style="display:none;"></div>'; // Workaround for cookieconsent bug. Revoke cannot be disabled correctly at the moment
	        $cookieOptions['position'] = $this->iniRead('cookiebanner.position', 'top-left');
	        
	        $cookieOptions['law'] = array();
	        $cookieOptions['law']['countryCode'] = 'DE';
	        
	        $cookieOptions['cookie'] = array();
	        $cookieOptions['cookie']['expiryDays'] = intval($this->iniRead('cookiebanner.cookiegueltigkeit', 7));
	        
	        $cookieOptions['content'] = array();
	        $cookieOptions['content']['message'] = $this->iniRead('cookiebanner.hinweis.text', 'Wir verwenden Cookies, um Ihnen eine Merkliste sowie eine Seiten&uuml;bersetzung anzubieten und um Kursanbietern die Pflege ihrer Kurse zu erm&ouml;glichen. Indem Sie unsere Webseite nutzen, erkl&auml;ren Sie sich mit der Verwendung der Cookies einverstanden. Weitere Details finden Sie in unserer Datenschutzerkl&auml;rung.');
	        
	        $this->detailed_cookie_settings_einstellungen = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.einstellungen', ''))) > 3); // legacy compatibility
	        $cookieOptions['content']['zustimmung_einstellungen'] = $this->iniRead('cookiebanner.zustimmung.einstellungen', false);
	        
	        if(strlen($cookieOptions['content']['zustimmung_einstellungen']) > 3 && $this->iniRead('cookiebanner.zeige.speicherdauer', ''))
	            $cookieOptions['content']['zustimmung_einstellungen'] .= " (".$cookieOptions['cookie']['expiryDays']." Tage)";
	            
	        $this->detailed_cookie_settings_popuptext = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.popuptext', ''))) > 3); // legacy compatibility
	        $cookieOptions['content']['zustimmung_popuptext'] = $this->iniRead('cookiebanner.zustimmung.popuptext', false);
	            
	        $this->detailed_cookie_settings_merkliste = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.merkliste', ''))) > 3); // legacy compatibility
	        $cookieOptions['content']['zustimmung_merkliste'] = $this->iniRead('cookiebanner.zustimmung.merkliste', false);
	        
	        $this->detailed_cookie_settings_onlinepflege = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.onlinepflege', ''))) > 3); // legacy compatibility
	        $cookieOptions['content']['zustimmung_onlinepflege'] = $this->iniRead('cookiebanner.zustimmung.onlinepflege', false);
	        
	        $this->detailed_cookie_settings_translate = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.translate', ''))) > 3); // legacy compatibility
	        $cookieOptions['content']['zustimmung_translate'] = $this->iniRead('cookiebanner.zustimmung.translate', false);
	        
	        $this->detailed_cookie_settings_analytics = boolval(strlen(trim($this->iniRead('cookiebanner.zustimmung.analytics', ''))) > 3); // legacy compatibility
	        $cookieOptions['content']['zustimmung_analytics'] = $this->iniRead('cookiebanner.zustimmung.analytics', false);
	        
	        $toggle_details = "javascript:toggle_cookiedetails();";
	        
	        $cookieOptions['content']['message'] = str_ireplace('__ZUSTIMMUNGEN__',
	            '<ul class="cc-consent-details">'
	            .($cookieOptions['content']['zustimmung_einstellungen'] ? $this->addCConsentOption("einstellungen", $cookieOptions) : '')
	            .($cookieOptions['content']['zustimmung_popuptext'] ? $this->addCConsentOption("popuptext", $cookieOptions) : '')
	            .($cookieOptions['content']['zustimmung_onlinepflege'] ? $this->addCConsentOption("onlinepflege", $cookieOptions) : '')
	            .($cookieOptions['content']['zustimmung_merkliste'] ? $this->addCConsentOption("merkliste", $cookieOptions) : '')
	            .($cookieOptions['content']['zustimmung_translate'] ? $this->addCConsentOption("translate", $cookieOptions) : '')
	            .($cookieOptions['content']['zustimmung_analytics'] ? $this->addCConsentOption("analytics", $cookieOptions) : '')
	            .'__ZUSTIMMUNGEN_SONST__'
	            .'</ul>'."<br><a href='".$toggle_details."' class='toggle_cookiedetails inactive'>Cookie-Details</a><br>",
	            $cookieOptions['content']['message']
	            );
	        
	        global $wisyPortalEinstellungen;
	        reset($wisyPortalEinstellungen);
	        $allPrefix = 'cookiebanner.zustimmung.sonst';
	        $allPrefixLen = strlen($allPrefix);
	        foreach($wisyPortalEinstellungen as $key => $value)
	        {
	            if( substr($key, 0, $allPrefixLen)==$allPrefix )
	            {
	                $cookieOptions['content']['message'] = str_replace('__ZUSTIMMUNGEN_SONST__',
	                    $this->addCConsentOption("analytics", $key).'__ZUSTIMMUNGEN_SONST__',
	                    $cookieOptions['content']['message']);
	            }
	        }
	        $cookieOptions['content']['message'] = str_replace('__ZUSTIMMUNGEN_SONST__', '', $cookieOptions['content']['message']);
	        
	        
	        $cookieOptions['content']['message'] = str_ireplace('__HINWEIS_ABWAHL__',
	            '<span class="hinweis_abwahl">'
	            .$this->iniRead('cookiebanner.hinweis.abwahl', '(Option abw&auml;hlen, wenn nicht einverstanden)')
	            .'</span>',
	            $cookieOptions['content']['message']);
	        
	        $cookieOptions['content']['allow'] = $this->iniRead('cookiebanner.erlauben.text', 'OK', 1);
	        $cookieOptions['content']['deny'] = $this->iniRead('cookiebanner.ablehnen.text', 'Ablehnen', 1);
	        $cookieOptions['content']['link'] = $this->iniRead('cookiebanner.datenschutz.text', 'Mehr erfahren', 1);
	        $cookieOptions['content']['href'] = $this->iniRead('cookiebanner.datenschutz.link', '');
	        
	        $cookieOptions['palette'] = array();
	        $cookieOptions['palette']['popup'] = array();
	        $cookieOptions['palette']['popup']['background'] = $this->iniRead('cookiebanner.hinweis.hintergrundfarbe', '#EEE');
	        $cookieOptions['palette']['popup']['text'] = $this->iniRead('cookiebanner.hinweis.textfarbe', '#000');
	        $cookieOptions['palette']['popup']['link'] = $this->iniRead('cookiebanner.hinweis.linkfarbe', '#3E7AB8');
	        
	        $cookieOptions['palette']['button']['background'] = $this->iniRead('cookiebanner.erlauben.buttonfarbe', '#3E7AB8');
	        $cookieOptions['palette']['button']['text'] = $this->iniRead('cookiebanner.erlauben.buttontextfarbe', '#FFF');
	        
	        $cookieOptions['palette']['highlight']['background'] = $this->iniRead('cookiebanner.ablehnen.buttonfarbe', '#FFF');
	        $cookieOptions['palette']['highlight']['text'] = $this->iniRead('cookiebanner.ablehnen.buttontextfarbe', '#000');
	        
	        $ret .= trim(json_encode($cookieOptions, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), '{}') . ',';
	        
	        // Callbacks for enabling / disabling Cookies
	        $ret .= 'onInitialise: function(status) {
						var didConsent = this.hasConsented();
						if(!didConsent) {
							window.cookiebanner.optedOut = true;
							updateCookieSettings();
						}
						callCookieDependantFunctions();
					},
					onStatusChange: function(status) {
						var didConsent = this.hasConsented();
						if(!didConsent) {
							window.cookiebanner.optedOut = true;
							updateCookieSettings();
						}
						callCookieDependantFunctions();
					}';
	        
	        // Hide Revoke Button and enable custom revoke function in e.g. "Datenschutzhinweise"
	        // Add an <a> tag with ID #wisy_cookieconsent_settings anywhere on your site. It will re-open the cookieconsent popup when clicked
	        $ret .= '},
					function(popup){
						popup.toggleRevokeButton(false);
						window.cookieconsent.popup = popup;
						jQuery("#wisy_cookieconsent_settings").on("click", function() {
							window.cookieconsent.popup.open();
							window.cookiebanner.optedOut = false;
							updateCookieSettings();
							return false;
						});
					}';
	        
	        $ret .= ');
	            
			/* save detailed cookie consent status */
				jQuery(".cc-btn.cc-allow").click(function(){
					jQuery(".cc-consent-details input[type=checkbox]").each(function(){
						var cname = jQuery(this).attr("name");
						jQuery.removeCookie(cname, { path: "/" });';
						
						if(trim($cookieOptions['content']['zustimmung_einstellungen']) != "" && $this->iniRead("cookiebanner.zustimmung.analytics.essentiell", false) !== false)
							$ret .='if( jQuery(this).is(":checked") && jQuery(".cc-consent-details .einstellungen input[type=checkbox]").is(":checked") ) {
								setCookieSafely(cname, "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].'});';
						else
							$ret .='if( jQuery(this).is(":checked") ) {
								setCookieSafely(cname, "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].'});';
							
							if(!$this->iniRead("cookiebanner.zustimmung.analytics.autoload", 0)) {
								$ret .= '
										if(cname == "cconsent_analytics") {
											/* Calling analystics url by calling script in script-tag. Calling via ajax() would not execute script withou eval. */
												
											if( jQuery("#ga_script").length )
												eval(jQuery("#ga_script").text());
											
											if( jQuery("#matomo_script").length )
												embedMatomoTracking();
										
										}';
							}
							
							
				$ret .= '
						}'; // End: is:checked
						
				if(trim($cookieOptions['content']['zustimmung_einstellungen']) != "" && $this->iniRead("cookiebanner.zustimmung.analytics.essentiell", false) !== false)
				$ret .='if( jQuery(".cc-consent-details .einstellungen input[type=checkbox]").is(":checked") == false)
													setTimeout(function(){ jQuery.cookie("cookieconsent_status", null, { path: "/", sameSite: "Strict" }); } , 500);';
								
					$ret .= '});
				});
				
			});
			
			'.($this->detailed_cookie_settings_popuptext ? "" : "window.cookiebanner_zustimmung_popuptext_legacy = 1;").'
			'.($this->detailed_cookie_settings_merkliste ? "" : "window.cookiebanner_zustimmung_merkliste_legacy = 1;").'
			'.($this->detailed_cookie_settings_onlinepflege ? "" : "window.cookiebanner_zustimmung_onlinepflege_legacy = 1;").'
			'.($this->detailed_cookie_settings_translate ? "" : "window.cookiebanner_zustimmung_translate_legacy = 1;").'
			
			</script>'."\n"; // end initialization of cookie consent window
			
			// count first visit / page view without interaction
			if( $this->iniRead("cookiebanner.zustimmung.analytics.essentiell", 0) &&  $this->iniRead("cookiebanner.zustimmung.analytics.autoload", 0) && !isset($_COOKIE['cookieconsent_status']) ) {
					$ret .= '<script>';
					$ret .= 'setCookieSafely("cconsent_analytics", "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].' });'." \n";
					$ret .= 'jQuery.ajax({ url: window.location.href, dataType: \'html\'});'." \n"; // call same page with analytics allowed to count this page view
					$ret .= '</script>';
			}
			
			// Set Cookies automatically, if...:
			if( $this->iniRead("cookiebanner.zustimmung.einstellungen.essentiell", 0) == 2 ) {
					$ret .= '<script>';
					$ret .= 'setCookieSafely("cconsent_einstellungen", "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].' });'." \n";
					$ret .= 'setCookieSafely("cookieconsent_status", "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].' });'." \n";
					// $ret .= 'cookieconsent.popup.close();';
					$ret .= '</script>';
			}
			
			if( $this->iniRead("cookiebanner.zustimmung.popuptext.essentiell", 0) == 2 ) {
					$ret .= '<script>';
					$ret .= 'setCookieSafely("cconsent_popuptext", "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].' });'." \n";
					$ret .= '</script>';
			}
			
			if( $this->iniRead("cookiebanner.zustimmung.merkliste.essentiell", 0) == 2 ) {
					$ret .= '<script>';
					$ret .= 'setCookieSafely("cconsent_merkliste", "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].' });'." \n";
					$ret .= '</script>';
			}
			
			if( $this->iniRead("cookiebanner.zustimmung.onlinepflege.essentiell", 0) == 2 ) {
					$ret .= '<script>';
					$ret .= 'setCookieSafely("cconsent_onlinepflege", "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].' });'." \n";
					$ret .= '</script>';
			}
			
			if( $this->iniRead("cookiebanner.zustimmung.translate.essentiell", 0) == 2) {
					$ret .= '<script>';
					$ret .= 'setCookieSafely("cconsent_translate", "allow", { expires:'.$cookieOptions['cookie']['expiryDays'].' });'." \n";
					$ret .= '</script>';
			}
			
			
			
		}
		
		return $ret;
		
		return '';
	}
	
	
	
	/* function extract_tree_simple($info) {
	 foreach($info AS $line) {
	 echo
	 }
	 
	 return $this->extract_tree_simple($info);
	 } */
	
	/* function decision_tree_simple($info) {
	
	return $tree;
	} */
	
	function matchingportalby_k(&$db, $k_ID) {
	    
	    // select all tags that represent portals
	    $db->query("SELECT tag_id, tag_name FROM x_tags WHERE tag_name LIKE \".portal%\"");
	    $portal_tag_ids = array();
	    $portal_name_ids = array();
	    while( $db->next_record() ) {
	        $portal_tag_ids[] = $db->f('tag_id');
	        $portal_name_ids[$db->f('tag_id')] = str_replace(".portal", "", $db->f('tag_name'));
	    }
	    
	    // select all portal tags of portals that contain the course requestet
	    $db->query("SELECT tag_id FROM x_kurse_tags WHERE tag_id IN (".implode(",", $portal_tag_ids).") AND kurs_id = ".$k_ID);
	    $relevant_portal_tag_ids = array();
	    while( $db->next_record() )
	        $relevant_portal_tag_ids[] = $db->f('tag_id');
	        
	        $relevant_portal_ids = array();
	        foreach($relevant_portal_tag_ids AS $relevant_portal_tag_id) {
	            $relevant_portal_ids[] = $portal_name_ids[$relevant_portal_tag_id];
	        }
	        
	        // select all portals that contain the course requestet
	        $db->query("SELECT id, name, domains, einstellungen FROM portale WHERE id IN (".implode(",", $relevant_portal_ids).") AND status = 1");
	        $relevant_portals = array();
	        
	        while( $db->next_record() )
	            $relevant_portals[] = array('id' => $db->f('id'), 'name' => $db->f('name'), 'domains' => $db->f('domains'), 'einstellungen' => $db->f('einstellungen'));
	            
	            return $relevant_portals;
	}
	
	function match_loginid(&$db, $hoursago) {
	    $visitor_login_id = berechne_loginid();
	    $add_cond = $hoursago > 0 ? "AND last_login >= now() - INTERVAL $hoursago HOUR" : "";
	    $db->query("SELECT id FROM user WHERE last_login_id='$visitor_login_id' ".$add_cond);
	    return $db->num_rows();
	}
	
	function is_editor_active(&$db, $hoursago = 0) {
	    return $this->match_loginid($db, $hoursago); // default: false = normal visitor
	}
	
	function is_frondendeditor_active() {
	    return isset($_COOKIE['wisyEdit20']); // default: false = normal visitor
	}
	
	function getJSOnload()
	{
		// stuff to add to <body onload=...> - if possible, please prefer jQuery's onload functionality instead of <body onload=...>
		$ret = '';
		if( ($onload=$this->iniRead('onload')) != '' ) { $ret .= ' onload="' .$onload. '" '; }
		
		if( !$this->askfwd ) { $this->askfwd = strval($_REQUEST['askfwd']); }
		if(  $this->askfwd ) { $ret .= ' data-askfwd="' . isohtmlspecialchars($this->askfwd) . '" '; }
		
		return $ret;
	}
	
	function getCanonicalTag($canocicalUrl)
	{
		// optionally, for SEO, we support canonical urls here
		$ret = '';
		if( $canocicalUrl )
		{
			$ret .= '<link rel="canonical" href="'.$canocicalUrl.'" />' . "\n";
		}
		return $ret;
	}
	
	function getMobileAlternateTag($requestedPage = "")
	{
		$mobile_url = array_map("trim", (explode("|", $this->iniRead('meta.mobile_url', ""))));
		$mobile_maxresolution = (intval($mobile_url[1]) > 0) ? $mobile_url[1] : 640;
		$mobile_url = $mobile_url[0];
	
		if(str_replace("http://", "", $mobile_url) != "")
			$ret .= '<link rel="alternate" media="only screen and (max-width: '.$mobile_maxresolution.'px)" href="'.$mobile_url.'/'.$requestedPage.'" >' . "\n"; // $requestedPage may be empty for homepage
			
		return $ret;
	}
	
	function getAdditionalHeadTags()
	{
		return $this->iniRead('head.additionalTags', '');
	}
	
	function getBodyClasses($bodyClass)
	{
		// we assign one or more classes to the body tag;
		// this behaviour may be used to emulate simple templates via CSS. In detail, we use the following classes:
		// wisyp_homepage	-	 for the homepage, may be combined with the other wisyp_-classes:
		// wisyp_search, wisyp_kurs, wisyp_anbieter, wisyp_glossar, wisyp_edit, wisyp_error
		
		// add base blass
		$ret = $bodyClass;
		
		// add wisyq_ classes ...
		$added = array();
		$q_org = $this->getParam('q', '');
		$q = strtolower($q_org);
		$q = strtr($q, array('�'=>'ae', '�'=>'oe', '�'=>'ue', '�'=>'ss'));
		$q = preg_replace('/[^a-z,]/', '', $q);
		$q = explode(',', $q);
		for( $i = 0; $i < sizeof($q); $i++ )
		{
			if( $q[$i] != '' && !$added[ $q[$i] ] )
			{
				$ret .= $ret==''? '' : ' ';
				$ret .= 'wisyq_' . $q[$i];
				$added[ $q[$i] ] = true;
			}
		}
		
		// add homepage class
		$is_homepage = false;
		if( ($temp=$this->iniRead('homepage', '')) != '' && strpos($temp, 'index.php')===false )
		{
			if( substr($_SERVER['REQUEST_URI'], strlen($temp)*-1) == $temp )
				$is_homepage = true;
		}
		else
		{
			if( $bodyClass == 'wisyp_search' && $q_org == '' )
				$is_homepage = true;
		}

		if( $is_homepage )
		{
			$ret .= $ret==''? '' : ' ';
			$ret .= 'wisyp_homepage';
		}
		
		// done
		return $ret;
	}
	
	function getPrologue($param = 0)
	{
		if( !is_array($param) ) $param = array();
		
		// prepare the HTML-Page
		$bodyStart = $GLOBALS['wisyPortalBodyStart'];
		if( strpos($bodyStart, '<html') === false )
		{
		    if($this->iniRead('portal.inframe', '') != 1)
		        header('X-Frame-Options: SAMEORIGIN');
		    
			// we got only an HTML-Snippet (part of the the body part), create a more complete HTML-page from this
			$bodyStart	= '<!DOCTYPE html>' . "\n"
						. '<html lang="de">' . "\n"
						. '<head>' . "\n"
						. '<meta http-equiv="content-type" content="text/html; charset=ISO-8859-1" />' . "\n"
						. '__HEADTAGS__' 
						. '</head>' . "\n"
						. '<body__BODYATTR__>' . "\n"
						. '<div class="acclink"><a href="#wisy_contentareaAnchor">Zum Inhalt</a></div>'
						. $bodyStart;
			if( strpos($bodyStart, '__CONTENT__') === false )
			{
				$bodyStart .= '__CONTENT__';
			}
		}
		else
		{
			// we got a full HTML-Page - remove the closing body- and html-tags; we will add them ourselves
			$bodyStart = str_replace('</body>', '', $bodyStart);
			$bodyStart = str_replace('</html>', '', $bodyStart);
		}
		
		// replace ALL placeholders
		// $this->getRSSTags() . 
		$bodyStart = str_replace('__HEADTAGS__', $this->getTitleTags($param['title']) . $this->getFaviconTags() . $this->getOpensearchTags() . $this->getCSSTags() . $this->getCanonicalTag($param['canonical']) . $this->getMobileAlternateTag($param['canonical']) . $this->getJSHeadTags(). $this->getAdditionalHeadTags() . $this->getMetaDescription($param['title'], $param['beschreibung']) . $this->getHreflangTags(), $bodyStart);
		$bodyStart = str_replace('__BODYATTR__', ' ' . $this->getJSOnload(). ' class="' . $this->getBodyClasses($param['bodyClass']) . '" x-ms-format-detection="none"', $bodyStart);
		$bodyStart = $this->replacePlaceholders($bodyStart);
		$i1 = strpos($bodyStart, "<!-- include ");
		if( $i1!==false && ($i2=strpos($bodyStart, "-->", $i1))!==false )
		{
			$before    = substr($bodyStart, 0, $i1);
			$mid       = trim(substr($bodyStart, $i1+12, $i2-$i1-12));
			$after     = substr($bodyStart, $i2+3);
			$bodyStart = $before . file_get_contents($mid) . $after;
		}
		
		// split body end (stuff after __CONTENT__) from bodyStart
		$this->bodyEnd = '';
		if( ($p=strpos($bodyStart, '__CONTENT__')) !== false )
		{
			$this->bodyEnd = substr($bodyStart, $p+11);
			$bodyStart = substr($bodyStart, 0, $p);
		}
		
		// start page
		$ret = $bodyStart . "\n";
		
		$ret .= "\n<!-- content area -->\n";
		$ret .= '<a id="wisy_contentareaAnchor"></a><div id="wisy_contentarea">' . "\n";

		// anbieter-Toolbar
		if( $this->editSessionStarted )
		{
			$editor =& createWisyObject('WISY_EDIT_RENDERER_CLASS', $this);
			$ret .= $editor->getToolbar();
		}	
		
		return $ret;
	}
	
	function getEpilogue()
	{
	    // get page epilogue
	    $ret .= "\n";
	    
	    $ret .= '</div>' . "\n"; // /wisy_contentarea
	    $ret .= "<!-- /content area -->\n";
	    
	    // footer (wrap in __CONTENT__)
	    $ret .= "\n<!-- after content -->\n";
	    $ret .= $this->bodyEnd? ($this->bodyEnd . "\n") : '';
	    $ret .= "<!-- /after content -->\n\n";
	    
	    // analytics stuff at bottom to avoid analytics slowing down
	    // the whole site ...
	    $ret .= $this->getAnalytics();
	    
	    $ret .= $this->getPopup();
	    
	    // iwwb specials
	    if( $this->iniRead('iwwbumfrage', 'unset')!='unset' && $_SERVER['HTTPS']!='on' )
	    {
	        require_once('files/iwwbumfrage.php');
	    }
	    
	    $ret .= '</body>' . "\n";
	    $ret .= '</html>' . "\n";
	    
	    return $ret;
	}
	
	function getPopup() {
	    $ret = "";
	    
	    // if cookie popuptext denied or not set (first page view) show text popup if activated and text available
	    if( $this->iniRead('popup', false) && strlen(trim($this->iniRead('popup.text', ''))) && ( (isset($_COOKIE['cconsent_popuptext']) && $_COOKIE['cconsent_popuptext'] == 'deny') || !isset($_COOKIE['cconsent_popuptext'])) )
	        $ret = '
				<div class="hover_bkgr_fricc">
						<span class="helper"></span>
							<div>
        <div class="popupCloseButton">&times;</div>
        <p>'.trim($this->iniRead('popup.text', '')).'</p>
							</div>
				</div>';
	        
	        return $ret;
	}
	
	function getAnalytics() {
	    $ret = "\n";
	    
	    $uacct = $this->iniRead('analytics.uacct', '');
	    if( $uacct != '' )
	    {
	        $expiryDays = intval($this->iniRead('cookiebanner.cookiegueltigkeit', 7));
	        
	        $ret .= '
				<script>
				'.($this->detailed_cookie_settings_analytics ? 'var optedOut = (jQuery.cookie("cconsent_analytics") != "allow");' : ' var optedOut = (document.cookie.indexOf("cookieconsent_status=deny") > -1);').'
				    
				if (!optedOut) {
					(function(i,s,o,g,r,a,m){i["GoogleAnalyticsObject"]=r;i[r]=i[r]||function(){
						(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
						m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
					})(window,document,"script","https://www.google-analytics.com/analytics.js","ga");
					ga("create", "' . $uacct . '", { cookieFlags: "max-age='. ( $expiryDays * 24 * 3600 ) .';secure;samesite=none" });
					ga("set", "anonymizeIp", true);
					ga("send", "pageview");
				} else {
					;
				}
				</script>';
	    }
	    
	    $piwik = $this->iniRead('analytics.piwik', '');
	    
	    if( $piwik != '' )
	    {
	        if( strpos($piwik, ',')!==false ) {
	            list($piwik_site, $piwik_id) = explode(',', $piwik);
	        }
	        else {
	            $piwik_site = 'statistik.kursportal.info';
	            $piwik_id = $piwik;
	        }
	        
	        $ret .= "
				<!-- Matomo -->
				<!-- analytics.piwik -->
				<script type=\"text/javascript\" id=\"matomo_script\">
						var _paq = window._paq || [];
						_paq.push(['trackPageView']);
						_paq.push(['enableLinkTracking']);
	            
						function embedMatomoTracking() {
								var u=\"//".$piwik_site."/\";
								_paq.push(['setTrackerUrl', u+'matomo.php']);
								_paq.push(['setSiteId', ".$piwik_id."]);
								var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
								g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
						};
								    
						/* console.log('".($_COOKIE['cconsent_analytics'] != 'allow')."'); */
				</script>
				<!-- /analytics.piwik -->
				<!-- End Matomo Code -->
				";
	    }
	    
	    $do_track_matomo = ( $piwik && $this->detailed_cookie_settings_analytics && $_COOKIE['cconsent_analytics'] == 'allow' )
	    || ( $piwik && $this->detailed_cookie_settings_analytics && $this->iniRead("cookiebanner.zustimmung.analytics.autoload", 0) )
	    || ( $this->detailed_cookie_settings_einstellungen == "" );
	    
	    // Load if piwik defined and cookie consent allow OR piwik defined and autoload
	    // Cannot check for !isset($_COOKIE['piwik_ignore'] b/c thrd.party cookie statistik..., but not necessary either, b/c cookie resepekted by matomoscript
	    // Alternative https://developer.matomo.org/guides/tracking-javascript-guide#optional-creating-a-custom-opt-out-form
	    if( $do_track_matomo ) {
	        $ret .= "
				<!-- Execute Matomo Tracking-->
				<script type=\"text/javascript\">
					setTimeout(function () {
							embedMatomoTracking();
					}, 5);
				</script>
				";
	        
	    }
	    
	    return $ret;
	}
	
	function getSearchField()
	{
	    if(trim($this->iniRead('disable.suche', false)))
	        return false;
	        
		// get the query
		$q = $this->getParam('q', '');
		$q_orig = $q;
		
		// radius search?
		if( $this->iniRead('searcharea.radiussearch', 0) )
		{
			// extract radius search parameters from query string
			$km_arr = array('' =>	'Umkreis', '1' => '1 km', '2' => '2 km', '5' => '5 km', '7' => '7 km', '10' => '10 km', '20' => '20 km', '50' => '50 km');
			$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this);
			$tokens = $searcher->tokenize($q);
			$q = '';
			$bei = '';
			$km = '';			
			for( $i = 0; $i < sizeof((array) $tokens['cond']); $i++ ) {
				switch( $tokens['cond'][$i]['field'] ) {
					case 'bei':	
						$bei = $tokens['cond'][$i]['value']; 
						break;
						
					case 'km':	
						$km =  intval($tokens['cond'][$i]['value']);
						if( $km <= 0 ) $km = '';
						if( !$km_arr[$km] ) $km_arr[$km] = "$km km";
						break;
					
					default:
						$q .= $q ? ', ' : '';
						$q .= $tokens['cond'][$i]['field'] != 'tag' ? ($tokens['cond'][$i]['field'].':') : '';
						$q .= $tokens['cond'][$i]['value'];
						break;
				}
			}
			if( isset($tokens['show']) && $tokens['show'] == 'anbieter' ) {
				$q .= $q ? ', ' : '';
				$q .= 'Zeige:Anbieter';
			}
		}

		// if the query is not empty, add a comma and a space		
		$q = trim($q);
		if( $q != '' )
		{
			if( substr($q, -1) != ',' )
				$q .= ',';
			$q .= ' ';
		}

		// link to send favourites
		$mailfav = '';
		if( $this->iniRead('fav.mail', '1') ) {
			$mailsubject = $this->iniRead('fav.mail.subject', 'Kursliste von __HOST__');
			$mailsubject = str_replace('__HOST__', $_SERVER['HTTP_HOST'], $mailsubject);
			$protocol = $this->iniRead('portal.https', '') ? "https" : "http";
			$mailbody = $this->iniRead('fav.mail.body', "Das ist meine Kursliste zum Ausdrucken von __HOST__:\n\n".$protocol."://__HOST__/");
			$mailbody = str_replace('__HOST__', $_SERVER['HTTP_HOST'], $mailbody);
			$mailfav = 'mailto:?subject='.rawurlencode($mailsubject).'&body='.rawurlencode($mailbody);
		}

		// echo the search field
		$DEFAULT_PLACEHOLDER	= '';
		$DEFAULT_ADVLINK_HTML	= '<a href="advanced?q=__Q_URLENCODE__" id="wisy_advlink">Erweitern</a>';
		$DEFAULT_RIGHT_HTML		= '| <a href="javascript:window.print();">Drucken</a>';
		$DEFAULT_BOTTOM_HINT	= 'bitte <strong>Suchw&ouml;rter</strong> eingeben - z.B. Englisch, VHS, Bildungsurlaub, ...';
		
		echo "\n";
		
		// #richtext
		$richtext = (intval(trim($this->iniRead('meta.richtext'))) === 1);
		$aboutpage = intval(trim($this->iniRead('meta.aboutpage')));
		$contactpage = intval(trim($this->iniRead('meta.contactpage')));
		
		global $wisyRequestedFile;
		
		$schema = "https://schema.org/WebSite";
		$pagetype = $this->getPageType();
		$schema = ($pagetype == "suche") ? "https://schema.org/SearchResultsPage" : $schema;
		$schema = ($pagetype == "glossar" || $pagetype == "anbieter" || $pagetype == "kurs") ? "https://schema.org/ItemPage" : $schema;
		$schema = ($wisyRequestedFile == "g".$aboutpage) ? "https://schema.org/AboutPage" : $schema;
		$schema = ($wisyRequestedFile == "g".$contactpage) ? "https://schema.org/ContactPage" : $schema;
		
		if($richtext) {
		    echo '<div itemscope itemtype="'.$schema.'">';
		    
		    $websiteurl .= trim($this->iniRead('meta.portalurl', ""));
		    
		    if($websiteurl)
		        $metatags .= '<meta itemprop="url" content="'.$websiteurl.'">'."\n";
		        
		}
		
		if($pagetype != "suche") {
		    $searchAction = ($richtext) ? 'itemprop="potentialAction" itemscope itemtype="https://schema.org/SearchAction"' : '';
		    $target = ($richtext) ? '<meta itemprop="target" content="https://'.$_SERVER['SERVER_NAME'].'/search?q={q}"/>' : '';
		    if($pagetype == "startseite") { $q = $this->iniRead('searcharea.placeholder', $DEFAULT_PLACEHOLDER); }
		    $queryinput = ($richtext) ? 'itemprop="query-input" placeholder="'.$q.'"': '';
		    $q = ""; // sonst aendert sich mit jeder Seite der DefaultValue
		} else {
		    $searchAction = ($richtext) ? 'itemscope itemtype="https://schema.org/FindAction"' : '';
		    $target = ($richtext) ? '
				<meta itemprop="target" content="https://'.$_SERVER['SERVER_NAME'].'/search?q={q}"/>
				<link itemprop="actionStatus" href="https://schema.org/CompletedActionStatus">' : '';
		    $queryinput = '';
		}
		
		echo $this->getSchemaWebsite();
		// end: #richtext
		
		echo "\n";
		echo '<div id="wisy_searcharea">' . "\n";
			echo '<form action="search" method="get">' . "\n";
				echo '<input type="text" id="wisy_searchinput" class="ac_keyword" name="q" value="' .isohtmlspecialchars($q). '" placeholder="' . $this->iniRead('searcharea.placeholder', $DEFAULT_PLACEHOLDER) . '" />' . "\n";
				if( $this->iniRead('searcharea.radiussearch', 0) )
				{
					echo '<input type="text" id="wisy_beiinput" class="ac_plzort" name="bei" value="' .$bei. '" placeholder="PLZ/Ort" />' . "\n";
					echo '<select id="wisy_kmselect" name="km" >' . "\n";
						foreach( $km_arr as $value=>$descr ) {
							$selected = strval($km)==strval($value)? ' selected="selected"' : '';
							echo "<option value=\"$value\"$selected>$descr</option>";
						}
					echo '</select>' . "\n";
				}
				echo '<input type="submit" id="wisy_searchbtn" value="Suche" />' . "\n";
				if( $this->iniRead('searcharea.advlink', 1) )
				{
					echo '' . "\n";
				}
				
				echo $this->replacePlaceholders($this->iniRead('searcharea.advlink', $DEFAULT_ADVLINK_HTML)) . "\n";
				echo $this->replacePlaceholders($this->iniRead('searcharea.html', $DEFAULT_RIGHT_HTML)) . "\n";
			echo '</form>' . "\n";
			echo '<div class="wisy_searchhints" data-favlink="' . isohtmlspecialchars($mailfav) . '">' .  $this->replacePlaceholders($this->iniRead('searcharea.hint', $DEFAULT_BOTTOM_HINT)) . '</div>' . "\n";
		echo '</div>' . "\n\n";
	
		echo $this->replacePlaceholders( $this->iniRead('searcharea.below', '') ); // deprecated!
	}
	
	// #richtext
	function getSchemaWebsite() {
	    $websitename = ''; $websiteurl = ""; $metatags = "";
	    
	    if(intval(trim($this->iniRead('meta.richtext'))) === 1) {
	        $websitename .= trim($this->iniRead('meta.portalname', ""));
	        $websiteurl .= trim($this->iniRead('meta.portalurl', ""));
	    }
	    
	    if($websitename)
	        $metatags .= '<meta itemprop="name" content="'.strtoupper($websitename).'">'."\n";
	        
	        if($websiteurl)
	            $metatags .= '<meta itemprop="url" content="'.$websiteurl.'">'."\n";
	            
	            // DF1 start date, to harmonize SERP entry date with DF
	            // if($websiteurl)
	            // $metatags .= "<meta name='datePublished' itemprop='datePublished' content='".$YDP."-".$mDP."-".dDP."."'>"."\n";
	            
	            return $metatags;
	}

	/******************************************************************************
	 main()
	 ******************************************************************************/

	function &getRenderer()
	{
		// this function returns the renderer object to use _or_ a string with the URL to forward to
		global $wisyRequestedFile;

		switch( $wisyRequestedFile )
		{
		    // homepage
		    // (in WISY 2.0 gibt es keine Datei "index.php", diese wird vom Framework aber als Synonym fuer "Homepage" verwendet)
		    case 'index.php':
				for( $i = 1; $i <= 9; $i++ ) 
				{
					$prefix = $i==1? 'switch' : "switch{$i}";
					$switch_dest = $this->iniRead("$prefix.dest", '');
					$if_browser = $this->iniRead("$prefix.if.browser", '');
					if( $switch_dest && $if_browser && preg_match("/". $if_browser ."/i", $_SERVER['HTTP_USER_AGENT'])) {
						if( $this->iniRead("$prefix.ask", 1) ) {
							$this->askfwd = $switch_dest;
							break;
						}
						else {
							return $switch_dest;
						}
					}
					else {
						break;
					}
				}

				if( ($temp=$this->iniRead('homepage', '')) != '' && strpos($temp, 'index.php')===false )
				{
					if( $this->askfwd ) {
						$temp .= (strpos($temp, '?')!==false? '&' : '?') . 'askfwd=' . urlencode($this->askfwd);
					}
					return $temp . (strpos($temp, '?') !== FALSE ? "&" : "?") . "ver=".date("Y-m-d-H-i-s");
				}
				else
				{
					return createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this);
				}

			// search
			case 'search':
				return createWisyObject('WISY_SEARCH_RENDERER_CLASS', $this);
			
			case 'advanced':
				return createWisyObject('WISY_ADVANCED_RENDERER_CLASS', $this);
	
			case 'tree':
				return createWisyObject('WISY_TREE_RENDERER_CLASS', $this);
	
			case 'geocode':
				return createWisyObject('WISY_OPENSTREETMAP_CLASS', $this);
	
			// view
			default:
				$firstLetter = substr($wisyRequestedFile, 0, 1);
				$_GET['id'] = intval(substr($wisyRequestedFile, 1));
	
				if( $firstLetter=='k' && $_GET['id'] > 0 )
				{
					return createWisyObject('WISY_KURS_RENDERER_CLASS', $this);
				}
				else if( $firstLetter=='a' && $_GET['id'] > 0 )
				{
					return createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this);
				}
				else if( $firstLetter=='g' && $_GET['id'] > 0 )
				{
					return createWisyObject('WISY_GLOSSAR_RENDERER_CLASS', $this);
				}
				else if( ($content=$this->iniRead('fakefile.'.$wisyRequestedFile, '0'))!='0' )
				{
					echo str_replace('<br />', '\n', $content);
					exit();
				}
				// #vanityurl
				else if( ($gid=$this->iniRead('glossaralias.'.$wisyRequestedFile, '0'))!='0' )
				{
				    // Wenn sinnvolle Glossar-ID: Ist max. 20-stellige Zahl, die nicht mit 0 anfaengt
				    if(preg_match("/^[1-9][0-9]{1,20}$/", $gid))
				    {
				        $_GET['id'] = trim($gid);	// unschoen, aber hier nicht sinnvoll anders moeglich?.
				        return createWisyObject('WISY_GLOSSAR_RENDERER_CLASS', $this);
				    }
				}
				break;

			// misc
			case 'sync':
				return createWisyObject('WISY_SYNC_RENDERER_CLASS', $this);
			
			case 'autosuggest':
				return createWisyObject('WISY_AUTOSUGGEST_RENDERER_CLASS', $this);

			case 'autosuggestplzort':
				return createWisyObject('WISY_AUTOSUGGESTPLZORT_RENDERER_CLASS', $this);
				
			case 'opensearch':
				return createWisyObject('WISY_OPENSEARCH_RENDERER_CLASS', $this);

			case 'rss':
			; // return createWisyObject('WISY_RSS_RENDERER_CLASS', $this, array('q'=>$this->getParam('q')));

			case 'portal.css':
				return createWisyObject('WISY_DUMP_RENDERER_CLASS', $this, array('src'=>$wisyRequestedFile));

			case 'feedback':
				return createWisyObject('WISY_FEEDBACK_RENDERER_CLASS', $this);

			case 'edit':
				return createWisyObject('WISY_EDIT_RENDERER_CLASS', $this);
				
			case 'robots.txt':
			case 'sitemap.xml':
			case 'sitemap.xml.gz':
			case 'terrapin':
				return createWisyObject('WISY_ROBOTS_RENDERER_CLASS', $this, array('src'=>$wisyRequestedFile));

			case 'paypalok':	 //  paypal does not forward any url-parameters, so we need a "real" file as kursportal.info/paypalok
			case 'paypalcancel': //   - " -
				return 'edit?action=kt';

			case 'paypalipn':
				return createWisyObject('WISY_BILLING_RENDERER_CLASS', $this);
			
			// deprecated URLs
			case 'kurse.php':
			case 'anbieter.php':
			case 'glossar.php':
				$firstLetter = substr($wisyRequestedFile, 0, 1);
				return $firstLetter . $_GET['id'];
		}
		
		return false;
	}

	function main()
	{
		// authentication required?
		if( $this->iniRead('auth.use', 0) == 1 )
		{
			$auth =& createWisyObject('WISY_AUTH_CLASS', $this);
			$auth->check();
		}
		
		/* Don't allow search request parameters to be set, if search isn't valid for page type -> don't let search engines and hackers consume unecessary ressources ! */
		global $wisyRequestedFile;
		$valid_searchrequests = array('rss', 'search', 'advanced', 'filter', 'tree', 'geocode', 'autosuggest', 'autosuggestplzort', 'opensearch', 'kurse.php', 'anbieter.php', 'glossar.php');
		if(
		    (isset($_GET['q']) || isset($_GET['qs']) || isset($_GET['qf']) || isset($_GET['qsrc']) || isset($_GET['offset']))
		    && !in_array($wisyRequestedFile, $valid_searchrequests)
		    && stripos($wisyRequestedFile, 'k') !== 0 && strpos($wisyRequestedFile, 'a') !== 0 && strpos($wisyRequestedFile, 'g') !== 0
		    )
		    $this->error404("Anfrage nicht erlaubt: q, qs, wf, qsrc, qtrigger, offset f&uuml;r  ".trim($wisyRequestedFile, '.php')
		        );
		    
		    foreach($_GET AS $get) {
		        if(!is_array($get) && strpos($get, 'volltext') !== FALSE && $this->qtrigger != 'h' && $this->force != 1) // qtrigger = h -> human search (click/return), force = link from unsuccessful searches
		            $this->error404("Volltextanfrage per direkter Verlinkung aus Ressourcengr&uuml;nden nicht erlaubt.<br><br>Bitte geben Sie Ihre Volltextanfrage unbedingt manuell in das Suchfeld ein - bzw. klicken Sie noch mal selbst auf 'Kurse finden' !<br><br>");
		    }

		// see what to do
		$renderer =& $this->getRenderer();
		if( is_object($renderer) )
		{
			// start the edit session, if needed
			if( isset($_COOKIE[ $this->editCookieName ]) )
			{
				$this->startEditSession();
			}
			
			// for "normal pages" as kurse, anbieter, search etc. switch back to non-secure
			if( $renderer->unsecureOnly && $_SERVER['HTTPS']=='on' && !$this->iniRead('portal.https', '') )
			{
			    $redirect = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
			    fwd301($redirect);
			}
			
			// render
			$renderer->render();
			$this->cacheFlush();
		}
		else if( $renderer != '' )
		{
			fwd301($renderer);
		}
		else
		{
			$this->error404();
		}
	}
	
	function addCConsentOption($name, $cookieOptions) {
	    $cookie_essentiell = intval($this->iniRead("cookiebanner.zustimmung.{$name}.essentiell", 0));
	    $expiration = $cookieOptions['cookie']['expiryDays'];
	    $details = "<span class='cookies_techdetails inactive'><br>Speicherdauer:".$expiration." Tage, Name: cconsent_{$name}".($name == 'analytics' ? ', Name: _pk_ref (Speicherdauer: 6 Monate), Name: _pk_cvar (Speicherdauer: 30min.), Name: _pk_id (Speicherdauer: 13 Monate), Name: _pk_ses (Speicherdauer: 30min.)': '').'</span>';
	    // print_r($cookieOptions['cookie']); die("ok");
	    return "<li class='{$name} ".($cookie_essentiell == 2 ? "disabled" : "")."'>
    				<input type='checkbox' name='cconsent_{$name}' "
    				.(($cookie_essentiell || $_COOKIE['cconsent_'.$name] == 'allow') ? "checked='checked'" : "")
    				.($cookie_essentiell == 2 ? "disabled" : "")
    				."> "
    				."<div class='consent_option_infos'>"
    				.$cookieOptions["content"]["zustimmung_{$name}"]
    				."<span class='importance'>"
    				.($cookie_essentiell === 1 ? '<br>(essentiell)' : ($cookie_essentiell == 2 ? '<br>(technisch notwendig)' : '<br><b>(optional'.($_COOKIE['cconsent_'.$name] == 'allow' ? ' - aktiv zugestimmt' : '').')</b>')).$details.'</span>'
    				.'</div>'
    		  ."</li>";
	}
	
};




