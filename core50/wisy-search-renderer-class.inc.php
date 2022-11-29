<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_SEARCH_RENDERER_CLASS
{
	var $framework;
	var $unsecureOnly = false;

	function __construct(&$framework)
	{
		// constructor
		$this->framework =& $framework;
		$this->rows = 20;
	}

	function pageSelLink($baseUrl, $currRowsPerPage, $currPageNumber, $hilite)
	{
		$ret = $hilite? '<strong class="wisy_paginate_pagelink">' : '<a class="wisy_paginate_pagelink" href="' . htmlentities($baseUrl) . intval($currPageNumber*$currRowsPerPage) . '">';
			$ret .= intval($currPageNumber+1);
		$ret .= $hilite? '</strong> ' : '</a> ';
		return $ret;
	}
	
	function pageSelCurrentpage($currOffset, $currRowsPerPage)
	{
		// find out the current page number (the current page number is zero-based)
		return $currPageNumber = intval($currOffset / $currRowsPerPage);
	}
	
	function pageSelMaxpages($totalRows, $currRowsPerPage)
	{
		// find out the max. page page number (also zero-based)
		$maxPageNumber = intval($totalRows / $currRowsPerPage);
		if( intval($totalRows / $currRowsPerPage) == $totalRows / $currRowsPerPage ) {
			$maxPageNumber--;
		}
		return $maxPageNumber;
	}
	
	
	function pageSel($baseUrl, $currRowsPerPage, $currOffset, $totalRows)
	{
		$page_sel_surround = 3;
		$currPageNumber = $this->pageSelCurrentpage($currOffset, $currRowsPerPage);
		$maxPageNumber = $this->pageSelMaxpages($totalRows, $currRowsPerPage);
	
		// find out the first/last page number surrounding the current page (zero-based)
		$firstPageNumber = $currPageNumber-$page_sel_surround;
		if( $firstPageNumber < $page_sel_surround ) {
			$firstPageNumber = 0;
		}
		
		$lastPageNumber = $currPageNumber+$page_sel_surround;
		if( $lastPageNumber > ($maxPageNumber-$page_sel_surround) ) {
			$lastPageNumber = $maxPageNumber;
		}
	
		// get the options string
		$options = '';
		if( $firstPageNumber != 0 ) {
			$options .= $this->pageSelLink($baseUrl, $currRowsPerPage, 0, 0) . '... ';
		}
		
		for( $i = $firstPageNumber; $i<=$lastPageNumber; $i++ ) {
			$options .= $this->pageSelLink($baseUrl, $currRowsPerPage, $i, $i==$currPageNumber? 1 : 0);
		}
		
		if( $lastPageNumber != $maxPageNumber ) {
			$options .= '... ' . $this->pageSelLink($baseUrl, $currRowsPerPage, $maxPageNumber, 0);
		}
	
		return trim($options);
	}
	
	function renderColumnTitle($title, $sollOrder, $istOrder, $info=0)
	{
	    // #richtext
	    $richtext = (intval(trim($this->framework->iniRead('meta.richtext'))) === 1);
	    $headattribs = ($richtext) ? ' content="'.$title.'"' : '';
	    
		// Add column title class for use in responsive CSS
		echo '    <th class="wisyr_'. $this->framework->cleanClassname($title) .'" '.$headattribs.'>'; // #richtext
			
			if( $sollOrder )
			{
				if( $istOrder{0} == $sollOrder ) 
				{
					$dir = $istOrder{1}=='d'? 'd' /*desc*/ : 'a' /*asc*/;
					$newOrder = $sollOrder . ($dir=='d'? '' : 'd');
					$icon = $dir=='d'? ' &#9660;' /*v*/ : ' &#9650;' /*^*/;
				}
				else
				{
					$newOrder = $sollOrder;
					$icon = '';
				}
				
				echo '<a href="' . htmlspecialchars($this->framework->getUrl('search', array('q'=>$this->framework->getParam('q', ''), 'order'=>$newOrder))) . '" title="Liste nach diesem Kriterium sortieren" class="wisy_orderby">';
			}
			
			echo $title;
			
			if( $sollOrder )
			{			
				echo $icon . '</a>';
			}
			
			if( $info > 0 )
			{
				echo ' <a href="' . htmlspecialchars($this->framework->getHelpUrl($info)) . '" title="Hilfe" class="wisy_help">i</a>';
			}
		echo '</th>' . "\n";
	}
	
	function renderPagination($prevurl, $nexturl, $pagesel, $currRowsPerPage, $currOffset, $totalRows, $extraclass)
	{
		$currentPage = $this->pageSelCurrentpage($currOffset, $currRowsPerPage) + 1;
		$maxPages = $this->pageSelMaxpages($totalRows, $currRowsPerPage) + 1;
		echo ' <span class="wisy_paginate ' . $extraclass . '">';
			echo '<span class="wisy_paginate_seitevon">Seite ' . $currentPage . ' von ' . $maxPages . '</span>';
			echo '<span class="wisy_paginate_text">Gehe zu Seite</span>';
		
			if( $prevurl ) {
				echo " <a class=\"wisy_paginate_prev\" href=\"" . htmlspecialchars($prevurl) . "\">&laquo;</a> ";
			}
	
			echo $pagesel;
	
			if( $nexturl ) {
				echo " <a class=\"wisy_paginate_next\" href=\"" . htmlspecialchars($nexturl) . "\">&raquo;</a>";
			}
		echo '</span>' . "\n";
	}
	
	protected function renderAnbieterCell2(&$db2, $record, $param)
	{
		$currAnbieterId = $record['id'];
		$anbieterName = $record['suchname'];
		$pruefsiegel_seit = $record['pruefsiegel_seit'];
		$anspr_tel = $record['anspr_tel'];

		echo '    <td class="wisy_anbieter wisyr_anbieter" data-title="Anbieter">';
			echo $this->framework->getSeals($db2, array('anbieterId'=>$currAnbieterId, 'seit'=>$pruefsiegel_seit, 'size'=>'small'));
			
			if( $anbieterName )
			{
				$aparam = array('id'=>$currAnbieterId, 'q'=>$param['q']);
				if( $param['promoted'] )
				{
					$aparam['promoted'] = intval($param['kurs_id']);
					echo '<span class="wisy_promoted_prefix">Anzeige von:</span> ';
				}
			
				if( $param['clickableName'] ) echo '<a href="'.$this->framework->getUrl('a', $aparam).'">';
					
					if( $param['addIcon'] ) {
						if( $record['typ'] == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratungsstelle<span class="dp">:</span></span> ';
					}
					
					echo htmlspecialchars($this->framework->encode_windows_chars(cs8($anbieterName)));
					
				if( $param['clickableName'] ) echo '</a>';

				if( $param['addPhone'] && $anspr_tel )
				{
					// $anspr_tel = str_replace(' ', '', $anspr_tel); // macht Aerger, da in den Telefonnummern teilw. Erklaerungen/Preise mitstehen. Auskommentiert am 5.9.2008 (bp)
					$anspr_tel = str_replace('/', ' / ', $anspr_tel);
					echo '<br><span class="wisyr_anbieter_telefon"> ' . htmlspecialchars(cs8($anspr_tel)) . '</span>';
				}
				
				if( !$param['clickableName'] )  echo '<span class="wisyr_anbieter_profil"> - <a href="'.$this->framework->getUrl('a', $aparam).'">Anbieterprofil...</a></span>';
			}
			else
			{
				echo 'k. A.';
			}
		echo '</td>' . "\n";
		
		return $anbieterName; // #richtext
	}
	
	function renderKursRecords(&$db, &$records, &$recordsToSkip, $param)
	{
		global $wisyPortalSpalten;

		$loggedInAnbieterId = $this->framework->getEditAnbieterId();

		// build skip hash
		$recordsToSkipHash = array();
		if( is_array($recordsToSkip['records']) )
		{
			reset($recordsToSkip['records']);
			foreach($recordsToSkip['records'] as $i => $record)
			{
				$recordsToSkipHash[ $record['id'] ] = true;
			}
		}

		// load all latlng values
		$distances = array();
		if( $this->hasDistanceColumn )
		{
			$ids = '';
			reset($records['records']);
			foreach($records['records'] as $i => $record)
			{
				$ids .= ($ids==''? '' : ', ') . $record['id'];
			}
			
			if ($ids != '' )
			{
				$x1 = $this->baseLng *  71460.0;
				$y1 = $this->baseLat * 111320.0;
				$sql = "SELECT kurs_id, lat, lng FROM x_kurse_latlng WHERE kurs_id IN ($ids)";
				$db->query($sql);
				while( $db->next_record() )
				{
					$kurs_id = intval($db->fcs8('kurs_id'));
					$x2 = (floatval($db->fcs8('lng')) / 1000000) *  71460.0;
					$y2 = (floatval($db->fcs8('lat')) / 1000000) * 111320.0;

					// calculate the distance between the points ($x1/$y1) and ($x2/$y2)
					// d = sqrt( (x1-x2)^2 + (y1-y2)^2 )
					$dx = $x1 - $x2; if( $dx < 0 ) $dx *= -1;
					$dy = $y1 - $y2; if( $dy < 0 ) $dy *= -1;
					$d = sqrt( $dx*$dx + $dy*$dy ); // $d ist nun die Entfernung in Metern ;-)
					
					// remember the smallest distance
					if( !isset($distances[ $kurs_id ]) || $distances[ $kurs_id ] > $d )
					{
						$distances[ $kurs_id ] = $d;
					}
				}
			}
		}

		// go through result
		$durchfClass =& createWisyObject('WISY_DURCHF_CLASS', $this->framework);
		
		$kursAnalyzer =& createWisyObject('WISY_KURS_ANALYZER_CLASS', $this->framework);
		
		$fav_use = $this->framework->iniRead('fav.use', 0);
		
		$rows = 0;
		
		$tag_cloud = array();
		
		reset($records['records']);
		foreach($records['records'] as $i => $record)
		{	
			// get kurs basics
			$currKursId = $record['id'];
			$currAnbieterId = $record['anbieter'];
			$currKursFreigeschaltet = $record['freigeschaltet'];
			$durchfuehrungenIds = $durchfClass->getDurchfuehrungIds($db, $currKursId);

			// record already promoted? if so, skip the normal row
			if( $recordsToSkipHash[ $currKursId ] )
				continue;

			// dump kurs
			$rows ++;
			
			if( $param['promoted'] )
				$class = ' class="wisy_promoted"';
			else
				$class = ($rows%2)==0? ' class="wisy_even"' : '';
			
			echo "  <tr$class>\n";

			// SPALTE: kurstitel
			$db->query("SELECT id, suchname, pruefsiegel_seit, anspr_tel, typ, freigeschaltet FROM anbieter WHERE id=$currAnbieterId");
			$db->next_record();
			$anbieter_record = $db->Record;
			
			// continue if Anbieter disabled!
			if($anbieter_record['freigeschaltet'] == 2)
			    continue;
			
				echo '    <td class="wisy_kurstitel wisyr_angebot" data-title="Angebot">';
					$aparam = array('id'=>$currKursId, 'q'=>$param['q']);
					if( $param['promoted'] ) {$aparam['promoted'] = $currKursId;}
					
					$aclass = '';
					if( $fav_use ) {
						$aclass = ' class="fav_add" data-favid="'.$currKursId.'"';
					}
					
					echo '<a href="' .$this->framework->getUrl('k', $aparam). "\"{$aclass}>";
						
							if( $currKursFreigeschaltet == 0 ) { echo '<em>Kurs in Vorbereitung:</em><br />'; }
							if( $currKursFreigeschaltet == 2 ) { echo '<em>Gesperrt:</em><br />'; }
							if( $currKursFreigeschaltet == 3 ) { echo '<em>Abgelaufen:</em><br />'; }
							
							if( $anbieter_record['typ'] == 2 ) echo '<span class="wisy_icon_beratungsstelle">Beratung<span class="dp">:</span></span> ';
							
							if($this->framework->iniRead('label.abschluss', 0) && count($kursAnalyzer->loadKeywordsAbschluss($db, 'kurse', $currKursId))) echo '<span class="wisy_icon_abschluss">Abschluss<span class="dp">:</span></span> ';
							if($this->framework->iniRead('label.zertifikat', 0) && count($kursAnalyzer->loadKeywordsZertifikat($db, 'kurse', $currKursId))) echo '<span class="wisy_icon_zertifikat">Zertifikat<span class="dp">:</span></span> ';
						
							echo htmlspecialchars($this->framework->encode_windows_chars(cs8($record['titel'])));
						
					echo '</a>';
					if( $loggedInAnbieterId == $currAnbieterId )
					{
						$vollst = $record['vollstaendigkeit'];
						if( $vollst>=1 ) {
							echo " <span class=\"wisy_editvollstcol\" title=\"Vollst&auml;ndigkeit der Kursdaten, bearbeiten Sie den Kurs, um die Vollst&auml;ndigkeit zu erh&ouml;hen\">($vollst% vollst&auml;ndig)</span>";
						}
						echo '<br><span class="wisy_edittoolbar"><a href="'.$this->framework->getUrl('edit', array('action'=>'ek', 'id'=>$currKursId)).'">Bearbeiten</a></span>';
					}
				echo '</td>' . "\n";

				if (($wisyPortalSpalten & 1) > 0)
				{
					// SPALTE: anbieter
					// #richtext
				    $anbieterName = $this->renderAnbieterCell2($db, $anbieter_record, array('q'=>$param['q'], 'addPhone'=>true, 'promoted'=>$param['promoted'], 'kurs_id'=>$currKursId));
				}
				
				// SPALTEN: durchfuehrung
				$addText = '';
				if( sizeof((array) $durchfuehrungenIds) > 1 )
				{
				    $addText = ' <span class="wisyr_termin_weitere"><a href="' .$this->framework->getUrl('k', $aparam). '">';
				    $temp = sizeof((array) $durchfuehrungenIds) - 1;
				    $addText .= $temp==1? "$temp<span> weiterer...</span>" : "$temp<span> weitere...</span>";
				    $addText .= '</a></span>';
				}
				
				$tags = $this->framework->loadStichwoerter($db, 'kurse', $currKursId);
				array_push($tag_cloud, $tags);
				$durchfClass->formatDurchfuehrung($db, $currKursId, intval($durchfuehrungenIds[0]), 0, 0, 1, $addText, array('record'=>$record, 'stichwoerter'=>$tags));
				
				// SPALTE: Entfernung
				if( $this->hasDistanceColumn )
				{
					$cell = '<td class="wisyr_entfernung" data-title="Entfernung">';
					if( isset($distances[$currKursId]) )
					{
						$meters = $distances[$currKursId];
						if( $meters > 1500 )
						{
							// 1 km, 2 km etc.
							$km = intval(($meters+500)/1000); if( $km < 1 ) $km = 1;
							$cell .= '~' . $km . ' km';
						}
						else if( $meters > 550 )
						{
							// 100 m, 200 m etc.
							$hundreds = intval(($meters+50)/100); if( $hundreds < 1 ) $hundreds = 1;
							$cell .= '~' . $hundreds . '00 m';
						}
						else
						{
							$cell .= '&lt;500 m';
						}
					}
					else
					{
						$cell .= 'k. A.';
					}
					$cell .= '</td>';
					echo $cell;
					
				}
				
			echo '  </tr>' . "\n";
		}
		
		return $tag_cloud;
	}
	
	function formatItem($tag_name, $tag_descr, $tag_type, $tag_help, $tag_freq, $addparam=0)
	{
		if( !is_array($addparam) ) $addparam = array();
		
		/* see also (***) in the JavaScript part*/
		$row_class   = 'ac_normal';
		$row_prefix  = '';
		$row_preposition = '';
		$row_postfix = '';
		
		/* base type */
		     if( $tag_type &   1 )	{ $row_class = "ac_abschluss";		      $row_preposition = ' zum '; $row_postfix = '<b>Abschluss</b>'; }
		else if( $tag_type &   2 )	{ $row_class = "ac_foerderung";		      $row_preposition = ' zur '; $row_postfix = 'F&ouml;rderung'; }
		else if( $tag_type &   4 )	{ $row_class = "ac_qualitaetszertifikat"; $row_preposition = ' zum '; $row_postfix = 'Qualit&auml;tszertifikat'; }
		else if( $tag_type &   8 )	{ $row_class = "ac_zielgruppe";		      $row_preposition = ' zur '; $row_postfix = 'Zielgruppe'; }
		else if( $tag_type &  16 )	{ $row_class = "ac_abschlussart";		  $row_preposition = ' zur '; $row_postfix = 'Abschlussart'; }
		else if( $tag_type & 128 )	{ $row_class = "ac_thema";		 		  $row_preposition = ' zum '; $row_postfix = 'Thema'; }
		else if( $tag_type & 256 )	{ $row_class = "ac_anbieter";		     
											  if( $tag_type &  0x10000 )    { $row_preposition = ' zum '; $row_postfix = 'Trainer'; }
										 else if( $tag_type &  0x20000 )    { $row_preposition = ' zur '; $row_postfix = 'Beratungsstelle'; }
										 else if( $tag_type & 0x400000 )    { $row_preposition = ' zum '; $row_postfix = 'Anbieterverweis'; }
										 else							    { $row_preposition = ' zum '; $row_postfix = 'Anbieter'; }
								    }
		else if( $tag_type & 512 )	{ $row_class = "ac_ort";                  $row_preposition = ' zum '; $row_postfix = 'Ort'; }
		else if( $tag_type & 1024 )	{ $row_class = "ac_sonstigesmerkmal";     $row_preposition = ' zum '; $row_postfix = 'sonstigen Merkmal'; }
		else if( $tag_type & 32768 ){ $row_class = "ac_unterrichtsart";       $row_preposition = ' zur '; $row_postfix = 'Unterrichtsart'; }
		else if( $tag_type & 65536 ){ $row_class = "ac_zertifikat";           $row_preposition = ' zum '; $row_postfix = 'Zertifikat'; }
	
		if( $addparam['hidetagtypestr'] ) {
			$row_preposition = '';
			$row_postfix = '';
		}

		/* frequency, end base type */ 
		if( $tag_freq > 0 )
		{
			$row_postfix = ($tag_freq==1? '1 Kurs' : "$tag_freq Kurse") . $row_preposition . $row_postfix;
		}
		
		if( $tag_descr ) 
		{
			$row_postfix = $tag_descr . ', ' . $row_postfix;
		}
		
		if( $row_postfix != '' )
		{
		    $row_postfix = ' <span class="ac_tag_type">(' . htmlentities($row_postfix) . ')</span> ';
		}
	
		/* additional flags */
		if( $tag_type & 0x10000000 )
		{
			$row_prefix = '&nbsp; &nbsp; &nbsp; &nbsp; &#8594; ';
			$row_class .= " ac_indent";
		}	
		else if( $tag_type & 0x20000000 )
		{
			$row_prefix = 'Meinten Sie: ';
		}
		else
		{
			$row_prefix = ''; //13.05.2014 was: 'Suche nach '
		}
		
		
		/* help link */
		if( $tag_help != 0 )
		{
			$row_postfix .=
			 " <a class=\"wisy_help\" href=\"" . $this->framework->getUrl('g', array('id'=>$tag_help, 'q'=>$tag_name)) . "\" title=\"Ratgeber\">&nbsp;i&nbsp;</a>";
		}
		
		return '<span class="' .$row_class. '">' .
		  		$row_prefix . ' <a href="' . $this->framework->getUrl('search', array('q'=>$addparam['qprefix'].$tag_name)) . ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '').($this->framework->force ? '&force='.$this->framework->force : '') . '">' . htmlspecialchars($tag_name) . '</a> ' . $row_postfix .
			   '</span>';
	}
	
	function formatItem_v2($tag_name, $tag_descr, $tag_type, $tag_help, $tag_freq, $tag_anbieter_id=false, $tag_groups='', $tr_class='', $queryString='')
	{
		/* see also (***) in the JavaScript part*/
		$row_class   = 'ac_normal';
		$row_type  = 'Lernziel';
		$row_count = '';
		$row_count_prefix = ($tag_freq == 1) ? ' Kurs zum' : ' Kurse zum';
		$row_info = '';
		$row_prefix = '';
		$row_postfix = '';
		$row_groups = '';
	
		/* base type */
		     if( $tag_type &   1 ) { $row_class = "ac_abschluss";		     $row_type = 'Abschluss'; }
		else if( $tag_type &   2 ) { $row_class = "ac_foerderung";		     $row_type = 'F&ouml;rderung'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs zur' : ' Kurse zur'; }
		else if( $tag_type &   4 ) { $row_class = "ac_qualitaetszertifikat"; $row_type = 'Qualit&auml;tsmerkmal'; }
		else if( $tag_type &   8 ) { $row_class = "ac_zielgruppe";		     $row_type = 'Zielgruppe'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs zur' : ' Kurse zur'; }
		else if( $tag_type &  16 ) { $row_class = "ac_abschlussart";		 $row_type = 'Abschlussart'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs zur' : ' Kurse zur'; }
		else if( $tag_type &  64 ) { $row_class = "ac_synonym";				 $row_type = 'Verweis'; }
		else if( $tag_type & 128 ) { $row_class = "ac_thema";		 		 $row_type = 'Thema'; }
		else if( $tag_type & 256 ) { $row_class = "ac_anbieter";		     
									      if( $tag_type &  0x20000 ) { $row_type = 'Beratungsstelle'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs von der' : ' Kurse von der'; }
									 else if( $tag_type & 0x400000 ) { $row_type = 'Tr&auml;gerverweis'; }
									 else							 { $row_type = 'Tr&auml;ger'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs vom' : ' Kurse vom'; }
								   }
		else if( $tag_type & 512 ) { $row_class = "ac_ort";                  $row_type = 'Kursort'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs am' : ' Kurse am'; }
		else if( $tag_type & 1024) { $row_class = "ac_merkmal";			 	 $row_type = 'Kursmerkmal'; }
		else if( $tag_type & 32768){ $row_class = "ac_unterrichtsart";		 $row_type = 'Unterrichtsart'; $row_count_prefix = ($tag_freq == 1) ? ' Kurs zur' : ' Kurse zur'; }
		else if( $tag_type & 65536){ $row_class = "ac_zertifikat";           $row_type = 'Zertifikat'; }

		if( $tag_descr ) $row_postfix .= ' <span class="ac_tag_type">('. htmlentities($tag_descr) .')</span>';
		
	
		if( $tag_freq > 0 ) {
			$row_count = $tag_freq;
			if($row_count_prefix == '') {
				$row_count .= ($tag_freq == 1) ? ' Kurs' : ' Kurse';
			} else {
				$row_count .= $row_count_prefix;
			}
		}

		/* additional flags */
		if( $tag_type & 0x10000000 )
		{
			$row_prefix = '<span class="wisyr_indent">&#8594;</span> ';
			$row_class .= " ac_indent";
		}	
		else if( $tag_type & 0x20000000 )
		{
			$row_prefix = 'Meinten Sie: ';
		}
		else
		{
			$row_prefix = ''; //13.05.2014 was: 'Suche nach '
		}
	
		if( $tag_groups ) $row_groups = implode('<br />', $tag_groups);
	
		if( $tag_help )
		{
			$row_info = '<a href="' . $this->framework->getUrl('g', array('id'=>$tag_help, 'q'=>$tag_name)) . '">Zeige Erkl&auml;rung</a>';
		} else if( $tag_type & 256 && $tag_anbieter_id ) {
			$row_info = '<a href="' . $this->framework->getUrl('a', array('id'=>$tag_anbieter_id)) . '">Zeige Tr&auml;gerprofil</a>';
		}
	
		$row_class = $row_class . ' ' . $tr_class;
		
		// highlight search string
		$tag_name = htmlspecialchars($tag_name);
		if($queryString != '') {
			//$tag_name_highlighted = str_ireplace($queryString, "<strong>$queryString</strong>", $tag_name);
			$tag_name_highlighted = preg_replace("/".preg_quote($queryString, "/")."/i", "<em>$0</em>", $tag_name);
		} else {
			$tag_name_highlighted = $tag_name;
		}
	
		return '<tr class="' .$row_class. '">' .
					'<td class="wisyr_tag_name" data-title="Rechercheziele">'. $row_prefix .'<a href="' . $this->framework->getUrl('search', array('q'=>$tag_name)) . '">' . $tag_name_highlighted . '</a>'. $row_postfix .'<span class="tag_count">'. $row_count .'</span></td>' . 
					'<td class="wisyr_tag_type" data-title="Kategorie">'. $row_type .'</td>' .
					'<td class="wisyr_tag_groups" data-title="Oberbegriffe">'. $row_groups .'</td>' . 
					'<td class="wisyr_tag_info" data-title="Zusatzinfo">'. $row_info . '</td>' .
			   '</tr>';
	}
	
	function renderTagliste($queryString)
	{
		$tagsuggestor =& createWisyObject('WISY_TAGSUGGESTOR_CLASS', $this->framework);
		$suggestions = $tagsuggestor->suggestTags($queryString);

		if( sizeof((array) $suggestions) )
		{
			if($this->framework->iniRead('search.suggest.v2') == 1)
			{
				echo '<div class="wisyr_list_header"><span class="wisyr_rechercheziele">Gefundene Rechercheziele - verfeinern Sie Ihren Suchauftrag:</span></div>';
				echo '<table class="wisy_list wisy_tagtable">';
				echo '	<thead>';
				echo '		<tr>'.
								'<th class="wisyr_titel"><span class="title">Rechercheziele</span> <span class="tag_count">Angebote dazu</span></th>'.
								'<th class="wisyr_art">Kategorie</th>'.
								'<th class="wisyr_gruppe">Oberbegriffe</th>'.
								'<th class="wisyr_info">Zusatzinfo</th>'.
							'</tr>';
				echo '	</thead>';
				echo '	<tbody>';
				for( $i = 0; $i < sizeof((array) $suggestions); $i++ )
				{
					$tr_class = ($i%2) ? 'ac_even' : 'ac_odd';
					echo $this->formatItem_v2($suggestions[$i]['tag'], $suggestions[$i]['tag_descr'], $suggestions[$i]['tag_type'], intval($suggestions[$i]['tag_help']), intval($suggestions[$i]['tag_freq']), $suggestions[$i]['tag_anbieter_id'], $suggestions[$i]['tag_groups'], $tr_class, $queryString);
				}
				echo '	</tbody>';
				echo '</table>';
			}
			else
			{
				echo '<span class="wisyr_rechercheziele">Gefundene Rechercheziele - verfeinern Sie Ihren Suchauftrag:</span>';
				echo '<ul>';
				    for( $i = 0; $i < sizeof((array) $suggestions); $i++ )
					{
						echo '<li>' . $this->formatItem($suggestions[$i]['tag'], $suggestions[$i]['tag_descr'], $suggestions[$i]['tag_type'], intval($suggestions[$i]['tag_help']), intval($suggestions[$i]['tag_freq'])) . '</li>';
					}
				echo '</ul>';
			}
		}
		else
		{
			echo 'Keine Treffer.';
		}
	}
	
	function renderKursliste(&$searcher, $queryString, $offset)
	{
		global $wisyPortalSpalten;
		
		$richtext = (intval(trim($this->framework->iniRead('meta.richtext'))) === 1); // #richtext
	
		$validOrders = array('a', 'ad', 't', 'td', 'b', 'bd', 'd', 'dd', 'p', 'pd', 'o', 'od', 'creat', 'creatd', 'rand');
		$orderBy = strval( $this->framework->getParam('order') ); if( !in_array($orderBy, $validOrders) ) $orderBy = 'b';

		$info = $searcher->getInfo();
		if( $info['changed_query'] || sizeof((array) $info['suggestions']) )
		{
			echo '<div class="wisy_suggestions">';
				if( $info['changed_query'] )
				{
					echo '<b>Hinweis:</b> Der Suchauftrag wurde abge&auml;ndert in <i><a href="'.$this->framework->getUrl('search', array('q'=>$info['changed_query'])).'">'.htmlspecialchars(cs8($info['changed_query'])).'</a></i>';
					if( sizeof($info['suggestions']) ) 
						echo ' &ndash; ';
				}
				
				if( sizeof($info['suggestions']) ) 
				{
					echo '<span class="wisyr_rechercheziele">Gefundene Rechercheziele - verfeinern Sie Ihren Suchauftrag:</span>';
					echo '<ul>';
						for( $i = 0; $i < sizeof($info['suggestions']); $i++ )
						{
							echo '<li>' . $this->formatItem($info['suggestions'][$i]['tag'], $info['suggestions'][$i]['tag_descr'], $info['suggestions'][$i]['tag_type'], intval($info['suggestions'][$i]['tag_help']), intval($suggestions[$i]['tag_freq'])) . '</li>';
						}
					echo '</ul>';
				}
			echo '</div>';
		}
		
		$sqlCount = $searcher->getKurseCount();
		if( $sqlCount )
		{
			$db = new DB_Admin();
			
			// create get prev / next URLs
			$prevurl = $offset==0? '' : $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset-$this->rows));
			$nexturl = ($offset+$this->rows<$sqlCount)? $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset+$this->rows)) : '';
			if( $prevurl || $nexturl )
			{	
				$param = array('q'=>$queryString);
				if( $orderBy != 'b' ) $param['order'] = $orderBy;
				$param['offset'] = '';
				$pagesel = $this->pageSel($this->framework->getUrl('search', $param), $this->rows, $offset, $sqlCount);
			}
			else
			{
				$pagesel = '';
			}

			// render head
			echo '<div class="wisyr_list_header">';
			echo '<div class="wisyr_listnav">';
			echo '<span class="active tab_kurse">Angebote</span>';
			echo '<a href="' . $baseurl . '?q=' . ($this->changedquery ? urlencode($this->changedquery) : urlencode($queryString)) . '%2C+Zeige:Anbieter' . ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '') . '" class="tab_anbieter">Anbieter</a>';
			echo '</div>';
			echo '<div class="wisyr_filternav';
			
				if( $queryString == '' ) {
					echo '<span class="wisyr_aktuelle_angebote">Aktuelle Angebote</span>';
				}
				else {
					echo '<span class="wisyr_angebote_zum_suchauftrag">';
					
					/* ! actually makes sense: but how? Because of richtext additional output above?
					 if($richtext)
					 echo "&nbsp;"; // notwendig
					 else */
					echo $sqlCount==1? '<span class="wisyr_anzahl_angebote">1 Angebot</span> zum Suchauftrag ' : '<span class="wisyr_anzahl_angebote">' . $sqlCount . ' Angebote</span> zum Suchauftrag ';
					echo '<span class="wisyr_angebote_suchauftrag">"' . htmlspecialchars((trim($queryString, ', '))) . '"</span>';
					echo '<a class="wisyr_anbieter_switch" href="search?q=' . urlencode($queryString) . '%2C+Zeige:Anbieter">Zeige Anbieter</a>';
					echo '</span>';
				}
				
				// Show filter / advanced search
				$DEFAULT_FILTERLINK_HTML= '<a href="filter?q=__Q_URLENCODE__'. ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '')  .'" id="wisy_filterlink">Suche anpassen</a>';
				echo $this->framework->replacePlaceholders($this->framework->iniRead('searcharea.filterlink', $DEFAULT_FILTERLINK_HTML));
				

				if( $pagesel )
				{
					$this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_top');
				}
				
				echo '</div>';
			echo '</div>';
			
			flush();
			
			/* $aggregateOffer = ($richtext) ? 'itemprop="offers" itemscope itemtype="http://schema.org/AggregateOffer"': '';
			 echo '<div '.$aggregateOffer.'>'; */

			// render table start
			echo "\n".'<table class="wisy_list wisyr_kursliste">' . "\n";
			
			// render column titles
			echo '  <thead><tr>' . "\n";
				$colspan = 0;
				$this->hasDistanceColumn = false;
												   {	$this->renderColumnTitle('Angebot',			't', 	$orderBy,	1333);			$colspan++; }
				if (($wisyPortalSpalten & 1)  > 0) {	$this->renderColumnTitle('Anbieter',		'a', 	$orderBy,	311); 			$colspan++; }
				if (($wisyPortalSpalten & 2)  > 0) {	$this->renderColumnTitle('Termin',			'b', 	$orderBy,	308);			$colspan++; }
				if (($wisyPortalSpalten & 4)  > 0) {	$this->renderColumnTitle('Dauer',			'd',	$orderBy,	0);				$colspan++; }
				if (($wisyPortalSpalten & 8)  > 0) {	$this->renderColumnTitle('Art',				'',		$orderBy,	1967);			$colspan++; }
				if (($wisyPortalSpalten & 16) > 0) {	$this->renderColumnTitle('Preis',			'p',	$orderBy,	309);			$colspan++; }
				if (($wisyPortalSpalten & 32) > 0) {	$this->renderColumnTitle('Ort',				'o', 	$orderBy,	1936);			$colspan++; }
				if (($wisyPortalSpalten & 64) > 0) {	$this->renderColumnTitle('Ang.-Nr.',		'', 	$orderBy,	0);				$colspan++; }
				/* if (($wisyPortalSpalten & 128)> 0) { 	$this->renderColumnTitle('Bemerkungen',				'', 	$orderBy,	0);				$colspan++; } */ /* ohne $details=true sinnlos */
				/* if (($wisyPortalSpalten & 256)> 0) { 	$this->renderColumnTitle('BU',				'', 	$orderBy,	0);				$colspan++; } */
				if( $info['lat'] && $info['lng'] ) {    $this->renderColumnTitle('Entfernung',		'', 	$orderBy,	0);				$colspan++; $this->hasDistanceColumn = true; $this->baseLat = $info['lat']; $this->baseLng = $info['lng'];  }
			echo '  </tr></thead>' . "\n";
			
			// render promoted records
			$records2 = array();
			if( !$info['changed_query'] 
			 && $offset == 0 
			 && $this->framework->iniRead('useredit.promote', 0)!=0 )
			{
			    global $wisyPortalId;
			    $searcher2 =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
			    
			    $queryString_db = mysql_escape_mimic($queryString);
			    
			    if($queryString_db != "")
			        $searcher2->prepare($queryString_db . ', schaufenster:' . $wisyPortalId);
			        
			        if( $searcher2->ok() )
				{
					$promoteCnt = $searcher2->getKurseCount();
					if( $promoteCnt > 0 )
					{
						$promoteRows = 3; 
						if( $promoteRows > $promoteCnt ) $promoteRows = $promoteCnt;
						
						$promoteOffset = mt_rand(0, $promoteCnt-$promoteRows);
						
						$records2 = $searcher2->getKurseRecords($promoteOffset, $promoteRows, 'rand');
						$notingToSkip = array();
						
						// render promoted head
						echo '<tr class="wisy_promoted_head"><td colspan="'.$colspan.'">';
							echo 'Schaufenster Weiterbildung ';
							echo '<a href="' .$this->framework->getHelpUrl(3368). '" class="wisy_help" title="Hilfe">i</a>';
						echo '</td></tr>';
						
						// render promoted records
						$this->renderKursRecords($db, $records2, $nothingToSkip, array('q'=>$queryString, 'promoted'=>true));
						
						// log the rendered records
						if( $queryString != '' ) // do not decreease / log on the homepage, see E-Mails/04.11.2010
						{
							$promoter =& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
							$promoter->logPromotedRecordViews($records2, $queryString);
						}
					}
				}
				
			}
			
			// render other records
			$records = $searcher->getKurseRecords($offset, $this->rows, $orderBy);
			$tags_heap = $this->renderKursRecords($db, $records, $records2 /*recordsToSkip*/, array('q'=>$queryString));
		
			// main table end
			echo '</table>' . "\n\n";
			
			/* // #richtext
				if($richtext) {
					// sort($durchfClass->preise);
					// echo '<meta itemprop="lowprice" content="'.$this->framework->preisUS($durchfClass->preise[0]).'">';
				// 	echo '<meta itemprop="highprice" content="'.$this->framework->preisUS($durchfClass->preise[sizeof((array) $durchfClass->preise)-1]).'">';
					
					echo '<meta itemprop="priceCurrency" content="EUR">';
				// 	echo '<meta itemprop="offerCount" content="'.sizeof((array) $durchfClass->preise).'">';
					echo '<meta itemprop="url" content="http://'.$_SERVER['SERVER_NAME'].str_replace('&amp;', '&', htmlspecialchars( $_SERVER['REQUEST_URI'] )).'">';
					echo '<meta itemprop="eligibleRegion" content="DE-RP">';
					echo '<span itemprop="eligibleCustomerType" itemscope itemtype="https://schema.org/BusinessEntityType">';
					echo '<meta itemprop="additionalType" content="http://purl.org/goodrelations/v1#Enduser">';
					echo '</span>';
					echo '</div>'; // Ende AggregateOffer
				} */
			
			flush();
			
			if( $pagesel )
			{
				echo '<div class="wisyr_list_footer clearfix">';
				    // if( $this->framework->iniRead('rsslink', 0) )
					 // echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
					$this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_bottom');
				echo '</div>';
			}
			
			if($this->framework->iniRead('sw_cloud.suche_anzeige', 0)) {
			    global $wisyPortalId;
			    
			    $cacheKey = "sw_cloud_p".$wisyPortalId."_s".$queryString;
			    $this->dbCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_tagcloud', 'itemLifetimeSeconds'=>60*60*24));
			    
			    
			    if( ($temp=$this->dbCache->lookup($cacheKey))!='' )
			    {
			        $tag_cloud = $temp." <!-- tag cloud from cache -->";
			    }
			    else
			    {
			        $filtersw = array_map("trim", explode(",", $this->framework->iniRead('sw_cloud.filtertyp', "32, 2048, 8192")));
			        $distinct_tags = array();
			        $tag_cloud = '<div id="sw_cloud">Suchbegriffe: ';
			        $tag_cloud .= '<h4>Suchbegriffe</h4>';
			        $tag_done = array();
			        
			        foreach($tags_heap AS $tags) {
			            for($i = 0; $i < count($tags); $i++)
			            {
			                $tag = $tags[$i];
			                
			                if(in_array($tag['id'], $tag_done))
			                    continue;
			                    
			                    $weight = 0;
			                    
			                    if($this->framework->iniRead('sw_cloud.suche_gewichten', 1)) {
			                        $tag_freq = $this->framework->getTagFreq($db, $tag['stichwort']);
			                        $weight = (floor($tag_freq/50) > 15) ? 15 : floor($tag_freq/50);
			                    }
			                    
			                    if($tag['eigenschaften'] != $filtersw && $tag_freq > 0); {
			                        if($this->framework->iniRead('sw_cloud.suche_stichwoerter', 1))
			                            $tag_cloud .= '<span class="sw_raw typ_'.$tag['eigenschaften'].'" data-weight="'.$weight.'"><a href="/search?q='.urlencode( cs8($tag['stichwort']) ).( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '').( $this->framework->force ? '&force='.$this->framework->force : '').'">'.cs8($tag['stichwort']).'</a></span>, ';
			                            
			                        if($this->framework->iniRead('sw_cloud.suche_synonyme', 0))
			                            $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Synonyme"), $filtersw, "Synonym", cs8($tag['stichwort']));
			                                
			                        if($this->framework->iniRead('sw_cloud.suche_oberbegriffe', 0))
			                            $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Oberbegriffe"), $filtersw, "Oberbegriff", cs8($tag['stichwort']));
			                                    
			                        if($this->framework->iniRead('sw_cloud.suche_unterbegriffe', 0))
			                            $tag_cloud .= $this->framework->writeDerivedTags($this->framework->loadDerivedTags($db, $tag['id'], $distinct_tags, "Unterbegriffe"), $filtersw, "Unterbegriff", cs8($tag['stichwort']));
			                    }
			                    
			                    array_push($tag_done, $tag['id']);
			                    
			            } // end: for
			        }
			        
			        $tag_cloud = trim($tag_cloud, ", ");
			        $tag_cloud .= '</div>';
			        
			        $this->dbCache->insert($cacheKey, utf8_decode($tag_cloud));
			    }
			    echo $tag_cloud;
			} // end: tag cloud
		}
		else 
		{
			
			if( sizeof($info['suggestions']) == 0 )
			{
				$temp = trim($queryString, ', ');
				echo '<p class="wisy_topnote">';
					echo 'Keine aktuellen Datens&auml;tze f&uuml;r <em>&quot;'  . htmlspecialchars(cs8($temp)) . '&quot;</em> gefunden.<br /><br />';
					echo '<a href="' . $this->framework->getUrl('search', array('q'=>"$temp, Datum:Alles")) . '">Suche wiederholen und dabei <b>auch abgelaufene Kurse ber&uuml;cksichtigen</b> ...</a>';
				echo "</p>\n";
			}
			
			echo '<div class="wisyr_list_footer clearfix">';
			     // if( $this->framework->iniRead('rsslink', 0) )
			      // echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
			echo '</div>';
		}

		if( !$nexturl && !$this->framework->editSessionStarted ) {

			echo '	<div id="iwwb"><!-- BANNER IWWB START -->
				<script type="text/javascript">
			        var defaultZIP="PLZ";
			        var defaultCity="Ort";
			        var defaultKeywords = "Suchw&ouml;rter eingeben";

			        function IWWBonFocusTextField(field,defaultValue){
			                if (field.value==defaultValue) field.value="";
			        }
			        function IWWBonBlurTextField(field,defaultValue){
			                if (field.value=="") field.value=defaultValue;
			        }
			        function IWWBsearch(button) {
			            if (button.form.feldinhalt1.value == defaultKeywords) {
			                        alert("Bitte geben Sie Ihre Suchw366rter ein!");
			                } else {
			                        if ((typeof button.form.feldinhalt2=="object") && button.form.feldinhalt2.value == defaultZIP) button
			.form.feldinhalt2.value="";
			                        if ((typeof button.form.feldinhalt3=="object") && button.form.feldinhalt3.value == defaultCity) button.form.feldinhalt3.value="";

			                        button.form.submit();
			                        if ((typeof button.form.feldinhalt2=="object") && button.form.feldinhalt2.value == "") button.form.feldinhalt2.value=defaultZIP;
			                        if ((typeof button.form.feldinhalt3=="object") && button.form.feldinhalt3.value == "") button.form.feldinhalt3.value=defaultCity;
			                }
			        }
			</script>

			<form method="post" action="https://www.iwwb.de/suchergebnis.php" target="IWWB">
			<input type="hidden" name="external" value="true">
  <input type="hidden" name="method" value="iso">
  <input type="hidden" name="feldname1" id="feldname1" value="Freitext" />
  <input type="hidden" name="feldname2" id="feldname2" value="PLZOrt" />
  <input type="hidden" name="feldname3" id="feldname3" value="PLZOrt" />
  <input type="hidden" name="feldname7" id="feldname7" value="datum1" />
  <input type="hidden" name="feldinhalt7" id="feldinhalt7" value="morgen" />

			<table width="100%" border="0" cellpadding="4" cellspacing="0" style="border: 1px solid #777EA7;background-color: #EFEFF7;">
			<tr>
			<td style="font-family: Verdana, Arial, Helvetica, sans-serif;font-size: 11px;color: #5F6796;font-weight: bold;">Bundesweite
			Suche im InfoWeb Weiterbildung</td>
			<td><input name="feldinhalt1" type="text" style="height: 22px;width: 150px;font-family: Verdana, Arial, Helvetica, sans-serif
			;font-size: 11px;color: #000000;" value="' .  isohtmlspecialchars($queryString) . '" onfocus="IWWBonFocusTextField(this,defaultKeywords)" onblur="IWWBonBlurTextField(this,defaultKeywords)"><input name="search" type
			="button" style="font-family: Verdana, Arial, Helvetica, sans-serif;font-size: 9px;height: 22px;width: 90px;background-color:
			 #A5AAC6;font-weight: bold;color: #FFFFFF;margin: 0px;border: 1px solid #FFFFFF;padding: 0px;" value="Suche starten" onClick=
			"IWWBsearch(this)"></td>
			<td align="right" style="font-family: Verdana, Arial, Helvetica, sans-serif;font-size: 11px;color: #5F6796;font-weight: bold;
			"><a href="https://www.iwwb.de" target="_blank" rel="noopener noreferrer"><img src="https://www.iwwb.de/web/images/iwwb.gif" border="0"></a>&nbsp;</td>
			</tr>
			</form>
			</table>
			</div>
			';
		
		}
	}
	
	function renderAnbieterliste(&$searcher, $queryString, $offset)
	{
		$anbieterRenderer =& createWisyObject('WISY_ANBIETER_RENDERER_CLASS', $this->framework);

		$validOrders = array('a', 'ad', 's', 'sd', 'p', 'pd', 'o', 'od', 'h', 'hd', 'e', 'ed', 't', 'td', 'creat', 'creatd');
		$orderBy = strval( $this->framework->getParam('order') ); if( !in_array($orderBy, $validOrders) ) $orderBy = 'a';

		$db2 = new DB_Admin();

		$sqlCount = $searcher->getAnbieterCount();
		if( $sqlCount )
		{
			// create get prev / next URLs
			$prevurl = $offset==0? '' : $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset-$this->rows));
			$nexturl = ($offset+$this->rows<$sqlCount)? $this->framework->getUrl('search', array('q'=>$queryString, 'offset'=>$offset+$this->rows)) : '';
			if( $prevurl || $nexturl )
			{	
				$param = array('q'=>$queryString);
				if( $orderBy != 'b' ) $param['order'] = $orderBy;
				$param['offset'] = '';
				$pagesel = $this->pageSel($this->framework->getUrl('search', $param), $this->rows, $offset, $sqlCount);
			}
			else
			{
				$pagesel = '';
			}

			// render head
			echo '<div class="wisyr_listnav">';
			$link_angebote = trim(urlencode(str_replace(array(',,', ', ,'), array(',', ','), str_replace('Zeige:Anbieter', '', $queryString))));
			if($link_angebote)
			    echo '<a href="search?q=' . $link_angebote . ( $this->framework->qtrigger ? '&qtrigger='.$this->framework->qtrigger : '') . ( $this->framework->force ? '&force='.$this->framework->force : '') . '" class="tab_kurse">Angebote</a>';
			    
			echo '<span class="active tab_anbieter">Anbieter</span>';
			echo '<a class="wisyr_kurse_switch" href="search?q=' . urlencode(str_replace(array(',,', ', ,'), array(',', ','), str_replace('Zeige:Anbieter', '', $queryString))). '">Zeige Kurse</a>';
			echo '</span>';
			    
			if( $pagesel )
			{
			     $this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_top');
			}
			echo '</div>' . "\n";
			flush();
			
			// render column titles
			echo "\n".'<table class="wisy_list wisyr_anbieterliste">' . "\n";
			echo '  <thead><tr>' . "\n";
				$this->renderColumnTitle('Anbieter',	'a', 	$orderBy,	311);
				$this->renderColumnTitle('Stra&szlig;e',		's', 	$orderBy,	0);
				$this->renderColumnTitle('PLZ',			'p',	$orderBy,	0);
				$this->renderColumnTitle('Ort',			'o',	$orderBy,	0);
				$this->renderColumnTitle('Web',			'h',	$orderBy,	0);
				$this->renderColumnTitle('E-Mail',		'e',	$orderBy,	0);
				$this->renderColumnTitle('Telefon',		't',	$orderBy,	0);
			echo '  </tr></thead>' . "\n";

			// render records
			$records = $searcher->getAnbieterRecords($offset, $this->rows, $orderBy);
			$rows = 0;
			
			foreach($records['records'] as $i => $record)
			{
				
				$rows++;
				$class = ($rows%2)==0? ' class="wisy_even"' : '';
			
				echo "  <tr$class>\n";
					$this->renderAnbieterCell2($db2, $record, array('q'=>$queryString, 'addPhone'=>false, 'clickableName'=>true, 'addIcon'=>true));
					echo '<td class="wisyr_strasse" data-title="Straße">';
						echo htmlspecialchars(cs8($record['strasse']));
					echo ' </td>';
					echo '<td class="wisyr_plz" data-title="PLZ">';
						echo htmlspecialchars(cs8($record['plz']));
					echo ' </td>';
					echo '<td class="wisyr_ort" data-title="Ort">';
						echo htmlspecialchars(cs8($record['ort']));
					echo ' </td>';
					echo '<td class="wisyr_homepage" data-title="Homepage">';
						$link = $record['homepage'];
						if( $link != '' )
						{
							if( substr($link, 0, 4) != 'http' )
								$link = 'http:/' . '/' . $link;
							echo '<a href="'.$link.'" target="_blank" rel="noopener noreferrer">Web</a>';
						}
					echo ' </td>';
					echo '<td class="wisyr_email" data-title="E-Mail">';
						$link = $record['anspr_email'];
						if( $link != '' )
							echo '<a href="' . $anbieterRenderer->createMailtoLink($link) . '" target="_blank"" rel="noopener noreferrer">E-Mail</a>';
					echo ' </td>';
					echo '<td class="wisyr_telefon" data-title="Telefon">';
						echo '<a href="tel:' . urlencode(cs8($record['anspr_tel'])) . '">' . htmlspecialchars(cs8($record['anspr_tel'])) . '</a>';
					echo ' </td>';
				echo '  </tr>' . "\n";
			}

			// main table end
			echo '</table>' . "\n\n";
			flush();

			// render tail
			if( $pagesel )
			{
				echo '<div class="wisyr_list_footer clearfix">';
				    // if( $this->framework->iniRead('rsslink', 0) )
				     // echo '<div class="wisyr_rss_link_wrapper">' . $this->framework->getRSSLink() . '</div>';
					$this->renderPagination($prevurl, $nexturl, $pagesel, $this->rows, $offset, $sqlCount, 'wisyr_paginate_bottom');
				echo '</div>';
			}
		}
		else /* if( sqlCount ) */
		{
			echo '<p class="wisy_topnote">Keine Datens&auml;tze f&uuml;r <em>&quot;'.htmlspecialchars(cs8(trim($queryString, ', '))).'&quot;</em> gefunden.</p>' . "\n";
		}
	}
	
	function render()
	{
		// get parameters
		// --------------------------------------------------------------------
		
		$queryString = $this->framework->getParam('q', '');

		if( $this->framework->iniRead('searcharea.radiussearch', 0) )
		{
			// add "bei" and "km" to the parameter "q" - this is needed as we forward only one paramter, eg. to subsequent pages
			$bei = trim($this->framework->getParam('bei', ''));
			if( $bei != '' ) {
				$bei = strtr($bei, array(', '=>'/', ','=>'/')); // convert the comma to slashes as commas are used to separate fields
				$queryString = trim($queryString, ', ');
				$queryString .= ($queryString!=''? ', ' : '') . 'bei:' . $bei;
				
				$km = intval($this->framework->getParam('km', ''));
				if( $km > 0 ) {
					$queryString .= ($queryString!=''? ', ' : '') . 'km:' . $km;
				}
				
				header('Location: search?q=' . urlencode($queryString));
				exit();
			}
		}
		
		$offset = intval( $this->framework->getParam('offset') ); if( $offset < 0 ) $offset = 0;
		$title = trim($queryString, ', ');

		// page / ajax start
		// --------------------------------------------------------------------

		if( intval( $this->framework->getParam('ajax') ) )
		{
			header('Content-type: text/html; charset=utf-8');
		}
		else
		{
			echo $this->framework->getPrologue(array(
												'title'		=>	$title,
												'bodyClass'	=>	'wisyp_search',
											));
			echo $this->framework->getSearchField();
			flush();
		}

		// result out
		// --------------------------------------------------------------------
		
		// Suche und Ergebnisliste auf Startseite optional abschalten        
		if(!(intval(trim($this->framework->iniRead('search.startseite.disable'))) && $this->framework->getPageType() == "startseite")) {

		$searcher =& createWisyObject('WISY_INTELLISEARCH_CLASS', $this->framework);
		$searcher->prepare($queryString);
		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.above', '') );
		
		echo '<div id="wisy_resultarea" class="' .$this->framework->getAllowFeedbackClass(). '">';
		
		    if( $this->framework->getParam('show') == 'tags' )
			{
				$this->renderTagliste($queryString);
			}
			else if( $searcher->ok() )
			{
				$info = $searcher->getInfo();
				if( $info['show'] == 'kurse' )
				{
					$this->renderKursliste($searcher, $queryString, $offset);
				}
				else
				{
					$this->renderAnbieterliste($searcher, $queryString, $offset);
				}
			}
			else
			{
				$info  = $searcher->getInfo();
				$error = $info['error'];
				switch( $error['id'] )
				{
					case 'tag_not_found':
						echo '<p class="wisy_topnote">Ihre Suche nach &quot;' .htmlspecialchars(cs8(trim($queryString, ', '))). '&quot; liefert keine Treffer.</p>' . "\n";
						break;
				
					case 'field_not_found':
						echo '<p class="wisy_topnote">Das zu durchsuchende Feld &quot;'.htmlspecialchars(cs8($error['field'])).'&quot; ist unbekannt oder falsch geschrieben.</p>' . "\n";
						break;
				
					case 'missing_fulltext':
						echo '<p class="wisy_topnote">Bitte geben Sie die zu suchenden Volltextw&ouml;rter hinter <em>Volltext:</em> an.</p>' . "\n";
						break;
				
					case 'bad_location':
						echo 	'<p class="wisy_topnote">'
							.		' Die Ortsangabe <em>bei:'.htmlspecialchars(cs8($error['field'])).'</em> konnte nicht lokalisiert werden (Status='.htmlspecialchars(cs8($error['status'])).').'
							.	'</p>' . "\n";
						break;
						
					case 'inaccurate_location':
						// see http://code.google.com/intl/de/apis/maps/documentation/reference.html#GGeoAddressAccuracy
						$accuracies = array('Unbekannt', 'Land', 'Region', 'Kreis', 'Ortschaft', 'PLZ', 'Strasse', 'Kreuzung', 'Adresse', 'Grundst&uuml;ck');
						$ist_accuracy = intval($error['ist_accuracy']);
						$soll_accuracy = intval($error['soll_accuracy']);
						echo 	'<p class="wisy_topnote">'
							.		' Die Ortsangabe <em>bei:'.htmlspecialchars(cs8($error['field'])).'</em> wurde als '
							.		' wurde als <em>'.$accuracies[$ist_accuracy].' ('.$ist_accuracy.')</em> klassifiziert und ist damit zu ungenau. '
							.		' Erforderlich ist mindestens die Genauigkeit <em>'.$accuracies[$soll_accuracy].'</em>. '
							.	'</p>' . "\n";
						break;
				
					case 'bad_km':
						echo '<p class="wisy_topnote">Fehlerhafte <em>km:</em>-Angabe. Geben Sie hier die Entfernung zur Adresse in ganzen Kilometern an im Bereich von 1-'.$error['max_km'].' an. Wenn Sie diese Angabe weglassen, wird der Standardwert <em>km:'.$error['default_km'].'</em> verwendet.</p>' . "\n";
						break;

					case 'km_without_bei':
						echo '<p class="wisy_topnote">Die Angabe von <em>km:</em> ist nur in Kombination mit <em>bei:</em> g&uuml;ltig. F&uuml;r eine einfache Eingabe der richtigen Werte, verwenden Sie bitte die <em>Erweiterte Suche</em>.</p>' . "\n";
						break;
				
					default:
						echo '<p class="wisy_topnote">Fehler in der Suchabfrage (interner Fehler <em>'.$error['id'].'</em>)</p>' . "\n";
						break;
				}
			}

			if( $this->framework->editSessionStarted )
			{
				$loggedInAnbieterId = $this->framework->getEditAnbieterId();
				
				$editor =& createWisyObject('WISY_EDIT_RENDERER_CLASS', $this->framework);
				$adminAnbieterUserIds = $editor->getAdminAnbieterUserIds();
				
				// get a list of all offers that are not "gesperrt"
				$temp = '0';
				$titles = array();
				$db = new DB_Admin;
				$db->query("SELECT id, titel FROM kurse WHERE anbieter=$loggedInAnbieterId AND user_created IN (".implode(',',$adminAnbieterUserIds).") AND freigeschaltet!=2;");
				while( $db->next_record() )
				{ 
					$currId = intval($db->fcs8('id')); $titles[ $currId ] = $db->fcs8('titel'); $temp .= ', ' . $currId;
				}
				
				// compare the 'offers that are not "gesperrt"' against the ones that are in the search index
				$liveIds = array();
				$db->query("SELECT kurs_id FROM x_kurse WHERE kurs_id IN($temp)");
				while( $db->next_record() )
				{
					$liveIds[ $db->fcs8('kurs_id') ] = 1;
				}
				
				// show 'offers that are not "gesperrt"' which are _not_ in the search index (eg. just created offers) below the normal search result
				echo '<p><span class="wisy_edittoolbar" title="Um einen neuen Kurs hinzuzuf&uuml;gen, klicken Sie oben auf &quot;Neuer Kurs&quot;">Kurse in Vorbereitung:</span>&nbsp; ';
					$out = 0;
					reset( $titles );
					foreach($titles as $currId => $currTitel)
					{
						if( !$liveIds[ $currId ] )
						{
							echo $out? ' &ndash; ' : '';
							echo '<a href="k'.$currId.'">' . htmlspecialchars($currTitel) . '</a>';

							$out++; 
						}
					}
					if( $out == 0 ) echo '<i title="Um einen neuen Kurs hinzuzuf&uuml;gen, klicken Sie oben auf &quot;Neuer Kurs&quot;">keine</i>';
					//echo ' &ndash; <a href="edit?action=ek&amp;id=0">Neuer Kurs...</a>';
				echo '</p>';
			}
		
		echo '</div>';
		}
		
		echo $this->framework->replacePlaceholders( $this->framework->iniRead('spalten.below', '') );
		
		// page / ajax end
		// --------------------------------------------------------------------
		
		if( intval( $this->framework->getParam('ajax') ) )
		{
			;
		}
		else
		{
			echo $this->framework->getEpilogue();
		}
	}
};
