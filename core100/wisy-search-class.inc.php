<?php if( !defined('IN_WISY') ) die('!IN_WISY');

/******************************************************************************

WISY_SEARCH_CLASS wird verwendet, um genau spezifizierte Suchen zu starten oder
um Informationen zu Suchen zu erhalten.

WISY_SEARCH_CLASS fuehrt keine alternativen Suchen durch (z.B. eine Volltextsuche,
wenn die normale Suche keinen Erfolg brachte). Wenn dies gewuenscht ist, ist dies 
die Aufgabe des Aufrufenden Programmteils.

Beispiele zur Verwendung:


	// Suche nach Kursen oder Anbietern
						$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
						$searcher->prepare($query);
	$anzahlErgebnisse = $searcher->getKurseCount();
	$ergebnisse 	  = $searcher->getKurseRecords($offset, $rows, $orderby)


	// Suche nach Anbietern
						$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
						$searcher->prepare($query);
	$anzahlErgebnisse = $searcher->getAnbieterCount();
	$ergebnisse 	  = $searcher->getAnbieterRecords($offset, $rows, $orderby)


	// Suchstring in Tokens zerlegen (z.B. um Teile in der erweiterten Suche speziell darzustellen):
						$searcher =& createWisyObject('WISY_SEARCH_CLASS', $this->framework);
	$tokens 		  = $searcher->tokenize($query);

Achtung: Leere Anfragen oder Leere Ergebnismengen sind _keine_ Fehler!

*******************************************************************************/

require_once('admin/config/codes.inc.php');

class WISY_SEARCH_CLASS
{
	// all private!
	var $framework;
	
	var $db;
	private $dbCache;
	
	var $error;
	var $tokens;
	
	var $last_lat;
	var $last_lng;
	
	var $rawJoin;
	var $rawWhere;
	var $fulltext_select;
	var $assumedLocation;
	var $globalTagHeap;
	
	private $rawCanCache;
	
	function __construct(&$framework, $param)
	{
		global $wisyPortalId;
	
		$this->framework	=& $framework;
		
		$this->db			=  new DB_Admin;
		$this->dbCache		=& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_search', 'itemLifetimeSeconds'=>SEARCH_CACHE_ITEM_LIFETIME_SECONDS));
		
		$this->error		= false;
		$this->secneeded	= 0;
		
		$this->checked_reftag = array();
	}
	

	/**************************************************************************
	 * performing a search
	 **************************************************************************/

	/* ********************* *
	 * handle redundant tags *
	 * ********************* */
	
	function findSiblingTags($type, $tag_orig_id, $tag_heap, $max_levels, $cnt_levels = 1) {
	    
	    // this function called recursively, increasing level up/down with each call until max_levels reached
	    if($cnt_levels >= $max_levels)
	        return false;
	        
	        $ref_tags = array();
	        $this->framework->loadDerivedTags($this->db, $tag_orig_id, $ref_tags, ($type == "descendant" ? "Unterbegriffe" : "Oberbegriffe") );
	        
	        foreach($ref_tags AS &$ref_tag) { // &$ref_tag b/c pushing synonyms to $ref_tags in loop to have effect
	            if(in_array(strtolower(trim($ref_tag)), array_map('strtolower', $tag_heap)))
	                return array("found" => $type, "tag" => $ref_tag);
	                
	                $result = $this->db->query("SELECT id FROM stichwoerter WHERE stichwort='".addslashes($ref_tag)."'");
	                while($this->db->next_record()) {
	                    $ref_tagID = $this->db->f('id');
	                    $synonym_tags = array();
	                    $this->framework->loadDerivedTags($this->db, $ref_tagID, $synonym_tags, "Synonyme"); // also hidden synonyms! just checks if mapped to other tag
	                    foreach($synonym_tags AS $sibling) { // foreach synonym
	                        if( !in_array($sibling, $this->checked_reftag) ) {
	                            array_push($ref_tags, $sibling); // check siblings of this level (and their ancestors/descendants) after looping through regular tags of this level
	                            array_push($this->checked_reftag, $sibling);
	                        }
	                    }
	                    
	                    if( $found = $this->findSiblingTags($type, $ref_tagID, $tag_heap, $max_levels, $cnt_levels+1) )
	                        return $found; // enough if one of the descendant matches
	                        else
	                            ; // continue with next descendant of this level (of foreach)
	                }
	        }
	        
	        // no descendant found on 1st level
	        return false;
	}
	
	
	function findSynonyms($tag_orig_id, $tag_heap, $q_tag = "") {
	    $ref_tags = array();
	    $this->framework->loadDerivedTags($this->db, $tag_orig_id, $ref_tags, "Synonyme"); // also hidden synonyms! just checks if mapped to other tag
	    $result = $this->db->query("SELECT eigenschaften FROM stichwoerter WHERE id=".intval($tag_orig_id)); // determine wether open or hidden
	    
	    if($this->db->next_record()) {
	        $tag_type = $this->db->f('eigenschaften');
	        foreach($ref_tags AS $sibling) { // foreach synonym
	            if(in_array(strtolower(trim($sibling)), array_map('strtolower', $tag_heap))) {
	                return array("found" => "synonym", "tag_type_current_q" => $tag_type, "tag" => ($tag_type == (64 || 32)) ?  $sibling : $q_tag, "synonym" => ($tag_type == (64 || 32)) ? $q_tag : $sibling );
	            }
	        }
	    }
	    
	    return false;
	}
	
	function isRedundantSearchTag( $q_tag, $tag_heap, $valid = array(), $max_levels = 4 ) {
	    
	    if($q_tag == "" || strpos($q_tag, '.portal') !== FALSE)
	        return false;
	        
	        $sql = "SELECT id FROM stichwoerter WHERE stichwort='".addslashes($q_tag)."';";
	        
	        $this->db->query($sql);
	        if( $this->db->next_record() )
	            $tag_orig_id = intval($this->db->f('id'));
	            
	        if( $tag_orig_id && in_array("ancestor", $valid) && $ancestor = $this->findSiblingTags("ancestor", $tag_orig_id, $tag_heap, $max_levels) )
	            return $ancestor;
	                
	        if( $tag_orig_id && in_array("descendant", $valid) && $descendant = $this->findSiblingTags("descendant", $tag_orig_id, $tag_heap, $max_levels) )
	            return $descendant;
	                    
	        if( $tag_orig_id && in_array("synonyms", $valid) && $synonyms = $this->findSynonyms($tag_orig_id, $tag_heap, $q_tag) )
	            return $synonyms;
	                        
	        // tag used at least twice in query?
	        if($q_tag != "" && in_array("duplicate", $valid)) {
	            if(in_array(strtolower(trim($q_tag)), array_map('strtolower', $tag_heap))) {
	               return array("found" => "duplicate", "tag" => $q_tag);
	            }
	        }
	                        
	        // no ancestors, descendants, snyonyms or duplicates in tag_heap
	        return false;
	}
	
	
	function filterRedundantTags($operator, $tag_heap, $tag, $maxlevels) {
	    
	    // Remove redundant search tags from actual query string: ancestors, descendant, synonyms, duplicates
	    
	    $continue_redundant = false;
	    $original = false;
	    
	    if($operator == "OR") {
	        
	        // duplicates and synonyms
	        // do this firsst b/c ancestors/descendants are not mapped to synonyms
	        if( $redundantFound = $this->isRedundantSearchTag($tag, $tag_heap, array("duplicate", "synonyms"), $maxlevels) ) { // Redundant found
	            if( isset($redundantFound["synonym"]) ) { // only works if both original and synonym in search string
	                array_push($this->double_tags, "Entfernt wurde: \"<strike>".$redundantFound["synonym"]."</strike>\" - Grund: Synonym zu ".$redundantFound["tag"])."</b>";
	                if($tag == $redundantFound["synonym"]) // curent q is synonym
	                    $continue_redundant = true; // => SKIP
	                    else { // former q contain synonym => keep $subval[$s] = $tag
	                        if (($key = array_search( strtolower($redundantFound["synonym"]), array_map("strtolower", $tag_heap) )) !== false)
	                            unset($tag_heap[$key]);
	                    }
	            }
	            else { // duplicate found
	                array_push($this->double_tags, "1x Entfernt wurde: \"<strike>".$tag."</strike>\" - Grund: Doppeltes Vorkommen");
	                $continue_redundant = true; // => skip duplicate
	            }
	        }
	        
	        // ancestors and descendants - continue witch ancestors only in OR-search!
	        $redundantFound = $this->isRedundantSearchTag($tag, $tag_heap, array("ancestor", "descendant"), $maxlevels);
	        
	        // curent value may still be a synonym => descendant/ancestor would not be found => check referenced tag
	        if(!$redundantFound["found"] == "descendant" && !$redundantFound["found"] == "ancestor") {
	            
	            // Look for: 64 = Synonym, 32 = verstecktes Synonym
	            $sql = "SELECT stichwort FROM stichwoerter WHERE id IN (SELECT stichwoerter_verweis.attr_id AS id "
	                ."FROM stichwoerter, stichwoerter_verweis WHERE stichwoerter.stichwort = '".addslashes($tag)."' AND stichwoerter.eigenschaften IN (64,32) AND stichwoerter_verweis.primary_id = stichwoerter.id)";
	                $this->db->query($sql);
	                while($this->db->next_record()) {
	                    $redundantFound = $this->isRedundantSearchTag($this->db->f('stichwort'), $tag_heap, array("ancestor", "descendant"), $maxlevels);
	                }
	        }
	        
	        if($redundantFound["found"] == "descendant") {
	            if (($key = array_search( strtolower($redundantFound["tag"]), array_map("strtolower", $tag_heap) )) !== false) {  // descendant is second tag
	                unset($tag_heap[$key]);
	                
	                array_push($this->double_tags, "Entfernt wurde: \"<strike>".$redundantFound["tag"]."</strike>\" - Grund: Unterbegriff von \"".$tag."\"");
	            }
	        } elseif($redundantFound["found"] == "ancestor") {
	            if (($key = array_search( strtolower($tag), array_map("strtolower", $tag_heap) )) !== false)
	                unset($tag_heap[$key]);
	                
	                array_push($this->double_tags, "Entfernt wurde: \"<strike>".$tag."</strike>\" - Grund: Unterbegriff von \"".$redundantFound["tag"]."\"");
	                $continue_redundant = true; // = > skip
	        }
	        
	    } elseif("AND") { // comma
	        
	        // duplicates and synonyms
	        // do this firsst b/c ancestors/descendants are not mapped to synonyms
	        if( $redundantFound = $this->isRedundantSearchTag($tag, $tag_heap, array("duplicate", "synonyms"), $maxlevels) ) {
	            if( isset($redundantFound["synonym"]) ) { // only works if both original and synonym in search string
	                array_push($this->double_tags, "Entfernt wurde: \"<strike>".$redundantFound["synonym"]."</strike>\" - Grund: Synonym zu ".$redundantFound["tag"])."</b>";
	                if($tag == $redundantFound["synonym"]) { // curent q is synonym
	                    $continue_redundant = true;
	                }
	                else { // former q contain synonym
	                    if (($key = array_search( strtolower($redundantFound["synonym"]), array_map("strtolower", $tag_heap) )) !== false)
	                        unset($tag_heap[$key]);
	                }
	            }
	            else {
	                array_push($this->double_tags, "1x Entfernt wurde: \"<strike>".$tag."</strike>\" - Grund: Doppeltes Vorkommen");
	                $continue_redundant = true;
	            }
	        }
	        
	        // ancestors and descendants - continue witch ancestors only in OR-search!
	        $redundantFound = $this->isRedundantSearchTag($tag, $tag_heap, array("ancestor", "descendant"), $maxlevels);
	        
	        // curent value may still be a synonym => descendant/ancestor would not be found => check referenced tag
	        if(!$redundantFound["found"] == "descendant" && !$redundantFound["found"] == "ancestor") {
	            // Look for: 64 = Synonym, 32 = verstecktes Synonym
	            $sql = "SELECT stichwort FROM stichwoerter WHERE id IN (SELECT stichwoerter_verweis.attr_id AS id "
	                ."FROM stichwoerter, stichwoerter_verweis WHERE stichwoerter.stichwort = '".addslashes($tag)."' AND stichwoerter.eigenschaften IN (64,32) AND stichwoerter_verweis.primary_id = stichwoerter.id)";
	                $this->db->query($sql);
	                while($this->db->next_record()) {
	                    // Current value = synonym => check referenced original for ancestors/descendants
	                    $redundantFound = $this->isRedundantSearchTag($this->db->f('stichwort'), $tag_heap, array("ancestor", "descendant"), $maxlevels);
	                }
	        }
	        
	        if($redundantFound["found"] == "ancestor") {
	            if (($key = array_search( strtolower($redundantFound["tag"]), array_map("strtolower", $tag_heap) )) !== false) {  // descendant is second tag
	                unset($tag_heap[$key]);
	                array_push($this->double_tags, "Entfernt wurde: \"<strike>".$redundantFound["tag"]."</strike>\" - Grund: Oberbegriff von \"".$tag."\"");
	            }
	            
	        } elseif($redundantFound["found"] == "descendant") {
	            if (($key = array_search( strtolower($tag), array_map("strtolower", $tag_heap) )) !== false)
	                unset($tag_heap[$key]);
	                array_push($this->double_tags, "Entfernt wurde: \"<strike>".$tag."</strike>\" - Grund: Oberbegriff von \"".$redundantFound["tag"]."\"");
	                $continue_redundant = true;
	        }
	        
	    }
	    
	    return array("continue_redundant" => $continue_redundant, "tag_heap" => $tag_heap);
	}
	
	
	/* ************************** *
	 * end: handle redundant tags *
	 * ************************** */
	
	function checkqueryString($queryString)
	{
	    // Excel in Mainz => Excel, Mainz if "Excel in Mainz" not a tag
	    global $ignoreWords_DE;
	    
	    $queryArr = explode(',', $queryString);
	    for( $i = 0; $i < sizeof($queryArr); $i++ )
	    {
	        $value = trim($queryArr[$i]);
	        if ($this->lookupTag($value) == 0) {
	            $parts =  explode(' ', $value);
	            for( $j = 0; $j < sizeof($parts); $j++ ) {
	                if (in_array($parts[$j], $ignoreWords_DE)) {
	                    $parts[$j] = "";
	                } else {
	                    $rort = $this->lookupOrt($parts[$j]);
	                    if ($rort) {
	                        
	                        if (sizeof($parts) == 1) {
	                        } else if ($i == 0) {
	                            $parts[$j] = ",".$rort.",";
	                        } else {
	                            $parts[$j] = ",".$rort;
	                        }
	                        break;
	                    }
	                }
	            }
	            $queryArr[$i] = implode(" ",  (array)$parts);
	        }
	    }
	    $queryString = implode(",", $queryArr);
	    
	    return $queryString;
	}
	
	function lookupOrt($ort)
	{
	    $ort_plz = 0;
	    $bezirk = 0;
	    $stadtteil = 0;
	    
	    if( $ort != '' )
	    {
	        global $geomap_orte;
	        
	        foreach($geomap_orte AS $synonym => $original) {
	            if (stristr(trim($ort), trim($synonym)))   { $ort = trim($original); }
	        }
	        
	        
	        $this->db->query("SELECT ort, plz FROM plz_ortscron WHERE ort = '".trim($ort)."';");
	        if( $this->db->next_record() ) {
	            $this->assumedLocation = $ort;
	            return $ort;
	        }
	        
	        $bezirk_name = str_replace('(Bezirk)', '', $ort); // space can't be part of ort
	        $this->db->query("SELECT tag_name FROM x_tags WHERE tag_name LIKE '".trim($bezirk_name)."%' AND tag_descr = 'Bezirk'");
	        if( $this->db->next_record() )
	            $bezirk = $this->db->fs('tag_name'); // recover Bezirk with " ... (Bezirk)"
	            
	            if( $bezirk ) {
	                $this->assumedLocation = $bezirk;
	                return $bezirk;
	            }
	            
	            $this->db->query("SELECT tag_name FROM x_tags WHERE tag_name LIKE '".trim($ort)."%' AND tag_descr = 'Stadtteil'");
	            if( $this->db->next_record() ) {
	                $this->assumedLocation = $ort." (Stadtteil)";
	                return $ort;
	            }
	    }
	    
	    
	    return 0;
	}
	
	function prepare($queryString)
	{
	    // Convert utf-8 input back to ISO-8859-15 because the DB ist still encoded with ISO
	    $queryString = PHP7 ? $queryString : iconv("UTF-8", "ISO-8859-15//IGNORE", $queryString);
	            
	    // first, apply the stdkursfilter
		global $wisyPortalFilter;
		global $wisyPortalId;
		
		// Ortsnamen im Suchstring nach dem Trennwort in umsetzen
		if ( $this->framework->getParam('qs', false) ) {
		    $queryString = $this->checkqueryString($queryString);
		}
		
		if( isset($wisyPortalFilter['stdkursfilter']) && $wisyPortalFilter['stdkursfilter'] != '' )
		{
			$queryString .= ", .portal$wisyPortalId";
		}

		$this->error 		= false;
		$this->queryString	= $queryString; // needed for the cache
		
		$this->tokens		= $this->tokenize($queryString);
		$this->rawJoinKurse = '';
		$this->rawJoin 		= '';
		$this->rawWhere		= '';
		$this->rawCanCache	= true;
		
		// pass 1: collect some values
		$this->last_lat = 0;
		$this->last_lng = 0;
		$has_bei = false;
		$max_km = 500;
		$default_km = $this->framework->iniRead('radiussearch.defaultkm', 2);
		$km = floatval($default_km);
		for( $i = 0; $i < sizeof((array) $this->tokens['cond']); $i++ )
		{
		    $value = $this->tokens['cond'][$i]['value']; // PHP7 ? utf8_decode()
		        
		        switch( $this->tokens['cond'][$i]['field'] )
			{
				case 'bei':
					$has_bei = true;
					break;
					
				case 'km':
					$km = floatval(str_replace(',', '.', $value));
					if( $km <= 0.0 || $km > $max_km )
						$km = 0.0; // error
					break;
			}
		}
		
				
		// pass 2: create SQL
		$abgelaufeneKurseAnzeigen = 'no';
		$tag_heap = array();
		$this->double_tags = array();
		
		for( $i = 0; $i < sizeof((array) $this->tokens['cond']); $i++ )
		{
		    
		    // build SQL statements for this part
		    $value = $this->tokens['cond'][$i]['value']; // PHP7 ? utf8_decode()
		    
		    // redundant search cache -> one week // todo: invalidate cache if tag younger?
		    $this->dbCacheRedundant =& createWisyObject('WISY_CACHE_CLASS', $this->framework, array('table'=>'x_cache_search', 'itemLifetimeSeconds'=>60*60*24*7));
		        
		    switch( $this->tokens['cond'][$i]['field'] )
			{
				case 'tag':
					$tagNotFound = false;
					if( stripos($value, ' ODER ') !== false )
					{
					    
					    // ODER-Suche
					    $subval = explode(' ODER ', strtoupper($value));
					    $rawOr = '';
					    for( $s = 0; $s < sizeof((array) $subval); $s++ )
					    {
					        // Remove redundant search tags from actual query string: ancestors, descendant, synonyms, duplicates
					        
					        $cacheKey = "redundant_tags_OR_query_".$value."_subval_".$subval[$s];
					        if( ($temp=$this->dbCacheRedundant->lookup($cacheKey))!='' ) // found in cache
					        {
					            $cacheArr = unserialize($temp);
					            $continue_redundant = $cacheArr['continue_redundant'];
					            
					            $tag_heap = $cacheArr['tag_heap'];
					            
					            foreach( $cacheArr['double_tags'] AS $double_tag) {
					                if( array_search($double_tag, $this->double_tags) === FALSE)
					                    array_push($this->double_tags, $double_tag); // don't do $this->double_tags = $cacheArr['double_tags'] - otherwise overwrites doubletags from AND search below
					            }
					            
					        } else {
					            $result = $this->filterRedundantTags( "OR", $tag_heap, $subval[$s], $this->framework->iniRead('search.redundant.maxlevels', 4) );
					            $continue_redundant = $result['continue_redundant'];
					            $tag_heap = $result['tag_heap'];
					            
					            $input = serialize( array("tag_heap" => $tag_heap, "continue_redundant" => $continue_redundant, "double_tags" => $this->double_tags));
					            
					            // Write to cache
					            $this->dbCacheRedundant->insert($cacheKey, $input );
					            
					        }
					        
					        if($continue_redundant)
					            continue;
					            
					            array_push($tag_heap, trim($subval[$s]));
					            
					            $tag_id = $this->lookupTag(trim($subval[$s]));
					            if( $tag_id == 0 )
					            { $tagNotFound = true; break; }
					            $rawOr .= $rawOr==''? '' : ' OR ';
					            $rawOr .= "j$i.tag_id=$tag_id";
					    }
					    if( !$tagNotFound )
					    {
					        $this->rawJoin  .= " LEFT JOIN x_kurse_tags j$i ON x_kurse.kurs_id=j$i.kurs_id";
					        $this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					        $this->rawWhere .= "($rawOr)";
					    }
					}
					else
					{
					    // simple AND- or NOT search
					    $op = '';
					    if( $value[0] == '-' )
					    {
					        $value = substr($value, 1);
					        $op = 'not';
					    }
					    
					    $tag_id = $this->lookupTag($value);  // here no intval() !! $tag_id may be string containing "#" for 1:n synonyms!
					    
					    
					    if( $tag_id == 0 )
					    {
					        $tagNotFound = true;
					    }
					    else
					    {
					        // Remove redundant search tags from actual query string: ancestors, descendant, synonyms, duplicates
					        
					        // adding position of the value within query ($i) only matches for exact positition within query = not very agressive caching
					        $cacheKey = "redundant_tags_AND_query_".$value.$i;
					        
					        // false = todo
					        if( false && ($temp=$this->dbCacheRedundant->lookup($cacheKey))!='' ) // found in cache
					        {
					            $cacheArr = unserialize($temp);
					            $continue_redundant = $cacheArr['continue_redundant'];
					            
					            $tag_heap = $cacheArr['tag_heap'];
					            foreach( $cacheArr['double_tags'] AS $double_tag) {
					                if( array_search($double_tag, $this->double_tags) === FALSE)
					                    array_push($this->double_tags, $double_tag); // don't do $this->double_tags = $cacheArr['double_tags'] - otherwise overwrites doubletags from OR search above
					            }
					        } else {
					            
					            $result = $this->filterRedundantTags( "AND", $tag_heap, $value, $this->framework->iniRead('search.redundant.maxlevels', 4) );
					            $continue_redundant = $result['continue_redundant'];
					            $tag_heap = $result['tag_heap'];
					            
					            // Write to cache
					            $this->dbCacheRedundant->insert($cacheKey, serialize( array("tag_heap" => $tag_heap, "continue_redundant" => $continue_redundant, "double_tags" => $this->double_tags)) );
					        }
					        
					        $this->double_tags = array_unique($this->double_tags);
					        
					        if($continue_redundant)
					            continue 2;
					            
					            array_push($tag_heap, $value);
					            
					            $this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					            if( $op == 'not' )
					            {
					                $this->rawWhere .= "x_kurse.kurs_id NOT IN(SELECT kurs_id FROM x_kurse_tags WHERE tag_id=$tag_id)";
					            }
					            else
					            {
					                $this->rawJoin  .= " LEFT JOIN x_kurse_tags j$i ON x_kurse.kurs_id=j$i.kurs_id";
					                
					                // synonym 1:n
					                if(strpos($tag_id, "#")) {
					                    $this->rawJoin  .= " LEFT JOIN x_tags_freq k$i ON k$i.tag_id=j$i.tag_id";
					                    
					                    $tag_ids = explode("#", $tag_id);
					                    $this->rawWhere .= '(';
					                    
					                    for($k = 0; $k < count($tag_ids); $k++) {
					                        $this->rawWhere .= "(j$i.tag_id=".$tag_ids[$k]." AND k$i.portal_id = ".$GLOBALS['wisyPortalId'].") OR ";	//  AND k$i.tag_freq > 0 -- not necessary -> if in table x_tags_freq must be used at least once
					                    }
					                    $this->rawWhere = substr($this->rawWhere, 0, strlen($this->rawWhere)-4); // remove last OR
					                    
					                    $this->rawWhere .= ')'; // brackets necessary, otherwise "AND x_kurse.beginn>=" etc. will only apply to last operand in OR-chain
					                    
					                } else {
					                    $this->rawWhere .= "j$i.tag_id=$tag_id";
					                } 
					                
					            }
					    }
					}

					if( $tagNotFound )
					{
						$this->error = array('id'=>'tag_not_found', 'tag'=>$value, 'first_bad_tag'=>$i);
						break;
					}
					break;

				case 'schaufenster':
					$portalId = intval($value);
					$this->rawJoin  .= " LEFT JOIN anbieter_promote j$i ON x_kurse.kurs_id=j$i.kurs_id";
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					$this->rawWhere .= "(j$i.portal_id=$portalId AND j$i.promote_active=1)";
					break;
				
				case 'typ':
				        
				        global $codes_stichwort_eigenschaften;
				        $codes_array = explode('###', $codes_stichwort_eigenschaften);
				        $code_found = false;
				        $cnt_types = 0;
				        
				        for($y = 0; $y < count($codes_array); $y++) {
				            
				            // more than one type
				            if(stripos(strtoupper($value), '#ODER#')) {
				                
				                $val_arr = explode('#ODER#', strtoupper($value));
				                
				                foreach($val_arr AS $val) {
				                    if(strtolower(trim($val)) == strtolower($codes_array[$y])) {
				                        $cnt_types++;
				                        $code_found = true;
				                        $this->rawWhere .= $this->rawWhere? ($cnt_types == 1 ? ' AND ( ' : ' OR ') : ' WHERE ( ';
				                        $this->rawWhere .= "x_kurse.kurs_id IN (SELECT x_kurse_tags.kurs_id FROM x_kurse_tags, x_tags WHERE x_kurse_tags.tag_id = x_tags.tag_id AND x_tags.tag_type = ".$codes_array[$y-1].")";
				                        if($cnt_types == count($val_arr))
				                            $this->rawWhere .= ")";
				                            
				                    }
				                }
				                
				            } elseif(strtolower(trim($value)) == strtolower($codes_array[$y])) {
				                $code_found = true;
				                $this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
				                $this->rawWhere .= "x_kurse.kurs_id IN (SELECT x_kurse_tags.kurs_id FROM x_kurse_tags, x_tags WHERE x_kurse_tags.tag_id = x_tags.tag_id AND x_tags.tag_type = ".$codes_array[$y-1].")";
				            }
				            
				        }
				        
				        if( !$code_found )
				        {
				            $this->error = array('id'=>'invalid_type', 'field'=>$value) ;
				        }
				        
				        break;
				    
				case 'preis':
					if( preg_match('/^([0-9]{1,9})$/', $value, $matches) )
					{	
						$preis = intval($matches[1]);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						if( $preis == 0 )
							$this->rawWhere .= "x_kurse.preis=0";
						else
							$this->rawWhere .= "(x_kurse.preis!=-1 AND x_kurse.preis<=$preis)";
					}
					else if( preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $value, $matches) )
					{	
						$preis1 = intval($matches[1]);
						$preis2 = intval($matches[2]);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						$this->rawWhere .= "(x_kurse.preis>=$preis1 AND x_kurse.preis<=$preis2)";
					}
					else
					{
						$this->error = array('id'=>'invalid_preis', 'field'=>$value) ;
					}
					break;
				
				case 'plz':
					$this->rawJoin  .= " LEFT JOIN x_kurse_plz j$i ON x_kurse.kurs_id=j$i.kurs_id";
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( strlen($value) < 5 )
						$this->rawWhere .= "(j$i.plz LIKE '".addslashes($value)."%')";
					else
						$this->rawWhere .= "(j$i.plz='".addslashes($value)."')";
					break;
				
				case 'id';
				case 'kid':
				    $this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
				    $this->rawWhere .= "x_kurse.kurs_id =$value";
				case 'fav':
				case 'favprint': // favprint is deprecated
					$ids = array();
					$temp = $this->tokens['cond'][$i]['field']=='fav'? $_COOKIE['fav'] : $value;
					$temp = explode(',', strtr($temp, ' /',',,'));
					for( $j = 0; $j < sizeof($temp); $j++ ) {
						$ids[] = intval($temp[$j]); // safely get the IDs - do not use the Cookie/Request-String directly!
					}
					
					$this->rawCanCache = false;
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( sizeof($ids) >= 1 ) {
						$this->rawWhere .= "(x_kurse.kurs_id IN (".implode(',', $ids)."))";
						$abgelaufeneKurseAnzeigen = 'void';
					}
					else {
						$this->rawWhere .= '(0)';
					}
					break;
				
				case 'nr':
					// search for durchfuehrungsnummer
				    $ids = $this->nr2id($value);
					$this->rawCanCache = false; // no caching as we have different results for login/no login
					$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
					if( sizeof($ids) >= 1 ) {
						$this->rawWhere .= "(x_kurse.kurs_id IN (".implode(',', $ids)."))";
						$abgelaufeneKurseAnzeigen = 'void'; // implicitly show expired results if a number was searched
					}
					else {
						$this->rawWhere .= '(0)';
					}							
					break;
					
				case 'anbieter_tag':
				    // search for anbieter_ids
				    $k_ids = $this->anbieter_tag2k_ids($value);
				    $this->rawCanCache = true;
				    $this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
				    if( sizeof($k_ids) >= 1 ) {
				        $this->rawWhere .= "(x_kurse.kurs_id IN (".implode(',', $k_ids)."))";
				    }
				    else {
				        $this->rawWhere .= '(0)';
				    }
				    break;
				
				case 'bei':
				    if( preg_match('/^\s*(\d+(\.\d+)?)\s*\/\s*(\d+(\.\d+)?)\s*$/', $value, $matches) ) // angabe lat/lng
				    {
				        $lat = floatval($matches[1]);
				        $lng = floatval($matches[3]);
				        $gi = array('direct_lat'=>$lat, 'direct_lng'=>$lng); // just for nicer debug view
				    }
				    else
				    {
				        $obj =& createWisyObject('WISY_OPENSTREETMAP_CLASS', $this->framework);
				        $value = (mb_detect_encoding($value, 'ISO-8859-1', true) ? utf8_encode($value) : $value);
				        $gi = $obj->geocode2(array('free'=>str_replace('/', ',', $value))); // in Adressen muss der Schraegstrich anstelle des Kommas verwendet werden (das Komma trennt ja schon die verschiedenenn Suchkriterien)
				        
				        if( $gi['error'] ) {
				            $this->error = array('id'=>'bad_location', 'field'=>$value, 'status'=>404);
				        }
				        else {
				            $lat = $gi['lat'];
				            $lng = $gi['lng'];
				            $this->foundVenue = $gi['DISPLAY_NAME'];
				        }
				    }
				    
				    if($_GET['debug'] == "ort") {
				        echo "<br>Gesuchter Ort:<br><b>".(mb_detect_encoding($value, 'UTF-8', true) ? utf8_decode($value): $value)."</b><br><br>"
				            ."Umlaut-Codierung Deutsch (ISO-8859-1)?:<br><b>".(mb_detect_encoding($value, 'ISO-8859-1', true) ? 'ja' : 'nein')."</b><br><br>"
				            ."Umlaut-Codierung Deutsch (UTF-8)?:<br><b>".(mb_detect_encoding($value, 'UTF-8', true) ? 'ja' : 'nein')."</b><br><br>"
				            .(is_array($gi) ? "Error:<br><b>".$gi['error']."</b>,<br><br>Anfrage an Geodkodierungsdienst war:<br><b>".$gi['url']."</b>" : '');
				    }
				    
				    if( !is_array($this->error) )
				    {
				        $radius_meters = $km * 1000.0;
				        
				        $radius_lat = $radius_meters / 111320.0; // Abstand zwischen zwei Breitengraden: 111,32 km  (weltweit)
				        $radius_lng = $radius_meters /  71460.0; // Abstand zwischen zwei Laengengraden :  71,46 km  (im mittel in Deutschland)
				        
				        $min_lat = intval( ($lat - $radius_lat)*1000000 );
				        $max_lat = intval( ($lat + $radius_lat)*1000000 );
				        $min_lng = intval( ($lng - $radius_lng)*1000000 );
				        $max_lng = intval( ($lng + $radius_lng)*1000000 );
				        
				        $this->rawJoin  .= " LEFT JOIN x_kurse_latlng j$i ON x_kurse.kurs_id=j$i.kurs_id";
				        $this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
				        $this->rawWhere .= "((j$i.lat BETWEEN $min_lat AND $max_lat) AND (j$i.lng BETWEEN $min_lng AND $max_lng))"; // lt. https://dev.mysql.com/doc/refman/4.1/en/mysql-indexes.html wird der B-Tree auch fuer groesser/kleiner oder BETWEEN abfragen verwendet.
				        
				        if( isset($_COOKIE['debug']) )
				        {
				            echo '<p style="background-color: orange;">gi: ' . htmlspecialchars(print_r($gi, true)) . '</p>';
				        }
				        
				        // remember some stuff for the getInfo() function (needed eg. for the "distance"-column)
				        $this->last_lat = $lat;
				        $this->last_lng = $lng;
				    }
				    break;
				
				case 'km':
					if( !$has_bei )
					{
						$this->error = array('id'=>'km_without_bei');
					}
					else if( $km == 0.0 )
					{
						$this->error = array('id'=>'bad_km', 'max_km'=>$max_km, 'default_km'=>$default_km);
					}
					break;
				
				case 'datum':
					if( strtolower($value) == 'alles' )
					{
						$abgelaufeneKurseAnzeigen = 'yes';
					}
					else if( preg_match('/^heute([+-][0-9]{1,5})?$/i', $value, $matches) )
					{
						$offset = intval($matches[1]);
						$abgelaufeneKurseAnzeigen = 'void';
						$todayMidnight = strtotime(strftime("%Y-%m-%d"));
						$wantedday = strftime("%Y-%m-%d", $todayMidnight + $offset*24*60*60);
						$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
						$this->rawWhere .= "(x_kurse.beginn_last>='$wantedday')"; // 13:58 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
					}
					else if( preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{2,4})$/', $value, $matches) )
					{
						$day = intval($matches[1]);
						$month = intval($matches[2]);
						$year = intval($matches[3]); if( $year <= 99 ) $year += 2000;
						$timestamp = mktime(0, 0, 0, $month, $day, $year);
						if( $timestamp <= 0 )
						{
							$this->error = array('id'=>'invalid_date', 'date'=>$value) ;
						}
						else
						{
						    // $abgelaufeneKurseAnzeigen = 'void';
						    $abgelaufeneKurseAnzeigen = 'yes';
							$wantedday = strftime("%Y-%m-%d", $timestamp);
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.beginn_last>='$wantedday')"; // 13:59 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
						}
					}
					else
					{
						$this->error = array('id'=>'invalid_date', 'field'=>$value) ;
					}
					break;
				
				case 'dauer':
					$dauer_error = true;
					if( preg_match('/^([0-9]{1,9})$/', $value, $matches) )
					{	
						$dauer = intval($matches[1]);
						if( $dauer > 0 )
						{
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.dauer!=0 AND x_kurse.dauer<=$dauer)";
							$dauer_error = false;
						}
					}
					else if( preg_match('/^([0-9]{1,9})\s?-\s?([0-9]{1,9})$/', $value, $matches) )
					{	
						$dauer1 = intval($matches[1]);
						$dauer2 = intval($matches[2]);
						if( $dauer1 > 0 && $dauer2 > 0 && $dauer1 <= $dauer2 )
						{
							$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
							$this->rawWhere .= "(x_kurse.dauer>=$dauer1 AND x_kurse.dauer<=$dauer2)";
							$dauer_error = false;
						}
					}
					
					if( $dauer_error )
					{
						$this->error = array('id'=>'invalid_dauer', 'field'=>$value) ;
					}
					break;
				
				case 'volltext':
				    // volltextsuche, aktuell gibt es ein Volltextindex ueber kurse.titel und kurse.beschreibung; dieser
				    // wird vom core10 *nicht* verwendet und vom redaktionssystem wohl eher selten.
				    // aktuell nehmen wird diesen Index einfach, sollten wir hier aber etwas anderes benoetigen,
				    // kann der alte Volltextindex verworfen werden. ALSO:
				    global $ignoreWords_DE;
				    
				    $this->min_chars = $this->getMinChars();
				    
				    if( strlen(trim($value)) >= $this->min_chars && !in_array($value, $ignoreWords_DE) )
				    {
				        if(strpos($value, '.') !== FALSE) // char "." in (valid) search terms is being misinterpreted without quotes
				            $value = '"'.$value.'"';
				            
				            $this->rawJoinKurse = " LEFT JOIN kurse ON x_kurse.kurs_id=kurse.id";	 // this join is needed only to query COUNT(*)
				            
				            $fulltext_excactmatch = addslashes(trim($value));
				            
				            // ignore some irrelevant words
				            $fulltext_matchall = $this->framework->replaceWords($ignoreWords_DE, $fulltext_excactmatch);
				            
				            
				            $this->fulltext_select = ", CASE WHEN kurse.titel LIKE '%".addslashes(trim($value))."%' THEN 1 ELSE 0 END AS title_relevance"
				                .", CASE WHEN kurse.beschreibung LIKE '%".addslashes(trim($value))."%' THEN 1 ELSE 0 END AS beschreibung_relevance"
				                .", '".addslashes(trim($value))."' AS fulltext_query"
				                .", beschreibung"
				                .", '".$this->min_chars."' AS min_chars" // query_string (cleaned of irrelevant words)
				                .", '".$fulltext_matchall."' AS fulltext_matchall"; // query_string (cleaned of irrelevant words)
				                            
				                $this->rawWhere    .= $this->rawWhere? ' AND ' : ' WHERE ';
				                            
				                            
				                $this->rawWhere    .= "( kurse.titel LIKE '%".$fulltext_excactmatch."%' OR kurse.beschreibung LIKE '%".$fulltext_excactmatch."%' "
				                                   ."OR MATCH(kurse.titel,kurse.beschreibung) AGAINST('".$fulltext_matchall."' IN BOOLEAN MODE) )";				                                
				    }
				    else
				    {
				        if( strlen($value) > 0 && strlen($value) < $this->min_chars)
				            $this->error = array('id'=>'tooshort_fulltext', 'help'=>'Suchbegriff muss mindestens '.$this->min_chars.' Buchstaben aufweisen.');
				            elseif( in_array($value, $ignoreWords_DE) )
				            $this->error = array('id'=>'ignored_fulltext', 'help'=>'Suchbegriff wurde als F&uuml;llwort o. &auml;. erkannt, welches keine ausreichend spezifischen Ergebnisse liefern w&uuml;rde.');
				            else
				                $this->error = array('id'=>'missing_fulltext', 'help'=>'Suchbegriff darf nicht leer sein.');
				    }
				    break;
				    
				default:
				    $this->error = array('id'=>'field_not_found', 'field'=>$this->tokens['cond'][$i]['field']) ;
				    break;
		        }
		}
		
		if(count($tag_heap))
		    $this->globalTagHeap = $tag_heap;
		
		/* -- leere Anfragen sind fuer "diese kurse beginnen morgen" notwendig, leere Anfragen sind _kein_ Fehler!
		if( !is_array($this->error) && $this->rawWhere=='' )
		{
			$this->error = array('id'=>'empty_query');
		}
		*/

		// finalize SQL
		if( !is_array($this->error) )
		{
			if( $abgelaufeneKurseAnzeigen == 'no' )
			{
				$today = strftime("%Y-%m-%d");
				
				$this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
				$this->rawWhere .= "(x_kurse.beginn>='$today')"; // 13:59 30.01.2013: war: x_kurse.beginn='0000-00-00' OR ...
			}
		}
	}
	
	public function getDoubleTags() {
	    return $this->double_tags;
	}
	
	public function getTagHeap() {
	    // return what was actually searched for
	    return $this->globalTagHeap;
	}
	
	function ok()
	{
		// check if there is an error or not - empty queries or results are no errors!
		return !is_array($this->error);
	}

	function getInfo()
	{
		// return an information-array
		// NOTE: "no records found" is no error if the query is fine. 
		return array(
			'show'		=>	$this->tokens['show'],
			'error'		=>	$this->error,
			'secneeded'	=>	$this->secneeded,
			'lat'		=>	$this->last_lat,
			'lng'		=>	$this->last_lng,
		);
			
	}
	
	public function getFoundVenue() {
	    return $this->foundVenue;
	}

	function getKurseCount()
	{
		$ret = 0;
		
		if( $this->error === false )
		{
			$start = $this->framework->microtime_float();

				global $wisyPortalId;
				$do_recreate = true;
				$cacheKey = "wisysearch.$wisyPortalId.$this->queryString.count";
				if( $this->rawCanCache && ($temp=$this->dbCache->lookup($cacheKey))!='' )
				{
					$ret = unserialize($temp);
					if( $ret === false )
					{
						if( isset($_COOKIE['debug']) ) {
							echo "<p style=\"background-color: yellow;\">getKurseCount(): bad counts for key <i>$cacheKey</i>, recreating  ...</p>";
						}
					}
					else
					{
						$do_recreate = false;
						if( isset($_COOKIE['debug']) ) {
							echo "<p style=\"background-color: yellow;\">getKurseCount(): counts for key <i>$cacheKey</i> loaded from cache ...</p>";
						}
					}
				}
							
				if( $do_recreate )
				{
					$sql = "SELECT COUNT(DISTINCT x_kurse.kurs_id) AS cnt FROM x_kurse " . $this->rawJoinKurse . $this->rawJoin . $this->rawWhere;
					$this->db->query("SET SQL_BIG_SELECTS=1"); // optional
					$this->db->query($sql);
					if( $this->db->next_record() )
						$ret = intval($this->db->f('cnt'));
					$this->db->free();
					
					// add to cache
					$this->dbCache->insert($cacheKey, serialize($ret));

					if( isset($_COOKIE['debug']) ) {
						echo '<p style="background-color: yellow;">getKurseCount(): ' .htmlspecialchars($sql). '</p>';
					}
				}
					
				if( isset($_COOKIE['debug']) ) {
					echo '<p style="background-color: yellow;">getKurseCount(): ' .$ret. '</p>';
				}
							
			$this->secneeded += $this->framework->microtime_float() - $start;
		}
		
		return $ret;
	}
	
	function getKurseRecordsSql($fields)
	{	
	    /* if(strpos($this->rawWhere, "kurse.freigeschaltet") === FALSE) {
	     $this->rawWhere .= $this->rawWhere? ' AND ' : ' WHERE ';
	     $this->rawWhere .= "kurse.freigeschaltet <> 0 AND kurse.freigeschaltet <> 2"; // never show "In Vorbereitung" or "Gesperrt"
	     } */
	    
		// create complete SQL query
		$sql =  "SELECT DISTINCT $fields
				   FROM kurse LEFT JOIN x_kurse ON x_kurse.kurs_id=kurse.id " . $this->rawJoin . $this->rawWhere;
		
		return $sql;		
	}
	
	function getKurseRecords($offset, $rows, $orderBy)
	{
	    
	    $ret = array('records'=>array());
	    
	    if( $this->error === false )
	    {
	        $start = $this->framework->microtime_float();
	        
	        global $wisyPortalId;
	        $do_recreate = true;
	        $cacheKey = "wisysearch.$wisyPortalId.$this->queryString.$offset.$rows.$orderBy";
	        if( $this->rawCanCache && ($temp=$this->dbCache->lookup($cacheKey))!='' )
	        {
	            // result in cache :-)
	            $ret = unserialize($temp);
	            if( $ret === false )
	            {
	                if( isset($_COOKIE['debug']) ) {
	                    echo "<p style=\"background-color: yellow;\">getKurseRecords(): bad result for key <i>$cacheKey</i>, recreating  ...</p>";
	                }
	            }
	            else
	            {
	                $do_recreate = false;
	                if( isset($_COOKIE['debug']) ) {
	                    echo "<p style=\"background-color: yellow;\">getKurseRecords(): result for key <i>$cacheKey</i> loaded from cache ...</p>";
	                }
	            }
	        }
	        
	        // if($this->radius_select)
	            // $do_recreate = true;
	        
	        
	        if( $do_recreate )
	        {
	            switch( $orderBy )
	            {
	                case 'a':		$orderBy = "x_kurse.anbieter_sortonly";						break;	// sortiere nach anbieter
	                case 'ad':		$orderBy = "x_kurse.anbieter_sortonly DESC";				break;
	                case 't':		$orderBy = 'kurse.titel_sorted';							break;	// sortiere nach titel
	                case 'td':		$orderBy = 'kurse.titel_sorted DESC';						break;
	                case 'b':		$orderBy = "x_kurse.beginn='0000-00-00', x_kurse.beginn";		break;	// sortiere nach beginn, spezielle Daten ans Ende der Liste verschieben
	                case 'bd':		$orderBy = "x_kurse.beginn='9999-09-09', x_kurse.beginn DESC";	break;
	                case 'd':		$orderBy = 'x_kurse.dauer=0, x_kurse.dauer';				break;	// sortiere nach dauer
	                case 'dd':		$orderBy = 'x_kurse.dauer DESC';							break;
	                case 'p':		$orderBy = 'x_kurse.preis=-1, x_kurse.preis';				break;	// sortiere nach preis
	                case 'pd':		$orderBy = 'x_kurse.preis DESC';							break;
	                case 'o':		$orderBy = "x_kurse.ort_sortonly='', x_kurse.ort_sortonly";	break;	// sortiere nach ort
	                case 'od':		$orderBy = "x_kurse.ort_sortonly DESC";						break;
	                case 'creat':	$orderBy = 'x_kurse.begmod_date';							break;	// sortiere nach beginnaenderungsdatum (hauptsaechlich fuer die RSS-Feeds interessant)
	                case 'creatd':	$orderBy = 'x_kurse.begmod_date DESC';						break;
	                case 'rand':
	                    $ip = str_replace('.', '', $_SERVER['REMOTE_ADDR']);
						try
						{
                            $seed = ((int)$ip + (int)date('d') );
						}
	                    catch(Exception $e)
						{
							$seed = 1;
						}
	                    $this->randSeed;
	                    $orderBy = 'RAND('.$seed.')';
	                    break;
	                default:		$orderBy = 'kurse.id';										die('invalid order!');
	            }
	            
	            if($this->fulltext_select)
	                $orderBy = "title_relevance DESC, beschreibung_relevance DESC";
	                
	                $sql = $this->getKurseRecordsSql("kurse.id, kurse.user_grp, kurse.anbieter, kurse.thema, kurse.freigeschaltet, kurse.titel, kurse.vollstaendigkeit, kurse.date_modified, kurse.bu_nummer, kurse.fu_knr, kurse.azwv_knr, x_kurse.begmod_date, x_kurse.bezirk, x_kurse.ort_sortonly, x_kurse.ort_sortonly_secondary".$this->fulltext_select);
	                
	                if($this->fulltext_select)
	                  $sql .= " ORDER BY $orderBy, RAND(".$this->randSeed.")";
	                else
	                  $sql .= " ORDER BY $orderBy, vollstaendigkeit DESC, x_kurse.kurs_id ";
	                        
	                if($this->fulltext_select) {
	                 $sql_nolimit = $sql;
	                 if($rows != 0) {
	                   $sql .= " LIMIT $offset, $rows ";
	                 }
	                }
	                else {
	                   if($rows != 0) $sql .= " LIMIT $offset, $rows ";
	                }
	                        

	                $this->db->query("SET SQL_BIG_SELECTS=1"); // optional
	                $this->db->query($sql);
	                            
	                if($this->fulltext_select) {
	                   $db_cnt = new DB_Admin();
	                                
	                   $db_cnt->query('SELECT COUNT(id) AS cnt_bothRelevance FROM ('.$sql_nolimit.') AS t WHERE t.title_relevance = 1 AND t.beschreibung_relevance = 1');
	                   if( $db_cnt->next_record() )
	                       $ret['meta']['cnt_bothRelevance'] = $db_cnt->f("cnt_bothRelevance");
	                                    
	                   $db_cnt->query('SELECT COUNT(id) AS cnt_titleRelevance FROM ('.$sql_nolimit.') AS t WHERE t.title_relevance = 1 AND t.beschreibung_relevance = 0');
	                   if( $db_cnt->next_record() )
	                       $ret['meta']['cnt_titleRelevance'] = $db_cnt->f("cnt_titleRelevance");
	                                        
	                   $db_cnt->query('SELECT COUNT(id) AS cnt_beschreibungRelevance FROM ('.$sql_nolimit.') AS t WHERE t.title_relevance = 0 AND t.beschreibung_relevance = 1');
	                   if( $db_cnt->next_record() )
	                       $ret['meta']['cnt_beschreibungRelevance'] = $db_cnt->f("cnt_beschreibungRelevance");
	                                            
	                   $db_cnt->query('SELECT COUNT(id) AS cnt_oneRelevance FROM ('.$sql_nolimit.') AS t WHERE t.title_relevance = 0 AND t.beschreibung_relevance = 0');
	                   if( $db_cnt->next_record() )
	                       $ret['meta']['cnt_oneRelevance'] = $db_cnt->f("cnt_oneRelevance");
	                }
	                            
	                while( $this->db->next_record() ) {
	                   $ret['records'][] = $this->db->Record;
	                }
	                $this->db->free();
	                                
	                foreach($ret['records'] AS $record) {
	                   if($record['ort_sortonly_secondary'] != "") {
	                       $sub_venues = array_map("trim", explode(",", $record['ort_sortonly_secondary']));
	                       $cnt = 0;
	                       foreach($sub_venues AS $sub_venue) {
	                           // echo "<br>".$record['id']." - ".$record['ort_sortonly']." <-> ".$record['ort_sortonly_secondary'];
	                           // $cnt++;
	                           // echo "<br><br>";
	                           // $this->db->Record[id] = $this->db->Record[id]+chr($cnt);
	                           // print_r($this->db->Record);
	                           // $ret['records'][] = $this->db->Record;
	                       }
	                   }
	                 }
	                 echo "<br><br><br><br>";
	        
	                            
	        // add result to cache
	        $this->dbCache->insert($cacheKey, serialize($ret));
	                            
	        if( isset($_COOKIE['debug']) ) {
	           echo '<p style="background-color: yellow;">getKurseRecords(): ' .htmlspecialchars($sql). '</p>';
	        }
	     }
	        
	     $this->secneeded += $this->framework->microtime_float() - $start;
	  }
	    
	    return $ret;
	}

	function getAnbieterCount()
	{
		$ret = 0;
		
		if( $this->error === false )
		{
			$start = $this->framework->microtime_float();
			
				$sql = "SELECT DISTINCT kurse.anbieter FROM kurse LEFT JOIN x_kurse ON x_kurse.kurs_id=kurse.id " . $this->rawJoin . $this->rawWhere;
				$this->db->query($sql);
				while( $this->db->next_record() )
				{
					$this->anbieterIds .= $this->anbieterIds==''? '' :', ';
					$this->anbieterIds .= intval($this->db->f('anbieter'));
					$ret++;
				}
				$this->db->free();
			
			$this->secneeded += $this->framework->microtime_float() - $start;
		}
		
		return $ret;
	}

	function getAnbieterRecords($offset, $rows, $orderBy)
	{
		$ret = array('records'=>array());
		
		if( !isset($this->anbieterIds) )
		{
			$this->getAnbieterCount(); // this little HACK sets $this->anbieterIds ...
		}
		
		if( $this->error === false && $this->anbieterIds != '' )
		{
			// apply order
			switch( $orderBy )
			{
				// ...
				case 'a':		$orderBy = "anbieter.suchname_sorted";					break;
				case 'ad':		$orderBy = "anbieter.suchname_sorted DESC";				break;

				case 's':		$orderBy = "strasse";									break;
				case 'sd':		$orderBy = "strasse DESC";								break;

				case 'p':		$orderBy = "plz";										break;
				case 'pd':		$orderBy = "plz DESC";									break;

				case 'o':		$orderBy = "ort";										break;
				case 'od':		$orderBy = "ort DESC";									break;

				case 'h':		$orderBy = "homepage";									break;
				case 'hd':		$orderBy = "homepage DESC";								break;

				case 'e':		$orderBy = "anspr_email";								break;
				case 'ed':		$orderBy = "anspr_email DESC";							break;

				case 't':		$orderBy = "anspr_tel";									break;
				case 'td':		$orderBy = "anspr_tel DESC";							break;
				
				// sortiere nach erstellungsdatum (hauptsaechlich fuer die RSS-Feeds interessant)
				case 'creat':	$orderBy = 'date_created';								break;
				case 'creatd':	$orderBy = 'date_created DESC';							break;

				default:		$orderBy = 'anbieter.id';								die('invalid order!');
			}
			
			// create complete SQL query
			$sql =  "SELECT id, date_created, date_modified, suchname, strasse, plz, bezirk, ort, homepage, firmenportraet, anspr_email, anspr_tel, typ FROM anbieter WHERE anbieter.id IN($this->anbieterIds)";
			$sql .= " ORDER BY $orderBy, anbieter.id ";
			if($rows != 0) $sql .= " LIMIT $offset, $rows ";

			if( isset($_COOKIE['debug']) )
			{
				echo '<p style="background-color: yellow;">' .htmlspecialchars($sql). '</p>';
			}
			
			
			$start = $this->framework->microtime_float();
			
				$this->db->query($sql);
				while( $this->db->next_record() )
					$ret['records'][] = $this->db->Record;
				$this->db->free();
			
			$this->secneeded += $this->framework->microtime_float() - $start;
		}		
		
		return $ret;
	}


	/**************************************************************************
	 * tools
	 **************************************************************************/
	
	function tokenize($queryString)
	{
		// function takes a comma-separated query and splits it into tags
		// 
		// returns an array as follows
		// array(
		//		'show' 		=> 'anbieter'
		//    	'cond' => array(
		//    		[0] array('field'=>'tag', 'value'='englisch')
		//		),
		// )
		$ret = array(
			'show'		=>	'kurse',
			'cond'		=>	array(),
		);

		$queryArr = explode(',', strval( $queryString ));
		for( $i = 0; $i < sizeof((array) $queryArr); $i++ )
		{
			// get initial value to search tags for, remove multiple spaces
			$field = '';
			$value = trim($queryArr[$i]);
			while( strpos($value, '  ')!==false )
				$value = str_replace('  ', ' ', $value);
			
			// find out the field to search the value in (defaults to "tag:")
			if( ($p=strpos($value, ':'))!==false )
			{
				$field = strtolower(trim(substr($value, 0, $p)));
				$value = trim(substr($value, $p+1));
				
				if( $field == 'zeige' )
				{
					$ret['show'] = strtolower($value);
					continue;
				}
			}
			else if( $value != '' )
			{
				$field = 'tag';
			}

			// any token?
			if( $field!='' || $value!='' )
			{
				$ret['cond'][] = array('field'=>$field, 'value'=>$value);
			}
		}
		
		return $ret;
	}
	
	function lookupTag($tag_name)
	{
	    // search a single tag
	    $tag_name = trim($tag_name);
	    $tag_id = 0;
	    if( $tag_name != '' )
	    {
	        // $tag_name = utf8_encode($tag_name);
	        
	        
	        
	        $sql = "SELECT tag_id, tag_eigenschaften, tag_type FROM x_tags WHERE tag_name='".addslashes($tag_name)."' ";
	        
	        $this->db->query($sql);
	        if( $this->db->next_record() )
	        {
	            
	            $tag_type = $this->db->fcs8('tag_type');
	            if( $tag_type & 64 || $tag_type == 65 || $tag_type & 262144) // 131072 = 65 // == 65, weil 64 + 1 den Typ Abschlüsse (=1) mit abdecken würde
	            {
	                
	                // synonym - ein lookup klappt nur, wenn es nur _genau_ ein synonym gibt
	                $temp_id   = $this->db->f('tag_id');
	                $syn_ids = array();
	                $sql = "SELECT t.tag_id FROM x_tags t LEFT JOIN x_tags_syn s ON s.lemma_id=t.tag_id WHERE s.tag_id=$temp_id";
	                $this->db->query($sql);
	                
	                while( $this->db->next_record() )
	                {
	                    $tag_id = $this->db->f('tag_id');
	                    $syn_ids[] = $tag_id;
	                }
	                
	                if( sizeof( $syn_ids ) == 1 )
	                {
	                    $tag_id = $syn_ids[0]; /*directly follow 1-dest-only-synonyms*/
	                } else {
	                    $tag_id = implode("#", $syn_ids); // must be analyzed for # and exploded at place of usage
	                }
	            }
	            else
	            {
	                // normales lemma
	                $tag_id   = $this->db->f('tag_id');
	            }
	        }
	        
	    }
	    
	    return $tag_id;
	}
	
	
	/*	***************************************************** *
	Search for a (Durchfuehrungs)-Nr, return offer ID(s)
	* ***************************************************** */
	public function nr2id($nr)
	{
	    $nr = trim($nr);
	    $db_nr = new DB_Admin();
	    
	    $sql = "SELECT DISTINCT k.id" // DISTINCT is needed as there may be offers with double nr in different durchfuehrungen
	    . " FROM kurse k
			 LEFT JOIN kurse_durchfuehrung s ON k.id=s.primary_id
			 LEFT JOIN durchfuehrung d ON s.secondary_id=d.id
			 WHERE d.nr=".$db_nr->quote($nr);
	    
	    $editAnbieterId = $this->framework->getEditAnbieterId();
	    if( $editAnbieterId > 0 ) {
	        $sql .= ' AND k.anbieter='.intval($editAnbieterId);
	    }
	    
	    $ret = array();
	    $db_nr->query($sql);
	    
	    // currently unavailable for public
	    if(!$this->framework->editSessionStarted) {
	        /* if($db_nr->next_record()) {
	         echo '<div class="info_conflict">'
	         .'Es wurden nur Angebote gefunden, deren <b>Durchf&uuml;hrungsnummern</b> Ihren Suchbegriff enthalten.<br><br>'
	         .'Da diese Nummern jedoch den Angaben der Anbieter unterliegen und redaktionell nicht gepr&uuml;ft werden k&ouml;nnen, stehen jene, alternativen Suchergebnisse aus Gr&uuml;nden der Vergleichbarkeit leider nicht mehr zur Verf&uuml;gung!<br><br>'
	         .'Bitte versuchen Sie es mit einem alternativen Suchbegriff.'
	         .'</div>';
	         } */
	    } else {
	        while( $db_nr->next_record() )
	        {
	            $ret[] = $db_nr->fcs8('id');
	        }
	    }
	    
	    
	    return $ret;
	}
	

	/*	******************************************************************************* *
	 Search for a offer by provider ids from provaider tag id, return offer ID(s)
	 * ******************************************************************************* */
	public function anbieter_tag2k_ids($a_sw_id)
	{
	    $a_sw_id = trim($a_sw_id);
	    $db_a = new DB_Admin();
	    
	    // LEFT JOIN durchfuehrung d ON s.secondary_id=d.id
	    // DISTINCT is needed as there may be offers with double nr in different durchfuehrungen
	    $sql = "SELECT DISTINCT kurse.id FROM kurse "
	        .	"LEFT JOIN anbieter ON kurse.anbieter=anbieter.id "
	        .	"LEFT JOIN anbieter_stichwort ON anbieter.id=anbieter_stichwort.primary_id "
	        .	"WHERE anbieter_stichwort.attr_id=".$db_a->quote($a_sw_id);
	                
	        $editAnbieterId = $this->framework->getEditAnbieterId();
	        if( $editAnbieterId > 0 ) {
	           $sql .= ' AND k.anbieter='.intval($editAnbieterId);
	        }
	                
	        $ret = array();
	        $db_a->query($sql);
	                
	        while( $db_a->next_record() ) {
	           $ret[] = $db_a->fcs8('id');
	        }
	                
	        return $ret;
	}
	
	public function getMinChars() {
	    $db_globalsettings = new DB_Admin();
	    $db_globalsettings->query("SHOW VARIABLES LIKE 'ft_min_word_len'");
	    
	    if($db_globalsettings->next_record())
	        return $db_globalsettings->f("Value");
	        
	        return 0;
	}
	
	public function getFulltextSelect() {
	    return $this->fulltext_select;
	}

	public function getAssumedLocation() {
	    return $this->assumedLocation;
	}
};

