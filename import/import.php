<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 50.000 Datensätze + je ein Kurs mit DF => 100.000 Aufrufe = ca. 10min
$dummy_kurs_anz = 10;

define('apikey', 'dtvpDGpSDzzm7XNVrO6ar5RignfacQCZ'); // Apikey aus etc.->API-KEYS 
define('client', 'localhost');

define('freigeschaltet', 1);
define('anbieter', 877001); // muss angelegt sein
define('thema', 10); // Themen-ID (nicht Kürzel) muss angelegt sein
define('stichwoerter', '838511, 834141, 836201'); // muss angelegt sein

define('user_created', 800181); // ID aus etc.->Benutzer
define('user_modified', 800181); // ID aus etc.->Benutzer
define('user_grp', 800001); // ID aus etc.->Benutzer
define('user_access', 504);

define('strasse', 'Am Rathaus 3');
define('ort', 'Pinneberg');
define('plz', 25421);

$base_url = 'https://' . $_SERVER['SERVER_NAME'] . '/api/v1/';


echo "Start: ".date("h:i:s d.m.Y")."<hr>";

$url_kurs = $base_url.'?scope=kurse&apikey='.apikey.'&client='.client;

for($i = 1; $i < $dummy_kurs_anz; $i++) {


 $data = array(
  'user_created' => user_created, 
  'user_modified' => user_modified,
  'user_grp' => user_grp,
  'user_access' => user_access,
  'freigeschaltet' => freigeschaltet,
  'thema' => thema,
  'stichwoerter' => stichwoerter,
  'titel' => 'Angebot '.$i,
  'beschreibung' => getRandomWord().' '.getRandomWord().' '.getRandomWord().' '.getRandomWord()
                    .' '.getRandomWord().' '.getRandomWord().' '.getRandomWord().' '.getRandomWord().' '.getRandomWord()
                    .' '.getRandomWord().' '.getRandomWord().' '.getRandomWord().' '.getRandomWord().' '.getRandomWord()
                    .' '.getRandomWord().' '.getRandomWord().' '.getRandomWord().' '.getRandomWord(),
  'anbieter' => anbieter
 );	


 // use key 'http' even if you send the request to https://...
 $options = array(
    'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data)
    ),
    'ssl' => array( // SSL-Zertifikate-Fehler ignorieren...
        "verify_peer"=>false,                                               
        "verify_peer_name"=>false             
    )
     
 );
 $context  = stream_context_create( $options );

 
 $result = file_get_contents($url_kurs, false, $context);

 if ($result === FALSE) { 
  echo "Fehler:<br>";
  echo "URL:".$url_kurs."<br>";
  echo "Context:<br>"; print_r( $options );
 }

 $result = json_decode($result, true);

 if( isset($result['id']) && $result['id']) {
     $url_df = $base_url . '?scope=kurse.'.$result['id'].'&apikey='.apikey.'&client='.client;
 
 	$df_data = array(
   		'user_created' => user_created,
   		'user_modified' => user_modified,
   		'user_grp' => user_grp,
   		'user_access' => user_access,
   		'nr' => getRandomWord(),
   		'stunden' => rand(1,30),
  	 	'teilnehmer' => rand(1,30),
   		'preis' => rand(1,300),
   		'preishinweise' => getRandomWord().' '.getRandomWord().' '.getRandomWord().' '.getRandomWord(),
   		'beginn' => (intval(date('Y'))+1)."-".rand(6,9)."-".rand(1,30)." ".rand(7,20).":".rand(1,59).":00",
   		'ende' => (intval(date('Y'))+1)."-".rand(10,12)."-".rand(1,30)." ".rand(7,20).":".rand(1,59).":00",   		
	    'zeit_von' => rand(7,12).":".rand(1,59),
	    'zeit_bis' => rand(13,22).":".rand(1,59),	    
	    'kurstage' => rand(1,50),	    	    
	    'strasse' => strasse,	    	    	    
	    'plz' => plz,
	    'ort' => ort,
   		'bemerkungen' => rand(2,50)." Vormittage"
 	);	

 	
 	$df_context  = stream_context_create( $options );
 	$df_result = file_get_contents($url_df, false, $df_context);

 	if ($df_result === FALSE) { 
  		echo "Fehler:<br>";
  		echo "URL:".$url_df."<br>";
  		echo "Context:<br>"; print_r( $options );
 	}
 	
 	$df_result = json_decode($df_result, true);
 
	if( isset($df_result['id']) && $df_result['id'])
 		echo $i . ") Kurs: ".$result['id']." -> DF: ".$df_result['id'].",<br>";  
 	

 } // end: if kurs eingefuegt
 


} // end: for



echo "<br><br><hr>Done: ".date("h:i:s d.m.Y");

function getRandomWord($len = 10) {
    $word = array_merge(range('a', 'z'), range('A', 'Z'));
    shuffle($word);
    return ucfirst(substr(implode($word), 0, $len));
}
 		
?>