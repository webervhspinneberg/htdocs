<?php if( !defined('IN_WISY') ) die('!IN_WISY');

require_once('admin/config/codes.inc.php');

class WISY_DURCHF_CLASS
{
	var $framework;
	
	protected $plzfilterObj;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
		$this->plzfilterObj = createWisyObject('WISY_PLZFILTER_CLASS', $this->framework, array(
			'durchf.plz.allow' => $this->framework->iniRead('durchf.plz.allow', ''),
			'durchf.plz.deny'  => $this->framework->iniRead('durchf.plz.deny',  ''),
			'durchf.plz.order' => $this->framework->iniRead('durchf.plz.order', '')
		));
		$this->resetSeeAbove();
	}

	function shy($text)
	{
		global $g_tr;
		if( !is_array($g_tr) )
		{
			$tr = array(
				"Ausstellungs-f",
				"Arbeits-such",
				"Ganz-t",
				"Nach-mittags",
			);
			$g_tr = array();
			for( $i = 0; $i < sizeof($tr); $i++ )
				$g_tr[ str_replace('-', '', $tr[$i]) ] = str_replace('-', '&shy;', $tr[$i]);
		}
				
		return strtr($text, $g_tr);
	}

	protected function formatArtSpalte($stichwoerter_arr, $details)
	{
		// Array Stichwörter/Tags => Bilder/Text erzeugen
		// (wir verwenden hier die Informationen aus der stichworttabelle anstelle von x_tags, da diese einfacher zur Verfügung stehen und
		// nicht aktualisiert werden müssen, d.h. Änderungen in der Onlinepflege sind sofort sichtbar.
		// Nachteil ist, dass einige Stichwörter erst rekonstruiert werden müssen (aus bu_nummer, s. wisy-sync-renderer-class.inc.php))
		if( !is_array($this->imgTagArr) ) 
		{
			// init Array with defaults
			$this->imgTagArr = array(
				'tc1'	=>	array('<span class="wisyr_art_icon wisyr_art_ganztaegig">&#9673;</span>',	 	'Ganzt&auml;gig'),
				'tc2'	=>	array('<span class="wisyr_art_icon wisyr_art_vormittags">&#9680;</span>',  		'Vormittags'),
				'tc3'	=>	array('<span class="wisyr_art_icon wisyr_art_nachmittags">&#9681;</span>', 		'Nachmittags'),
				'tc4'	=>	array('<span class="wisyr_art_icon wisyr_art_abends">&#9682;</span>',		 	'Abends'),
			    'tc5'	=>	array('<span class="wisyr_art_icon wisyr_art_wochenende">WE</span>',			'Wochenende'),
			    1		=>	array('<span class="wisyr_art_icon wisyr_art_bildungsurlaub">BU</span>',		'Bildungsurlaub'),
			    7721	=>	array('<span class="wisyr_art_icon wisyr_art_fernunterricht">&#9993;</span>',	'Fernunterricht'),
			    7430	=>	array('<span class="wisyr_art_icon wisyr_art_fernunterricht">BL</span>',	    'Blended Learning'),
			    7639    =>	array('<span class="wisyr_art_icon wisyr_art_fernunterricht">WWW</span>',		'Web-Seminar'),
				17261	=>	array('<span class="wisyr_art_icon wisyr_art_fernunterricht">P</span>',			'Pr&auml;senzunterricht'),
			    806441	=>	array('<span class="wisyr_art_icon wisyr_art_fernunterricht">WWW</span>',		'Web-Seminar'), // eigentl. Teleteaching = Web-Seminar
			    832301	=>	array('<span class="wisyr_art_icon wisyr_art_bildungszeit">BZ</span>',		'Bildungszeit')
			);

			// overwrite defaults with portal settings from img.tag
			foreach( $GLOBALS['wisyPortalEinstellungen'] as $key => $value ) {
				
				if( substr($key, 0, 8) == 'img.tag.' ) 
				{
					$tag = str_replace('img.tag.', '', $key);
					if( $value == '' ) {
						unset($this->imgTagArr[ $tag ]);
					}
					else {
						$this->imgTagArr[ $tag ] = explode('|', $value);
					}
				}
			}

		}
		
		// make stichwoerter easier searchable
		$stichwoerter_hash = array();
		foreach( $stichwoerter_arr as $dummy=>$attr ) {
			$stichwoerter_hash[ $attr['id'] ] = 1;
		}
		
		// render
		$html = '';
		
		foreach( $this->imgTagArr as $id=>$img_arr )
		{
		    if( $stichwoerter_hash[ $id ] )
		    {
		        $img_icon = $img_arr[0];
		        $img_text = $img_arr[1];
		        
		        $ext = substr($img_icon, -4);
		        if( $ext == '.gif' || $ext == '.png' || $ext == '.jpg' || $ext == '.svg' ) {
		            $alt = $details? '' : $img_text;
		            $html .= '<img src="'.$img_icon.'" alt="'.$alt.'" title="'.$img_text.'" data-sw_id="'.$id.'"/>';
		        }
		        else {
		            $html .= '<span title="'.$img_text.'" data-sw_id="'.$id.'"/>'.$img_icon.'</span>';
		        }
		        
		        if( $details ) {
		            $html .= ' <span class="wisyr_art_details" data-sw_id="'.$id.'"/>' . $img_text . '</span>';
		        }
		    }
		}
		
		return $html;
	}

	function formatBeginnoptionen($opt)
	{
		global $codes_beginnoptionen_array;
		
		if( !is_array($codes_beginnoptionen_array) ) 
		{	
			require_once('admin/config/codes.inc.php');
			global $codes_beginnoptionen;
			$codes_beginnoptionen_array = array();
			$temp = explode('###', $codes_beginnoptionen);
			for( $i = 0; $i < sizeof($temp); $i+=2 ) {
				$codes_beginnoptionen_array[$temp[$i]] = $temp[$i+1];
			}
		}
	
		if( $opt <= 0 ) {
			return '';
		}
		else if( $codes_beginnoptionen_array[$opt] ) {
		    return cs8($codes_beginnoptionen_array[$opt]); // UTF-8 encode because the source file (admin/config/codes.inc.php) is still ISO-encoded
		}
		else {
			return '';
		}
	}

	function formatKurstage($kurstage)
	{
		// convert the "kurstage" bitfield to an string
		global $codes_kurstage_array;
		
		if( !is_array($codes_kurstage_array) ) 
		{	
			global $codes_kurstage;
			if( !is_string($codes_kurstage) ) {
				require_once('admin/config/codes.inc.php');
			}
			
			$codes_kurstage_array = array();
			$temp = explode('###', $codes_kurstage);
			for( $i = 0; $i < sizeof($temp); $i+=2 ) {
				$codes_kurstage_array[intval($temp[$i])] = trim($temp[$i+1]);
			}
		}
	
		$c = 0;
		reset($codes_kurstage_array);
		foreach($codes_kurstage_array as $value => $descr) {
			if( $kurstage & $value ) {
				$c++;
			}
		}
	
		$ret = '';
		reset($codes_kurstage_array);
		foreach($codes_kurstage_array as $value => $descr) {
			if( $kurstage & $value ) {
				$ret .= $ret? ($c==1? ' und ' : ', ') : '';
				$ret .= $descr;
				$c--;
			}
		}
		
		return cs8($ret);
	}

	function formatDauer($dauer, $stunden, $mask2 = '%1 (%2)') // return as HTML
	{
		// Dauer formatieren
		global $codes_dauer_array;
		if( !is_array($codes_dauer_array) ) 
		{	
			require_once('admin/config/codes.inc.php');
			global $codes_dauer;
			$codes_dauer_array = array();
			$temp = explode('###', $codes_dauer);
			for( $i = 0; $i < sizeof($temp); $i+=2 ) {
				$codes_dauer_array[$temp[$i]] = $temp[$i+1];
			}
		}
	
		if( $dauer <= 0 ) {
		    $dauer = '';
		}
		else if( $codes_dauer_array[$dauer] && $dauer <= 365 ) {
		    $dauer = str_replace(' ', '&nbsp;', $codes_dauer_array[$dauer]) . ' ';
		} else if(
		    $codes_dauer_array[$dauer] && $dauer > 365
		    || !isset( $codes_dauer_array[$dauer] ) && $dauer > 90
		    ) {
		        $dauer = daysToReadable($dauer) . ' ';
		    }
		    else {
		        $dauer = "$dauer Tage";
		    }
		    
		
		// stunden
		if( $stunden > 0 ) {
			$stunden = "$stunden&nbsp;Std.";
		}	
		else {
			$stunden = '';
		}
		
		// done
		if( $dauer != '' && $stunden != '' ) {
			$ret = str_replace('%1', $dauer, $mask2);
			$ret = str_replace('%2', $stunden, $ret);
		}
		else if( $dauer != '' ) {
			$ret = $dauer;
		}
		else if( $stunden != '' ) {
			$ret = $stunden;
		}
		else {
			$ret = 'k. A.';
		}
		return cs8($ret); // UTF-8 encode because the source file (admin/config/codes.inc.php) is still ISO-encoded
	}

	function formatPreis($preis, $sonderpreis, $sonderpreistage, $beginn, $preishinweise_str, $html = 1, $addParam = 0)
	{
		if( !is_array($addParam) ) $addParam = array();

		// Preis formatieren
		if( $preis == -1 ) 
		{
			$ret = 'k. A.';
		}
		else if( $preis == 0 )
		{
			$ret = 'kostenlos';
		}
		else 
		{
			$ret = '<span class="wisyr_euro">' . $preis . '</span>';

			
			if( $preis>0
			 && $sonderpreis>0 
			 && $sonderpreis<$preis )
			{
				$beginn = explode(' ', str_replace('-', ' ', $beginn));
				$beginn = mktime(0, 0, 0, $beginn[1], $beginn[2], $beginn[0]) - $sonderpreistage*86400;
				if( time() >= $beginn ) {
					if( $html ) {
						$ret = "<strike>$ret</strike><br /><span class=\"red\">" . $this->formatPreis($sonderpreis, -1, 0, 0, '', $html, 0) . '</span>';
					}
					else {
						$ret = $this->formatPreis($sonderpreis, -1, 0, 0, '', $html, 0) . " (bisheriger Preis: $ret)";
					}
				}
			}
		}
	
		if( $addParam['showDetails'] )
		{
		    $preishinweise_arr = array();
		    
		    global $controlTags;
		    $aid_output = false;
		    
		    foreach( $addParam['stichwoerter'] as $stichwort ) {
		         
		         if( !$aid_output && ($stichwort['id'] == $controlTags['Bildungsgutschein']
		             || $stichwort['id'] == $controlTags['Umschulung']
		             || $stichwort['id'] == $controlTags['Aktivierungsgutschein']
		             || $stichwort['id'] == $controlTags['DeuFoeV']
		             || $stichwort['id'] == $controlTags['Integrationskurs']
		             || $stichwort['id'] == $controlTags['Integrationskurs (zu speziellem Förderbedarf)']
		             || $stichwort['id'] == $controlTags['Integrationskurs (Intensivkurs)']
		             || $stichwort['id'] == $controlTags['Integrationskurs (mit Alphabetisierung)']
		             || $stichwort['id'] == $controlTags['Integrationskurs für Zweitschriftlernende']
		             || $stichwort['id'] == $controlTags['Orientierungskurs'] )
		             ) {
		                 $preishinweise_arr[] = cs8('kostenlos&nbsp;bei&nbsp;F&ouml;rderung');
		                 $aid_output = true;
		             }
		             
		        if( $stichwort['id'] == $controlTags['Preis komplex'] )
		          $preishinweise_arr[] = cs8('Preisstruktur komplex - ggf. beim Anbieter einholen.');       
		    } 
		    
		    if( $preishinweise_str ) $preishinweise_arr[] = $preishinweise_str;
			
			if( sizeof((array) $preishinweise_arr) )
			{
			    $preishinweise_out = implode(', ', $preishinweise_arr);
			    $ret = str_replace(array("k. A.", "k.A."), "", $ret);
			    if( $html ) {
			        $preishinweise_out = cs8($preishinweise_out);
			        $ret .= '<div class="wisyr_preis_hinweise">' .  str_replace(chr(128), "&euro;", htmlentities(html_entity_decode($preishinweise_out))) . '</div>'; // str_replace(chr(0xE2).chr(0x82).chr(0xAC), "&euro;",
			    }
			    else {
			        $ret .= " ($preishinweise_out)";
			    }
			}
			
			// Auto link URLs in Preishinweis
			$replaceURL = (strpos($ret, 'http') === FALSE && strpos($ret, 'https') === FALSE) ? '<a href="http://$0" target="_blank" rel="noopener noreferrer" title="$0">'.$this->framework->iniRead("preishinweis.linktext", "$0").'</a>' : '<a href="$0" target="_blank" rel="noopener noreferrer" title="$0">'.$this->framework->iniRead("preishinweis.linktext", "$0").'</a>';
			$ret = preg_replace('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', $replaceURL, $ret);
		}
		
		return $ret;
	}
	
	function getDurchfuehrungIds(&$db, $kursId, $sabg = 0 /*1=abgel. Df immer anzeigen, 0=abgel. Df nur anzeigen  wenn keine aktuellen vorhanden*/)
	{
		// "ORDER BY beginn='0000-00-00 00:00:00'" stellt Kurse ohne Datum ans Ende
		// (der erste Versuch, "STRCMP(beginn,'0000-00-00 00:00:00') DESC" klappte auf der Server (MySQL 4.1.10a) nicht)
		for( $test = ($sabg?2:0); $test <= 2; $test++ )
		{
			switch( $test )
			{
				case 0:
					// nur Kurse anzeigen, die noch nicht begonnen haben
					$where = " AND (beginn>='".strftime("%Y-%m-%d 00:00:00")."' OR (beginn='0000-00-00 00:00:00' AND beginnoptionen>0))";	
					break;
				
				case 1:
					// Stichwort 315/"Einstieg beis kursende Möglich"
					$db->query("SELECT attr_id FROM kurse_stichwort WHERE primary_id=$kursId AND attr_id=315");
					if( $db->next_record() ) {
						$where = " AND (ende>='".strftime("%Y-%m-%d 00:00:00")."')";
					}
					else {
						continue 2;
					}
					break;
				
				case 2: 
					// alle Kurse anzeigen
					$where = ""; 
					break;
			}
			
			$durchfuehrungenIds = array();
	
			$db->query("SELECT secondary_id, plz FROM kurse_durchfuehrung, durchfuehrung WHERE primary_id=$kursId AND id=secondary_id $where
						 ORDER BY beginn='0000-00-00 00:00:00', beginn, beginnoptionen, structure_pos");
			while( $db->next_record() )
			{
				if( $this->plzfilterObj->is_valid_plz($db->f('plz')) ) {
					$durchfuehrungenIds[] = $db->f('secondary_id');
				}
			}
			
			if( sizeof($durchfuehrungenIds) )
				break;
		}
		
		return $durchfuehrungenIds;
	}

	protected function stichw_in_array(&$arr, $id)
	{
		foreach( $arr as $index=>$values ) {
			if( $values['id'] == $id ) {
				return true;
			}
		}
		return false;
	}

	function resetSeeAbove()
	{
		$this->seeAboveArt = '<unset>';
		$this->seeAboveOrt = '<unset>';
	}

	function formatDurchfuehrung(&$db, $kursId, $durchfuehrungId, $details = 0, $anbieterId = 0, $showAllDurchf = 1, $addText='', $addParam = 0)
	{
		global $wisyPortalSpalten;
        global $wisyPortalSpaltenDurchf;
        
        $spalten = $wisyPortalSpalten;
        if($details && $wisyPortalSpaltenDurchf != '') $spalten = $wisyPortalSpaltenDurchf;
        
        if( !is_array($addParam) ) $addParam = array();
        
        $durchfuehrungIds = array();
        
        
        if(is_array($durchfuehrungId) && count($durchfuehrungId) > 0) {
            
            $durchfuehrungIds = $durchfuehrungId;
            $durchfuehrungId = intval($durchfuehrungId[0]);
            
            
            // this ORDER BY puts durchfuehrungen with beginn = '0000-00-00 00:00:00' at the end(!) and sorts everything else by beginn ASC.
            // using -beginn DESC doesn't work b/c '0000-00-00 00:00:00' seems not to be treated as empty
            $db->query("SELECT id, nr, dauer, bemerkungen, preis, teilnehmer, kurstage, sonderpreis, sonderpreistage, plz, strasse,
						 land, stadtteil, preishinweise, beginn, beginnoptionen, ende, ort, tagescode, stunden, zeit_von, zeit_bis, bg_nummer, bg_nummer_count
					  FROM durchfuehrung
							WHERE id IN (".implode(",", $durchfuehrungIds).") ORDER BY  beginn = '0000-00-00 00:00:00', beginn");
            
        } elseif(!is_array($durchfuehrungId)) {
            
            $db->query("SELECT id, nr, dauer, bemerkungen, preis, teilnehmer, kurstage, sonderpreis, sonderpreistage, plz, strasse,
						 land, stadtteil, preishinweise, beginn, beginnoptionen, ende, ort, tagescode, stunden, zeit_von, zeit_bis, bg_nummer, bg_nummer_count
					  FROM durchfuehrung
							WHERE id=$durchfuehrungId");
        }
        if( $db->next_record() )
        {
            $record  = $db->Record;
        }
	    else
	    {
	    	$record = array('preis' => -1); // alle andere felder sind mit "leer" gut bedient
	    }
	    
		// stichwoerter um im sync-process automatisch vergebene Stichwörter ergänzen
		//
		// 2014-11-01 11:26 Anmerkung [1] (vgl. Anmerkungen [1] in wisy-sync-renderer.inc.php):
		// 		da dies ungefähr dasselbe ist, wie in wisy-sync-renderer-class.inc.php könnte man dies evtl. in eine Klasse wie "auto-stichwort" auslagern,
		// 		v.a. da im Grunde an dieser Stelle auch die AutoStichwoerter vergeben werden müssten - und wenn man
		//		ein System etabliert, Stichwörter aus Volltext zu erzeugen, noch mehr.
		//
		//		Es ist also eine Grundsätzliche Frage, wie mit automatisch vergebenen Stichwörtern verfahren werden soll,
		//		vll. ist es doch am Einfachsten, diese beim Abspeichern direkt im Redaktionssystem zu hinterlegen,
		//		auch wenn mal etwas nach core20 folgt ...
		//		dies müsste allerdings mit Jürgen und Monika besprochen werden (bp)
	    global $controlTags;
	    if( $addParam['record']['bu_nummer'] )	{ if(!$this->stichw_in_array($addParam['stichwoerter'], $controlTags['Bildungsurlaub']   )) { $addParam['stichwoerter'][] = array('id'=>$controlTags['Bildungsurlaub']   ); } }
	    if( $addParam['record']['fu_knr'] )		{ if(!$this->stichw_in_array($addParam['stichwoerter'], $controlTags['Fernunterricht'])) { $addParam['stichwoerter'][] = array('id'=>$controlTags['Fernunterricht']); } }
	    // if( $addParam['record']['azwv_knr'] ) 	{ if(!$this->stichw_in_array($addParam['stichwoerter'], $controlTags['Bildungsgutschein'])) { $addParam['stichwoerter'][] = array('id'=>$controlTags['Bildungsgutschein']); } } // nicht autom. Bildungsgutschein
	    
		// termin
		$beginnsql		= $record['beginn'];
		$beginn			= $this->framework->formatDatum($beginnsql);
		$beginnoptionen = $this->formatBeginnoptionen($record['beginnoptionen']);
		$beginnoptionen = (PHP7 ? cs8($beginnoptionen) : $beginnoptionen);
		$endesql		= $record['ende'];
		$ende			= $details? $this->framework->formatDatum($endesql) : '';
		$zeit_von		= $details? $record['zeit_von'] : ''; if( $zeit_von=='00:00' ) $zeit_von = '';
		$zeit_bis		= $details? $record['zeit_bis'] : ''; if( $zeit_bis=='00:00' ) $zeit_bis = '';
		$bg_nummer = PHP7 ? $db->f('bg_nummer') : $db->f8('bg_nummer');
		$bg_nummer_count = PHP7 ? $db->f('bg_nummer_count') : $db->f8('bg_nummer_count');
		
		// termin abgelaufen?
		$termin_abgelaufen = false;
		$heute_datum = strftime("%Y-%m-%d 00:00:00");
		if( $this->stichw_in_array($addParam['stichwoerter'], 315 /*Einstieg bis Kursende möglich?*/ ) 
		 && $endesql > '0000-00-00 00:00:00' )
		{
			if( $endesql < $heute_datum ) {
				$termin_abgelaufen = true;
			}
		}
		else if( $beginnsql > '0000-00-00 00:00:00' ) {
			if( $beginnsql < $heute_datum ) {
				$termin_abgelaufen = true;	
			}
		}

		
		if (($spalten & 2) > 0)
		{
		    echo '    <td class="wisyr_termin" data-title="Termin">';
		    
		    $cell = '';
		    
		    if( $beginn )
		    {
		        if( $termin_abgelaufen ) {
		            $cell .= '<span class="wisyr_termin_datum wisy_datum_abgel" data-title="Datum">';
		        } else {
		            $cell .= '<span class="wisyr_termin_datum" data-title="Datum">';
		        }
		        $cell .= ($ende && $beginn!=$ende)? "$beginn - $ende</span>" : $beginn . '</span>';
		        
		        if( $beginnoptionen ) { $cell .= "<span class=\"wisyr_termin_beginn\">".str_replace(' ', '&nbsp;', $beginnoptionen)."</span>"; }
		    }
		    else if( $beginnoptionen )
		    {
		        $cell .= '<span class="wisyr_termin_optionen">' . str_replace(' ', '&nbsp;', $beginnoptionen) . '&nbsp;</span>';
		    }
		    
		    if( $details && $this->framework->iniRead('details.kurstage', 1)==1 ) {
		        $temp = $this->formatKurstage(intval($record['kurstage']));
		        if( $temp ) {
		            $cell .= "<div class=\"wisyr_art_kurstage\">".$temp." </div>";
		        }
		    }
		    
		    if( $zeit_von && $zeit_bis ) {
		        $cell .= "<span class=\"wisyr_termin_zeit\" data-title=\"Zeit\">{$zeit_von}&nbsp;-&nbsp;{$zeit_bis}&nbsp;Uhr</span>";
		    }
		    else if( $zeit_von ) {
		        $cell .= "<span class=\"wisyr_termin_zeit\">{$zeit_von}&nbsp;Uhr</span>";
		    }
		    
		    if( $addParam['record']['freigeschaltet'] == 4 )
		    {
		        $cell .= '<span class="wisyr_termin_dauerhaft">'.(strlen(trim($cell)) > 1 ? ' ' : '').'dauerhaftes Angebot</span>';
		    }
		    
		    if( $addText ) // z.B. fuer "2 weitere Durchfuehrungen ..."
		    {
		        $cell .= '<span class="wisyr_termin_text">' . $addText . '</span>';
		    }
		    
		    if( $cell == '' )
		    {
		        $cell .= '<span class="wisyr_termin_ka">k. A.</span>';
		    }
		    
		    echo $cell . ' </td>' . "\n";
		}
		
		if (($spalten & 4) > 0)
		{
			// dauer
			echo '    <td class="wisyr_dauer" data-title="Dauer">';
				echo $this->formatDauer($record['dauer'], $record['stunden'], '%1 <span class="wisyr_dauer_detail">(%2)</span>');
			echo ' </td>' . "\n";
		}
		
		if (($spalten & 8) > 0)
		{
		    $cell = '';
		    
		    // art-spalte: tagescode und img.tag
		    $dfStichw = $addParam['stichwoerter'];
		    $dfStichw[] = array('id'=>'tc'.$record['tagescode']);
		    
		    $cell .= $this->formatArtSpalte($dfStichw, $details);
		    
		    if( $cell == $this->seeAboveArt && $details ) {
		        $cell = '<div class="noprint">'.$cell.'</div><span class="printonly">s.o.</span>';
		    }
		    else {
		        $this->seeAboveArt = $cell;
		    }
		    
		    // tagescode / bildungsurlaub
		    echo '    <td class="wisyr_art" data-title="'.($cell ? 'Art' : '').'">';
		    echo $cell;
		    echo ' </td>' . "\n";
		}
		
		if (($spalten & 16) > 0)
		{
		    // preis
		    echo '    <td class="wisyr_preis" data-title="Preis">';
		    
		    $temp = $this->formatPreis($record['preis'],
		        $record['sonderpreis'], $record['sonderpreistage'],
		        $record['beginn'], $details? $record['preishinweise'] : '',
		        true, // format as HTML
		        array(
		            'showDetails'=>$details,
		            'stichwoerter'=>$addParam['stichwoerter']
		        )
		        );
		    
		    global $controlTags;
		    $freeFinancialAid = (	 $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Bildungsgutschein'])
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['DeuFoeV'])
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Umschulung'])
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Orientierungskurs'])
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Aktivierungsgutschein'] )
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Integrationskurs'] )
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Integrationskurs (zu speziellem Förderbedarf)'] )
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Integrationskurs (Intensivkurs)'] )
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Integrationskurs (mit Alphabetisierung)'] )
		        || $this->stichw_in_array($addParam['stichwoerter'], $controlTags['Integrationskurs für Zweitschriftlernende'] )
		        );
		    
		    //  && (is_array($preishinweise_arr) && !in_array("kostenlos", $preishinweise_arr))
		    // Preis komplex: $this->stichw_in_array($addParam['stichwoerter'], 849451)
		    if( ($temp == "" || $temp == "k.A." || $temp == "k. A.") && (strlen($record['preishinweise']) > 3 && !$freeFinancialAid) ) {
		        echo "<small>s.&nbsp;Preishinw.</small>";
		    }
		    elseif( ($temp == "" || $temp == "k.A." || $temp == "k. A.") && $freeFinancialAid && stripos($temp, 'kostenlos') === FALSE) {
		        echo "<small>kostenlos&nbsp;b.&nbsp;F&ouml;rderung</small>";
		    } elseif( !($temp == "" || $temp == "k.A." || $temp == "k. A.") && $freeFinancialAid && stripos($temp, 'kostenlos') === FALSE) {
		        /* Doesnt work well to differentiate:
		         * if($this->stichw_in_array($addParam['stichwoerter'], $controlTags['Bildungsgutschein']))
		         echo $this->shy($temp)."<br><small>kostenlos&nbsp;per&nbsp;Bildungsgutschein</small>";
		         elseif($this->stichw_in_array($addParam['stichwoerter'], $controlTags['Umschulung']))
		         echo $this->shy($temp)."<br><small>kostenlos&nbsp;durch&nbsp;Umschulung</small>";
		         elseif($this->stichw_in_array($addParam['stichwoerter'], $controlTags['Aktivierungsgutschein']))
		         echo $this->shy($temp)."<br><small>kostenlos&nbsp;als&nbsp;Aktivierungsma&szlig;nahme</small>";
		         else */
		         echo $this->shy($temp)."<br><small>kostenlos&nbsp;b.&nbsp;F&ouml;rderung</small>";
		    } else
		        echo $this->shy($temp);
		        
		    echo ' </td>' . "\n";
		}
		
		if (($spalten & 32) > 0)
		{
			// ort
		    $multiple_orte = count($durchfuehrungIds) > 1 ? "multiple" : false;
		    echo '    <td class="wisyr_ort '.$multiple_orte.'" data-title="Ort">';
			
			// get ort
		    $strasse	= str_replace(" ", "&nbsp;", cs8($record['strasse']));
			$plz		= $record['plz'];
			$ort		= htmlentities(cs8($record['ort'])); // hier wird noch der Stadtteil angehaengt
			$stadt		= $ort;
			$stadtteil	= cs8($record['stadtteil']);
			
			$exclude_ort = trim($this->framework->iniRead('search.hide.ort', ''));
			if($stadtteil && $exclude_ort && stripos($ort, $exclude_ort) !== FALSE && $this->framework->getPageType() == "suche") {
			    $ort = str_replace($exclude_ort, "", $ort);
			    $stadt = str_replace($exclude_ort, "", $stadt);
			}
			
			$land		= cs8($record['land']);
			if( $ort && $stadtteil ) {
				if( strpos($ort, $stadtteil)===false ) {
				    $ort = $ort . ' - ' . $stadtteil;
				}
				else {
					$ort = $ort;
				}
			}
			else if( $ort ) {
				$ort = $ort;
			}
			else if( $stadtteil ) {
				$ort = $stadtteil;
				$stadt = $stadtteil;
			}
			else {
				$ort = '';
			}
			
			if( $this->framework->getParam('order') == 'o' ) {
			    
			    $strasse_a = array();
			    $ort_a = array();
			    $plz_a = array();
			    $stadt_a = array();
			    $stadtteil_a = array();
			    
			    for($i = 1; $i < count($durchfuehrungIds); $i++) {
			        $db->next_record();
			        $record = $db->Record;
			        
			        $strasse_a[$i]	= cs8($record['strasse']);
			        $plz_a[$i]		= $record['plz'];
			        $ort_a[$i]		= cs8($record['ort']); // hier wird noch der Stadtteil angehaengt
			        
			        $stadt_a[$i]	= $ort_a[$i];
			        $stadtteil_a[$i]	= cs8($record['stadtteil']);
			        
			        $exclude_ort = trim($this->framework->iniRead('search.hide.ort', ''));
			        if($stadtteil_a[$i] && $exclude_ort && stripos($ort_a[$i], $exclude_ort) !== FALSE && $this->framework->getPageType() == "suche") {
			            $ort_a[$i] = str_replace($exclude_ort, "", $ort_a[$i]);
			            $stadt_a[$i] = str_replace($exclude_ort, "", $stadt_a[$i]);
			        }
			        
			        $land_a[$i]		= cs8($record['land']);
			        if( $ort_a[$i] && $stadtteil_a[$i] ) {
			            if( strpos($ort_a[$i], $stadtteil_a[$i])===false ) {
			                $ort_a[$i] = $ort_a[$i] . ' - ' . $stadtteil_a[$i];
			            }
			            else {
			                $ort_a[$i] = $ort_a[$i];
			            }
			        }
			        else if( $ort_a[$i] ) {
			            $ort_a[$i] = $ort_a[$i];
			        }
			        else if( $stadtteil_a[$i] ) {
			            $ort_a[$i] = $stadtteil_a[$i];
			            $stadt_a[$i] = $stadtteil_a[$i];
			        }
			        else {
			            $ort_a[$i] = '';
			        }
			    }
			    
			    $ort_a = array_unique($ort_a, SORT_STRING); // make sure each place only once in Array
			    
			    // if left places happen to mach place from first DF eliminate it here in add. array to not output redundand event venues
			    $key = array_search($ort, $ort_a);
			    if($key !== FALSE)
			        unset($ort_a[$key]);
			        
			        // list event venues comma separated
			        $ort .= (count($ort_a) ? "," : "")."<br>".implode(",<br>", $ort_a);
			        
			        for($i = 0; $i < count($durchfuehrungIds); $i++) {
			            $db->prev_record();
			        }
			        
			}
			
			if( is_object($this->framework->map) )
			{
				$this->framework->map->addPoint2($record, $durchfuehrungId);
			}
			
			
			$map_strasse = urlencode(PHP7 ? utf8_encode(html_entity_decode($strasse)) : html_entity_decode($strasse));
			$map_plz = urlencode(PHP7 ? utf8_encode(html_entity_decode($plz)) : html_entity_decode($plz));
			$map_ort = urlencode(PHP7 ? utf8_encode(html_entity_decode($ort)) : html_entity_decode($ort));
			$map_land = urlencode(PHP7 ? utf8_encode(html_entity_decode($land)) : html_entity_decode($land));
			
			$map_URL = 'https://maps.google.com/?q=' . $map_strasse . ',%20' . $map_plz . '%20' . $map_ort . ($map_land ? ', ' . $map_land : '');
			
			global $nonvenues;
			$nonvenue = false;
			foreach($nonvenues AS $nonvenue_pattern) {
			    if( preg_match("/".$nonvenue_pattern."/i", $ort) )
			        $nonvenue = true;
			    if( preg_match("/".$nonvenue_pattern."/i", $strasse) )
			        $nonvenue = true;
			}
			
			if( $details )
			{
			    $cell = '';
			    
			    if( $strasse && !$nonvenue ) {
			        $cell .=  '<a title="Adresse in Google Maps ansehen" href="' . $map_URL . '" target="_blank" rel="noopener noreferrer">' . $strasse . '</a>';
			    } elseif( $strasse ) {
			        $cell .=  $strasse;
			    }
			    
			    if( $ort && !$nonvenue ) {
			        $cell .= $cell? '<br />' : '';
			        $cell .= '<a title="Adresse in Google Maps ansehen" href="' . $map_URL . '" target="_blank" rel="noopener noreferrer">' . "$plz $ort" . '</a>';
			    } elseif ( $ort ) {
			        $cell .= $cell? '<br />' : '';
			        $cell .= "$plz $ort";
			    }
			    
			    if( $land ) {
					$cell .= $cell? '<br />' : '';
					$cell .= '<i>' . $land . '</i>';
				}
				
				if( strip_tags($cell) == $this->seeAboveOrt && $details ) {
					echo '<div class="noprint">'.$cell.'</div><span class="printonly">s.o.</span>';
				}
				else if( $cell ) {
					echo $cell;
					$this->seeAboveOrt = strip_tags($cell); // ignore tags as there are many remarks differung only in the Link-URLs - which are not visible in print
				}
				else {
					echo 'k. A.';
					$this->seeAboveOrt = '<unset>';
				}
			}
			else
			{
				echo $ort? $ort : 'k. A.';
			}
			
			echo ' </td>' . "\n";
		}
		
		if (($spalten & 64) > 0)
		{
	
			// nr
			echo '    <td class="wisyr_nr" data-title="Nr">';
			$nr = $record['nr'];
			$nr = cs8($nr);
			echo $nr? htmlentities($nr) : 'k. A.';
			echo ' </td>' . "\n";
		}
		
		if (($spalten & 128) > 0)
		{
		    // maxTN, Bemerkungen
		    if($details)
		    {
		        echo '    <td class="wisyr_bemerkungen" data-title="Bemerkungen">';
		        if( $record['teilnehmer'] ) echo '<p class="wisyr_art_teilnehmer">max. ' . intval($record['teilnehmer']) . ' Teilnehmer</p>';
		        $wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
		        $bemerkungen = $record['bemerkungen'];
		        $bemerkungen = str_replace(chr(128), "&euro;", $bemerkungen); // str_replace(chr(0xE2).chr(0x82).chr(0xAC), "&euro;",
		        echo cs8($wiki2html->run($bemerkungen));
		        echo ' </td>' . "\n";
		    } 
		}
	}
};


