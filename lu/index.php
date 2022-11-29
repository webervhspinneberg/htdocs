<?php

// die(date("d.m.Y").": Wartungsarbeiten.");

/*******************************************************************************
WISY
********************************************************************************

Portals Main Entry Point

@author Bjoern Petersen

*******************************************************************************/

header('Referrer-Policy: origin-when-cross-origin');
header('X-Powered-By: Software');

define('IN_WISY', true);

// redirects
$url = 'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

if(strpos($url,'https://www.example.info/search?q=requested-term') !== false) {
    header("Location: https://portalx.example.info/search?q=target-term", true, 301); // permanent URL redirections
    exit();
}


if(substr(PHP_VERSION_ID, 0, 1) > 6)
    define('PHP7', true);
else
    define('PHP7', false);
        
// convert string to UTF-8 or not
function cs8($string) {
    if(PHP7)
        return $string;
    else
        return utf8_encode($string);
}


/*******************************************************************************
 Set any cookie, used to enable special features
*******************************************************************************/

// with &setcookie=name,val you can set a cookie to the given value
// with &setcookie=name you can remove a cookie
// an serverless alternative would be:
// javascript:d=new%20Date();d.setTime(d.getTime()+10*24*60*60*1000);document.cookie="cookiename=val;expires="+d.toGMTString();
if( isset($_GET['setcookie']) )
{
	$temp = explode(',', $_GET['setcookie']);
	if( sizeof($temp)>1 )
	{
		setcookie($temp[0], $temp[1], time()+864000); // expires in 10 days
		$_COOKIE[ $temp[0] ] = $temp[1];
	}
	else
	{
		setcookie($temp[0]);
		unset($_COOKIE[ $temp[0] ]);
	}
	
}



/*******************************************************************************
 Connect to the database
*******************************************************************************/

require_once("admin/sql_curr.inc.php");
require_once("admin/config/config.inc.php");
$db = new DB_Admin;



/*******************************************************************************
 Tools
*******************************************************************************/

// wrappers for PHP >= 5.4 with changed defaults for some functions
function isohtmlspecialchars($a, $f=ENT_COMPAT) { return htmlspecialchars($a, $f, 'ISO-8859-1'); }
function isohtmlentities    ($a, $f=ENT_COMPAT) { return htmlentities    ($a, $f, 'ISO-8859-1'); }

// temporary functions for switching between html5/html3.2 rendering
function html5($h) { return $GLOBALS['wisyPortalEinstellungen']['html5']? $h : ''; }
function html3($h) { return $GLOBALS['wisyPortalEinstellungen']['html5']? '' : $h; }

function explodeSettings__($in, &$out, $follow_includes)
{
	$in = strtr($in, "\r\t", "\n ");
	$in = explode("\n", $in);
	for( $i = 0; $i < sizeof($in); $i++ )
	{
		$equalPos = strpos($in[$i], '=');
		if( $equalPos )
		{
			$regKey = trim(substr($in[$i], 0, $equalPos));
			if( $regKey != '' )
			{
				$regValue = trim(substr($in[$i], $equalPos+1));
				if( $regKey == 'include' ) 
				{
					if( !$follow_includes ) {
						echo 'ERROR: includes inside includes are not allowed!'; // a die() would be too harsh ...
					}
					else if( !@file_exists($regValue) ) {
						echo "ERROR: the following include-file does not exists: $regValue"; // a die() would be too harsh ...
					}
					else {
						$infile = file_get_contents($regValue);
						explodeSettings__($infile, $out, false);
					}
				}
				else
				{
					$out[$regKey] = $regValue; // the key may be set with an empty value!
				}
			}
		}
	}
}
function explodeSettings($in)
{
	$out = array();
	explodeSettings__($in, $out, true);
	return $out;
}


function headerDoCache($seconds = 43200 /*12 hours*/)
{
	if( $seconds <= 0 )
	{
		// add header entries that make sure, the file is NOT cached
		header("Cache-Control: no-cache, must-revalidate");	// HTTP/1.1
		header("Pragma: no-cache");							// HTTP/1.0
	}
	else
	{
		// add header entries that make sure, the file IS CACHED
		header("Cache-Control: public");
		header('Expires: ' . gmdate("D, d M Y H:i:s", time()+intval($seconds)) . ' GMT');
	}
}

function fwd301($fwdTo)
{
	// wenn man nur "Location:" verwendet, wird von PHP der Code 302 versandt
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: $fwdTo");
	header("Cache-Control: max-age=259200"); // 3 days, to be able to change homepage in future
	header("Connection: close");
	exit();
}

function error404($msg = "")
{
	global $wisyCore;
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: text/html; charset=ISO8859-15');
	
	echo '<html>
			<head>
				<title>Fehler 404 - Seite nicht gefunden</title>
			</head>
			<body>
				<h1>Fehler 404 - Seite nicht gefunden</h1>
                <h2 style="color: darkgreen;">'.$msg.'</h2>
				<p>Entschuldigung, aber die von Ihnen gew&uuml;nschte Seite (<i>'.isohtmlspecialchars($_SERVER['REQUEST_URI']).'</i> in <i>/'.isohtmlspecialchars($wisyCore).'</i> auf <i>' .$_SERVER['HTTP_HOST']. '</i>) konnte leider nicht gefunden werden. Sie k&ouml;nnen jedoch ...
				<ul>
					<li><a href="http://'.$_SERVER['HTTP_HOST'].'">Die Startseite von '.$_SERVER['HTTP_HOST'].' aufrufen ...</a></li>
					<li><a href="javascript:history.back();">Zur&uuml;ck zur zuletzt besuchten Seite wechseln ...</a></li>
				</ul>
			</body>
		  </html>';
	exit();
}



/*******************************************************************************
 Find out the portal to use, load some basic settings
*******************************************************************************/

function selectPortalOrFwd301()
{
	global $db;
	$ist_domain = strtolower($_SERVER['HTTP_HOST']);

	// do forward by default - however, we skip forwarding on special domains
	$do_fwd = true;
	
	// some special domain handling
	if( substr($ist_domain, 0, 7)=='sandbox' ) // remove sandbox prefix
	{
	    if(preg_match("/sandbox[0-9]/i", substr($ist_domain, 0, 8)) && !preg_match("/sandbox[10-18]/i", substr($ist_domain, 0, 9)))
	        $ist_domain = substr($ist_domain, 8 + 1 /*dot or minus*/ );
	    elseif(preg_match("/sandbox[10-18]/i", substr($ist_domain, 0, 9)))
	        $ist_domain = substr($ist_domain, 9 + 1 /*dot or minus*/ );
		else
			$ist_domain = substr($ist_domain, 7 + 1 /*dot or minus*/ );
		$do_fwd = false;
	}
	else if( substr($ist_domain, -6)=='.local' ) // ... special domain needed for development
	{	
		$ist_domain = str_replace('.local', '.info', $ist_domain);
		$do_fwd = false;
	}
	else if( substr($_SERVER['REQUEST_URI'], 0, 5)=='/sync' ) // ... do not forward on sync as we may use special domains with more CPU-Time (as kursportal.domainfactory-kunde.de with 9 additional minutes CPU time)
	{
		$do_fwd = false;
	}
	
	// find all matching domains
	$sql = "SELECT * FROM portale WHERE status=1 AND domains LIKE '%" . addslashes(str_replace('www.', '', $ist_domain)) . "%';";
	$db->query($sql);
	while( $db->next_record() )
	{
		// as the LIKE above may give us by far too many results, we have to inspect the result carefully
		$domains = strtr($db->fs('domains'), ';,/*', '    '); // allow `:` for ports in domain names 
		$domains = explode(' ', $domains);
		$first_domain = '';
		for( $i = 0; $i < sizeof($domains); $i++ )
		{
			$domain = strtolower($domains[$i]);
			if( $domain != '' )
			{
				if( $first_domain == '' )
				{
					$first_domain = $domain;
				}
				
				if( $domain==$first_domain && $domain==$ist_domain )
				{
					return; // success - $db contains a pointer to the current portal now
				}
				else if( str_replace('www.', '', $domain)==str_replace('www.', '', $ist_domain) )
				{
					if( $do_fwd )
						fwd301("http://" . $first_domain . $_SERVER["REQUEST_URI"]);
					else
						return; // success - $db contains a pointer to the current portal now
				}
			}
		}
	}
	
	// find all matching domains with other status than "1" - in this case 404 on purpose (mainly for SEO)
	$sql = "SELECT * FROM portale WHERE status<>1 AND domains LIKE '%" . addslashes(str_replace('www.', '', $ist_domain)) . "%';";
	$db->query($sql);
	if( $db->next_record() ) {
	    $wisyPortalEinstellungen = explodeSettings($db->fs('einstellungen'));
	    error404($wisyPortalEinstellungen['error404.msg']);
	}
	    
	// nothing found at all - go to fallback (domain containing an "*") or show an error
	$sql = "SELECT * FROM portale WHERE status=1 AND domains LIKE '%*%';";
	$db->query($sql);
	if( $db->next_record() )
	{
		$domains = strtr($db->fs('domains'), ';,/*', '    '); // allow `:` for ports in domain names
		$domains = explode(' ', $domains);
		for( $i = 0; $i < sizeof($domains); $i++ )
		{
			$domain = strtolower($domains[$i]);
			if( $domain != '' )
			{
				if( $do_fwd )
					fwd301("http://" . $domain . $_SERVER["REQUEST_URI"]);
				else
					return; // success - $db contains a pointer to the current portal now
			}
		}
	}

	error404();
}

selectPortalOrFwd301();
$wisyPortalId				= intval($db->f('id'));
$wisyPortalModified			= $db->fs('date_modified');
$wisyPortalName				= $db->fs('name');
$wisyPortalKurzname			= $db->fs('kurzname');
$wisyPortalCSS				= trim($db->fs('css'))==''? 0 : 1;
$wisyPortalBodyStart		= stripslashes($db->f('bodystart'));
$wisyPortalEinstellungen	= explodeSettings($db->fs('einstellungen'));
$wisyPortalFilter			= explodeSettings($db->fs('filter'));
$wisyPortalEinstcache		= explodeSettings($db->fs('einstcache'));
$wisyPortalUserGrp          = $db->fs('user_grp'); 

define('DEF_STICHWORT_BILDUNGSURLAUB',	1);
define('DEF_STICHWORTTYP_QZERTIFIKAT',	4);



/*******************************************************************************
 Get the requested file
*******************************************************************************/


$wisyRequestedFile = 'index.php';
if( preg_match('#/([^/\?]+)#', $_SERVER['REQUEST_URI'], $temp) )
{
	$wisyRequestedFile = $temp[1];
}

$wisyRequestedExt = '';
if( ($temp=strrpos($wisyRequestedFile, '.')) !== false )
{
	$wisyRequestedExt = substr($wisyRequestedFile, $temp+1);
}



/*******************************************************************************
 Find out the core to use
*******************************************************************************/

$wisyCore = 'core20';
/*if( strval($_GET['filecore']) != '' )
 {
 // $wisyCore = 'core' . strval($_GET['filecore']);
 }
 else */
if( strval($_COOKIE['core']) != '' )
{
    $wisyCore = 'core' . strval($_COOKIE['core']);
}
else if( strval($wisyPortalEinstellungen['core']) != '' )
{
    $wisyCore = 'core' . strval($wisyPortalEinstellungen['core']);
}



/*******************************************************************************
 Forward to the requested file
*******************************************************************************/

$wisyMiniMime = array(
	'css'	=>	'text/css',
	'gif'	=>	'image/gif',
	'html'	=>	'text/html',
	'ico'	=>	'image/x-icon',
	'jpg'	=>	'image/jpeg',
	'js'	=>	'application/javascript',
	'php'	=>	'require_once',
	'png'	=>	'image/png',
	'txt'	=>	'text/plain',
);

if( @file_exists("$wisyCore/$wisyRequestedFile") )
{
    $temp = $wisyMiniMime[$wisyRequestedExt];
    if( $temp == 'require_once' )
    {
        require_once("$wisyCore/$wisyRequestedFile");
        exit();
    }
    else if( $temp != '' )
    {
        $requested_filepath = "$wisyCore/$wisyRequestedFile";
        header("Content-type: $temp");
        
        
        if($wisyRequestedExt == "css"
            || $wisyRequestedExt == "js"
            ) {
                $longer_expiration = (20); // 2 weeks // 86400*14
                headerDoCache($longer_expiration); // $default_expiration
                
                $filectime_orig = filectime($requested_filepath);
                $gzip = (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE);
                $requested_filepath_tmp = $requested_filepath.".tmp.gz";
                
                if(!is_file($requested_filepath_tmp) || ($filectime_orig > filectime($requested_filepath_tmp))) {
                    $contents = file_get_contents($requested_filepath);
                    $contents = gzencode($contents, 9, $gzip ? FORCE_GZIP : FORCE_DEFLATE);
                    file_put_contents($requested_filepath_tmp, $contents);
                } elseif($filectime_orig < filectime($requested_filepath_tmp) && $gzip) {
                    header ("Content-Encoding: gzip");
                    header ('Content-Length: ' . @filesize($requested_filepath_tmp));
                    readfile($requested_filepath_tmp);
                    exit(0);
                }
            } else {
                headerDoCache();
            }
            
            header("Content-length: " . @filesize($requested_filepath));
            
            // ob_start("ob_gzhandler");
            readfile($requested_filepath);
            // ob_end_flush();
            exit();
    }
}
else if( @file_exists("$wisyCore/main.inc.php") )
{
    ini_set("zlib.output_compression", "On");
    require_once("$wisyCore/main.inc.php");
    exit(0);
}

/*******************************************************************************
 If we reach this part, we cannot handle the request -> 404
*******************************************************************************/

error404();

