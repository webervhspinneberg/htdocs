<?php if( !defined('IN_WISY') ) die('!IN_WISY');



class WISY_EDIT_RENDERER_CLASS
{
	var $framework;

	/**************************************************************************
	 * Tools / Misc.
	 *************************************************************************/
	
	function __construct(&$framework)
	{
		require_once('admin/config/codes.inc.php'); // needed for $codes_beginnoptionen, $codes_kurstage etc.
		require_once('admin/lang.inc.php');			
		require_once('admin/table_def.inc.php');	// needed for db.inc.php
		require_once('admin/config/db.inc.php');	// needed for LOG_WRITER_CLASS
		require_once('admin/date.inc.php');
		// require_once('admin/eql.inc.php');
		require_once('admin/classes.inc.php');
		require_once('admin/config/trigger_kurse.inc.php');
		require_once('admin/config/trigger_durchfuehrung.inc.php');

		// constructor
		$this->framework	=& $framework;
		$this->promoter 	=& createWisyObject('WISY_PROMOTE_CLASS', $this->framework);
		$this->tools		=& createWisyObject('WISY_EDIT_TOOLS_CLASS', $this->framework);
		
		
		
		// find out the "backward" location (where to go to if "OK" or "Cancel" is hit
		$this->bwd	= isset($_REQUEST['bwd']) ? $_REQUEST['bwd'] : '';
		if( isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '' )
		{
		    $this->bwd = $_SERVER['HTTP_REFERER'];
		}
		else if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'ek' )
		{
		    if( isset($_REQUEST['id']) )
		        $this->bwd = 'k' . intval($_REQUEST['id']);
		}
		else if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'ea' )
		{
		    $this->bwd = isset($_SESSION['loggedInAnbieterId']) ? 'a' . intval($_SESSION['loggedInAnbieterId']) : '';
		}
		
		
		// read some settings
		$this->billingRenderer =& createWisyObject('WISY_BILLING_RENDERER_CLASS', $this->framework);
	}

	protected function _anbieter_ini_read($key, $default='')
	{
		$return_value = $default;
		
		if( !isset($this->_anbieter_ini_settings) ) 
		{
			$this->_anbieter_ini_settings = array();
			$db = new DB_Admin;
			
			if( isset($_SESSION['loggedInAnbieterId']) )
			    $db->query("SELECT settings x FROM anbieter WHERE id=".intval($_SESSION['loggedInAnbieterId']));
			    
			if( $db->next_record() )
			{
				$this->_anbieter_ini_settings = explodeSettings($db->fs('x'));
			}
		}
		
		if( isset( $this->_anbieter_ini_settings[ $key ] ) )
		{
			$return_value = $this->_anbieter_ini_settings[ $key ];
		}
		
		return $return_value;		
	}
	
	protected function _anbieter_ini_write()
	{
		if( isset($this->_anbieter_ini_settings) ) 
		{
			$data = '';
			ksort($this->_anbieter_ini_settings);
			reset($this->_anbieter_ini_settings);
			foreach($this->_anbieter_ini_settings as $regKey => $regValue) 
			{
				$regKey		= strval($regKey);
				$regValue	= strval($regValue);
				if( $regKey!='' ) {
					$regValue = strtr($regValue, "\n\r\t", "   ");
					$data .= "$regKey=$regValue\n";
				}
			}
			
			$db = new DB_Admin;
			
			if( isset($_SESSION['loggedInAnbieterId']) )
			    $db->query("UPDATE anbieter SET settings='" .addslashes($data). "' WHERE id=".intval($_SESSION['loggedInAnbieterId']));
		}
	}
	
	function getAdminAnbieterUserIds()
	{
		// generische Anbieter ID fuer "grobe Aenderungen" (20) und "Bagatellaenderungen" (19)
		return array($this->getAdminAnbieterUserId20(), $this->getAdminAnbieterUserId19());
	}
	protected function getAdminAnbieterUserId20()
	{
		return 20;
	}
	protected function getAdminAnbieterUserId19()
	{
		return 19;
	}	

	protected function getAnbieterPwEinst()
	{
		if( !isset($this->cachePwEinst) )
		{
			$this->cachePwEinst = 0;
			$anbieter_id = $_SESSION['loggedInAnbieterId'] ? intval($_SESSION['loggedInAnbieterId']) : null;
			if( $anbieter_id )
			{
				$db = new DB_Admin;
				$db->query("SELECT pflege_pweinst FROM anbieter WHERE id=$anbieter_id;");
				if( $db->next_record() )
				{
					$this->cachePwEinst = $db->f('pflege_pweinst');
				}
			} 
		}

		return $this->cachePwEinst;
	}

	protected function canPromote()
	{
		if( !isset($this->cacheCanPromote) )
		{
			$this->cacheCanPromote = false;
			if( $this->framework->iniRead('useredit.promote', 0) != 0 )
			{
				$pw_einst = $this->getAnbieterPwEinst();
				if( ($pw_einst&1) && ($pw_einst&2) )
				{
					$this->cacheCanPromote = true;
				}
			}
		}
		
		return $this->cacheCanPromote;
	}
	
	protected function canEditBagatelleOnly()
	{
		$pw_einst = $this->getAnbieterPwEinst();
		if( !($pw_einst&1) /*check this for security reasons: normally, the user cannot even login without bit #0 set*/ 
		 ||  ($pw_einst&4) ) {
			return true;
		}
		return false;
	}

	function checkEmptyOnNull($val, &$error_array, $error)
	{
		$val = str_replace(' ', '', strtolower($val));
		if( $val=='k.a.' || $val=='ka.' || $val=='ka' ) {
			$val = '';
		}

		if( preg_match('/[^0-9]/', $val) || $val=='0' ) {
			$error_array[] .= $error;
			return 0;
		}
		return $val==''? 0 : intval($val);
	}

	function checkEmptyOnMinusOne($val, &$error_array, $error)
	{
		$val = str_replace(' ', '', strtolower($val));
		if( $val=='k.a.' || $val=='ka.' || $val=='ka' ) {
			$val = '';
		}
	
		if( preg_match('/[^0-9]/', $val) ) {
			$error_array[] .= $error;
			return 0;
		}
		return $val==''? -1 : intval($val);
	}
	
	function checkDate($val, &$error_array)
	{
		if( $val=='tt.mm.jjjj' )
		{
			$val = '';
		}

		$ret = sql_date_from_human($val, 'dateopt');
		if( $ret=='0000-00-00 00:00:00' && $val!='' )
		{
			$error_array[] = "Fehler: Ung&uuml;ltiges Datum <i>".isohtmlspecialchars($val)."</i> - geben Sie das Datum bitte in der Form <i>tt.mm.jjjj</i> an.";
			return $ret;
		}
		
		return $ret;
	}

	function checkTime($val, &$error_array)
	{
		if( $val=='hh:mm' )
		{
			$val = '';
		}

		list($hh, $mm) = explode(':', $val);
		$hh = intval($hh);
		$mm = intval($mm);
		if( $hh<0 || $hh>23 || $mm<0 || $mm>59 )
		{
			$error_array[] = "Fehler: Ung&uuml;ltige Zeitangabe <i>".isohtmlspecialchars($val)."</i> - geben Sie die Zeit bitte in der Form <i>hh:mm</i> an.";
			return $val; // error
		}
		
		if( $hh<10 ) $hh = "0$hh";
		if( $mm<10 ) $mm = "0$mm";
		return "$hh:$mm"; // success
	}

	function saveStichwort($kursId, $oldStichwId, $newStichwId)
	{
		if( $oldStichwId == $newStichwId )
		{
			// nothing to do
			return;
		}
	
		$db = new DB_Admin;
		if( $oldStichwId>0 && $newStichwId>0 )
		{
			// update stichwort
			$_SESSION['stockStichw'][$oldStichwId] = 1;
			$db->query("UPDATE kurse_stichwort SET attr_id=$newStichwId WHERE primary_id=$kursId AND attr_id=$oldStichwId;");
		}
		else if( $oldStichwId==0 && $newStichwId>0 )
		{
			// add stichwort
			$db->query("SELECT MAX(structure_pos) AS sp FROM kurse_stichwort WHERE primary_id=$kursId;");
			$db->next_record();
			$structurePos = intval($db->f('sp'))+1;
			$db->query("INSERT INTO kurse_stichwort (primary_id, attr_id, structure_pos) VALUES($kursId, $newStichwId, $structurePos);");
		}
		else if( $oldStichwId>0 && $newStichwId==0 )
		{
			// delete stichwort
			$_SESSION['stockStichw'][$oldStichwId] = 1;
			$db->query("DELETE FROM kurse_stichwort WHERE primary_id=$kursId AND attr_id=$oldStichwId;");
		}
	}
	
	function moeglicheAbschluesseUndFoerderungen(&$retAbschluesse, &$retFoerderungen)
	{
		// aktuell vom anbieter verwendete Stichwoerter suchen
		$alleStichw = '';
		$anbieter_id = isset($_SESSION['loggedInAnbieterId']) ? intval($_SESSION['loggedInAnbieterId']) : null;
		$db = new DB_Admin;
		$db->query("SELECT DISTINCT attr_id FROM kurse_stichwort LEFT JOIN kurse ON id=primary_id WHERE anbieter=$anbieter_id;");
		while($db->next_record() ) {
			$alleStichw .= ($alleStichw==''? '' : ', ') .  $db->f('attr_id'); 
		}
		
		// kuerzlich geloeschte stichworte hinzufuegen (falls z.B. der letzte Kurse mit einem best. Abschluss geloescht wurde - dieser Abschluss darf dann dennoch wieder vergeben werden)
		if( isset($_SESSION['stockStichw']) && is_array($_SESSION['stockStichw']) ) {
			reset($_SESSION['stockStichw']); 
			foreach(array_keys($_SESSION['stockStichw']) as $id) {
				$alleStichw .= ($alleStichw==''? '' : ', ') .  $id; 
			}
		}
		
		// liste moeglicher abschluesse/foerderungen erzeigen
		if( $alleStichw!='' )
		{
			$db->query("SELECT id, eigenschaften, stichwort FROM stichwoerter WHERE id IN($alleStichw) ORDER BY stichwort_sorted;");
			while( $db->next_record() )
			{
				$id = intval($db->f('id'));
				$eigenschaften = intval($db->f('eigenschaften'));
				$stichwort = $db->fs('stichwort');
				if( $eigenschaften & 1 )
				{
					$retAbschluesse .= ($retAbschluesse?'###' : '') . $id . '###' . isohtmlspecialchars($stichwort);
				}
				else if( $eigenschaften & 2 )
				{
					$retFoerderungen .= ($retFoerderungen?'###' : '') . $id . '###' . isohtmlspecialchars($stichwort);
				}
			}
		}
	}
	
	function controlHidden($name, $value)
	{
	    echo "<input type=\"hidden\" name=\"$name\" value=\"" . isohtmlentities(strval($value)) . "\" />" ;
	}
	
	function controlText($name, $value, $size = 8, $maxlen = 255, $tooltip = '', $valuehint = '')
	{
		$em = intval($size*.6 + .5);
		echo "<input style=\"width: {$em}em\" type=\"text\" name=\"$name\" value=\"" . isohtmlentities(strval( $value!=''? $value : $valuehint )) . "\" size=\"$size\" maxlength=\"$maxlen\" title=\"{$tooltip}\"";
		if( $valuehint ) {
			echo " onfocus=\"if(this.value=='$valuehint'){this.value='';this.className='normal';}return true;\"";
			echo " onblur=\"if(this.value==''){this.value='$valuehint';this.className='wisy_hinted';}return true;\"";
			echo ($value==''||$value==$valuehint)? ' class="wisy_hinted"' : ' class="normal"';
		}
		echo " />";
	}

	function controlSelect($name, $value, $values)
	{
		$values = explode('###', $values);
				
		echo "<select name=\"$name\" size=\"1\">";
			for( $v = 0; $v < sizeof($values); $v+=2 ) {
				echo '<option value="' .$values[$v]. '"';
				if( $values[$v] == $value ) {
					echo ' selected="selected"';
				}
				echo '>' .$values[$v+1]. '</option>';
			}
		echo '</select>';
	}

	function getToolbar()
	{
		$ret = '';
		
		$ret .= '<div class="wisy_edittoolbar">';
		
			// name / logout link
		    $name = isset($_SESSION['loggedInAnbieterSuchname']) ? $_SESSION['loggedInAnbieterSuchname'] : '';
		    $loggedInAnbieterId = isset($_SESSION['loggedInAnbieterId']) ? $_SESSION['loggedInAnbieterId'] : null;
			$maxlen = 30;
			if(strlen($name) > $maxlen ) $name = trim(substr($name, 0, $maxlen-5)) . '..';
			$ret .= '<div style="float: right;">eingeloggt als: '
			    .		'<a href="' .$this->framework->getUrl('a', array('id'=>$loggedInAnbieterId, 'q'=>$this->framework->getParam('q'))). '?editstart='.date("Y-m-d-h-i-s").'">' . isohtmlspecialchars($name) . '</a>';
				 
		    $loggedInAnbieterPflegemail = isset($_SESSION['loggedInAnbieterPflegemail']) && strlen($_SESSION['loggedInAnbieterPflegemail']) > 3;
			if( $loggedInAnbieterPflegemail ) {
			     $ret .= ' | <a href="#" onclick=\'resetPassword('.$loggedInAnbieterId.', "'.substr($loggedInAnbieterPflegemail, 0, 3).'...'
			          .substr($loggedInAnbieterPflegemail, strlen($loggedInAnbieterPflegemail)-6).'"); return false;\'>Neues Passwort anfordern!</a>';
			}
			else {
			 if( isset($_SESSION['_login_as']) && $_SESSION['_login_as'])
			     $ret .= ' <span class="pw_resetinfo">(Redaktionslogin)</span>';
		     else
			     $ret .= ' | <span class="pw_resetinfo">(F&uuml;r Passwort&auml;nderungen: Pflege-Email hinterlegen oder Portal-Betreiber schreiben.)</span>';
		    }
				 
		    $ret .= ' | <a href="'.$this->framework->getUrl('edit', array('action'=>'logout')) . '">Logout</a>'
			     .	'</div>';
				     
				     
		    $ret .= 'f&uuml;r Anbieter: ';

		    // link "meine kurse"
		    $q = (isset($_SESSION['loggedInAnbieterTag']) ? $_SESSION['loggedInAnbieterTag'] : '') . ', Datum:Alles';
		    $ret .=  '<a href="' . $this->framework->getUrl('search', array('q'=>$q)) . '">Alle Kurse</a>';
		    
		    // link "kurs bearbeiten"
		    global $wisyRequestedFile;
		    if( $wisyRequestedFile[0] == 'k' && ($kursId=intval(substr($wisyRequestedFile, 1))) > 0 )
		    {
				$db = new DB_Admin;
				$db->query("SELECT anbieter FROM kurse WHERE id=$kursId");
				if( $db->next_record() )
				{
					if( $this->framework->getEditAnbieterId() == $db->f('anbieter') )
					{
						$ret .=  ' | <a href="edit?action=ek&amp;id=' . $kursId . '">Kurs bearbeiten</a>';
					}
				}
			}
			else if( $GLOBALS['wisyRequestedFile'][0] == 'a' && ($_SESSION['loggedInAnbieterId'] == intval(substr($GLOBALS['wisyRequestedFile'], 1))) )
			{
				$ret .= ' | <a href="edit?action=ea">Profil bearbeiten</a> ';
			}
			
			// link "neuer kurs"
			$ret .=  ' | <a href="edit?action=ek&amp;id=0">Neuer Kurs</a>';
			
			// link "konto"
			if( $this->canPromote() )
			{
				$ret .=  ' | <a href="edit?action=kt">Konto</a>';
			}

			// link "hilfe"
			$ret .=  ' | <a href="' .$this->framework->getHelpUrl($this->framework->iniRead('useredit.help', '3371')). '" target="_blank" rel="noopener noreferrer">Hilfe</a>';
			
		$ret .=  '</div>';

		if( isset($_COOKIE['editmsg']) && $_COOKIE['editmsg'] != '' )
		{
			$ret .= "<p class=\"wisy_topnote\">" .  $_COOKIE['editmsg'] . "</p>";
			setcookie('editmsg', '');
		}
		
		return $ret;
	}

	protected function isEditable($kursId) /* returns 'yes', 'no' or 'loginneeded' */
	{
		if( $kursId == 0 ) 
		{
			return 'yes'; // new kurs - this must be editable
		}

		$db = new DB_Admin;
		$db->query("SELECT anbieter, freigeschaltet, user_created FROM kurse WHERE id=$kursId;");
		if( !$db->next_record() )
		{
			return 'no'; // bad record ID - not editable
		}
			
		if( !isset($_SESSION['loggedInAnbieterId']) || $db->fcs8('anbieter')!=$_SESSION['loggedInAnbieterId'] )
		{
			return 'loginneeded'; // may be editable, but bad login data
		}
		
		if( $db->f('freigeschaltet') == 1 /*freigeschaltet*/ 
		 || $db->f('freigeschaltet') == 4 /*dauerhaft*/ 
		 || $db->f('freigeschaltet') == 3 /*abgelaufen*/
		 ||	($db->f('freigeschaltet') == 0 /*in Vorbereitung*/ ) ) // Kurse in Vorbereitung sollen ueber Direktlinks editierbar sein, daher an dieser Stelle keine Ueberpruefung, ob der Kurs von getAdminAnbieterUserIds() angelegt wurde, s. https://mail.google.com/mail/#all/132aa92c4ec2cda7
		{
			return 'yes'; // editable
		}
		
		return 'no'; // not editable
	}
	
	
	/**************************************************************************
	 * Login / Logout
	 **************************************************************************/

	protected function renderLoginScreen()
	{
		// see what to do
		$db					= new DB_Admin;
		$fwd				= 'search';
		$anbieterSuchname	= '';
		$loginError			= '';
		
		if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'loginSubseq' && isset($_REQUEST['cancel']) )
		{
			header('Location: ' . $this->bwd);
			exit();
		}
		else if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'loginSubseq' )
		{
			// "OK" wurde angeklickt - loginversuch starten
		    $fwd 				= isset($_REQUEST['fwd']) ? strval( $_REQUEST['fwd'] ) : '';
		    $anbieterSuchname	= isset($_REQUEST['as']) ? strval( $_REQUEST['as'] ) : '';
			$anbieterSuchname_utf8dec = (PHP7 ? $anbieterSuchname : $anbieterSuchname);

			$logwriter = new LOG_WRITER_CLASS;
			$logwriter->addData('ip', $_SERVER['REMOTE_ADDR']);
			$logwriter->addData('browser', $_SERVER['HTTP_USER_AGENT']);
			$logwriter->addData('portal', $GLOBALS['wisyPortalId']);
			
			$loggedInAnbieterId = 0;
			$loggedInAnbieterSuchname = 0;
			$loggedInAnbieterPflegemail = "";

			// // Anbieter ID in name konvertieren
			if(is_numeric($anbieterSuchname_utf8dec)) {
				$db->query("SELECT suchname FROM anbieter WHERE id=".intval($anbieterSuchname_utf8dec)." AND freigeschaltet = 1");
				if( $db->next_record() ) {
					$anbieterSuchname = $db->fs('suchname');
					$anbieterSuchname_utf8dec = (PHP7 ? $anbieterSuchname : $anbieterSuchname);
				}
			} // end: is_numeric
				
			$login_as = false;
			if( ($p=strpos( strval( $_REQUEST['wepw'] ), '.')) !== false )
			{
			    // ...Login als registrierter Admin-Benutzer in der Form "<loginname>.<passwort>"
			    // KEINE Fehler fuer  diesen Bereich loggen - ansonsten wuerden wir u.U. Teile des Passworts loggen!
			    $temp[0] = substr( strval( $_REQUEST['wepw'] ), 0, $p);
			    $temp[1] = substr( strval( $_REQUEST['wepw'] ), $p+1);
					
					$sql = "SELECT password, id FROM user WHERE loginname='".addslashes($temp[0])."'";
					$db->query($sql);
					if( $db->next_record() )
					{
						$dbPw = $db->fs('password');
						if( crypt($temp[1], $dbPw) == $dbPw )
						{
							require_once('admin/acl.inc.php');
							if( acl_check_access('kurse.COMMON', -1, ACL_EDIT, $db->f('id')) )
							{
								$db->query("SELECT id FROM anbieter WHERE suchname='".addslashes($anbieterSuchname_utf8dec)."' AND freigeschaltet = 1");
								if( $db->next_record() )
								{
									$logwriter->addData('loginname', $temp[0] . ' as ' . $anbieterSuchname);
									$loggedInAnbieterId = intval($db->f('id'));
									$login_as = true;
								}
							}
						}
					}
			}
			
			
			if( $loggedInAnbieterId == 0 )
			{
				// ...Login als normaler Anbieter in der Form "<passwort>"
				$logwriter->addData('loginname', $anbieterSuchname);
				$db->query("SELECT pflege_email, pflege_passwort, pflege_pweinst, id FROM anbieter WHERE suchname='".addslashes($anbieterSuchname_utf8dec)."' AND freigeschaltet = 1");
				if( $db->next_record() )
				{
					$dbPw = $db->f('pflege_passwort');
					$dbPwEinst = intval($db->f('pflege_pweinst'));
					if( crypt( strval( $_REQUEST['wepw'] ), $dbPw) == $dbPw
					    && $dbPwEinst&1 /*freigeschaltet?*/ )
					{
						$loggedInAnbieterId = intval($db->f('id'));
						$loggedInAnbieterPflegemail = $db->f('pflege_email');
					}
					else
					{
						$logwriter->addData('msg', 'Anbieter "'.$anbieterSuchname.'" hat ein falsches Passwort eingegeben.');
					}
				}
				else
				{
					$logwriter->addData('msg', 'Anbieter "'.$anbieterSuchname.'" existiert nicht.');
				}
			}

			if( $loggedInAnbieterId == 0 )
			{
				$db->query("SELECT id FROM anbieter WHERE suchname='".addslashes($anbieterSuchname_utf8dec)."' AND freigeschaltet = 1");
				$db->next_record();
				$logwriter->log('anbieter', intval($db->f('id')), $this->getAdminAnbieterUserId20(), 'loginfailed');
				
				$loginError = 'bad_pw';
			}
			else if( !isset($_REQUEST['javascript']) || $_REQUEST['javascript'] != 'enabled' )
			{
				$loginError = 'no_js';
			}
			else
			{
				$this->framework->startEditSession();
				$_SESSION['loggedInAnbieterId'] = $loggedInAnbieterId;
				$_SESSION['loggedInAnbieterPflegemail'] = $loggedInAnbieterPflegemail;
				$_SESSION['loggedInAnbieterSuchname'] = $anbieterSuchname;
				$_SESSION['loggedInAnbieterTag'] = g_sync_removeSpecialChars($anbieterSuchname);
				$_SESSION['_login_as'] = $login_as;
				
				$logwriter->log('anbieter', $loggedInAnbieterId, $this->getAdminAnbieterUserId20(), 'login');
				
				if( $fwd == 'search' ) {
					$fwd = 'search?q=' . urlencode($anbieterSuchname . ', Datum:Alles'); // avoid using just /search which would lead to the homepage
				}
				
				$redirect = $fwd . (strpos($fwd, '?')===false? '?' : '&') . ('bwd='.urlencode($this->bwd));
				$protocol = $this->framework->iniRead('portal.https', '') ? "https" : "http";
				$redirect = $protocol.'://' . $_SERVER['HTTP_HOST'] . '/' . $redirect; // back to a normal connection
				header("Location: $redirect");
				exit(); // success - login done!
			}
		}
		else 
		{
			// erster Aufruf der Seite - initialisieren
		    if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'ek' )
			{
				$sql = "SELECT suchname FROM anbieter LEFT JOIN kurse ON kurse.anbieter=anbieter.id WHERE kurse.id=".intval($_REQUEST['id'])." AND anbieter.freigeschaltet = 1";
				$db->query($sql);
				if( $db->next_record() )
				{
					$anbieterSuchname = $db->fs('suchname');
					$anbieterSuchname_utf8dec = (PHP7 ? $anbieterSuchname : utf8_decode($anbieterSuchname));
				}
				
				if( isset($_REQUEST['id']) )
				    $fwd = "edit?action=ek&id=".intval($_REQUEST['id']);
				$secureLogin = $fwd;
			}
			else
			{
				if( isset($_REQUEST['fwd']) ) {
					$fwd 				= $_REQUEST['fwd'];
				}
				$anbieterSuchname = isset($_REQUEST['as']) ? $_REQUEST['as'] : '';
				$anbieterSuchname_utf8dec = (PHP7 ? $anbieterSuchname : utf8_decode($anbieterSuchname));
				$secureLogin = "edit?action=login&as=".urlencode($anbieterSuchname);
			}
			
			// first, see if we have to redirect to a secure login screen
			if( $this->framework->iniRead('useredit.secure', 1)==1 
			 && substr($_SERVER['HTTP_HOST'], -6)!='.local'
			 && $_SERVER['HTTPS']!='on' )
			{
				$redirect = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $secureLogin;
				header("Location: $redirect");
				exit();
			}
		}
		
		// render login form
		
		echo $this->framework->getPrologue(array('title'=>'Login', 'bodyClass'=>'wisyp_edit'));
		echo $this->framework->getSearchField();
		
			echo '<h1>Login</h1>';

			$showLoginForm = true;
			if( $loginError == 'bad_pw' )
			{
				echo '<p class="wisy_topnote"><b>Die Kombination aus Anbieter und Passwort ist unbekannt.</b> Bitte versuchen Sie es erneut. Beachten bei der Eingabe des Passwortes die Gro&szlig;-/Kleinschreibung und &uuml;berpr&uuml;fen Sie die Stellung der Feststelltaste.</p>';
			}
			else if( $loginError == 'no_js' )
			{
				$url = 'edit?as=' . urlencode($anbieterSuchname) . '&fwd=' .urlencode($fwd). '&bwd=' . urlencode($this->bwd);
				echo  '<p class="wisy_topnote">Um alle Funktionen im Login-Bereich nutzen zu k&ouml;nnen, <b>aktivieren Sie bitte jetzt Javascript in Ihrem Browser.</b> '
					. 'Danach <a href="'.isohtmlspecialchars($url).'">melden Sie sich bitte erneut an ...</a></p>';
				$showLoginForm = false;
			}
			else
			{
				echo '<p>Bitte geben Sie Ihre <b>Login-Daten</b> ein:</p>';
			}
	
			if( $showLoginForm )
			{
			    $secureaction = "";
			    
			    if( $this->framework->iniRead('useredit.secure', 1)==1
			        && substr($_SERVER['HTTP_HOST'], -6)!='.local'
			        && $_SERVER['HTTPS'] == "on")
			    {
			        $secureaction = 'https://' . $_SERVER['HTTP_HOST'] . '/edit';
			    }
			    
			    $editurl = ($secureaction != "") ? $secureaction : "edit";
			    
			    echo '<form action="edit" method="post">'; // '.$editurl.'
					echo '<table>';
						echo "<input type=\"hidden\" name=\"action\" value=\"loginSubseq\" />";
						echo "<script type=\"text/javascript\"><!--\ndocument.write('<input type=\"hidden\" name=\"javascript\" value=\"enabled\" />');\n/"."/--></script>";
						echo "<input type=\"hidden\" name=\"fwd\" value=\"".isohtmlspecialchars(strval($fwd))."\" />";
						echo "<input type=\"hidden\" name=\"bwd\" value=\"".isohtmlspecialchars(strval($this->bwd))."\" />";
						echo '<tr>';
							echo '<td nowrap="nowrap">Anbietername oder -ID:</td>';
							echo "<td><input type=\"text\" name=\"as\" value=\"".isohtmlspecialchars($anbieterSuchname)."\" size=\"40\" /></td>";
						echo '</tr>';
						echo '<tr>';
							echo '<td align="right">Passwort:</td>';
							echo '<td nowrap="nowrap">';
								echo '<input type="password" name="wepw" value="" size="30" />';
																																// der Anbietername wird _nicht_ weitergegeben, damit ein Missbrauch mehr als nur einen Klick erfordert.
								echo ' <a href="'.isohtmlspecialchars($this->framework->getUrl('edit', array('action'=>'forgotpw' /*, 'as'=>$anbieterSuchname*/))).'">Passwort vergessen?</a>';
								
							echo '</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td nowrap="nowrap">';
							echo '</td>';
							echo '<td nowrap="nowrap">';
								echo '<input type="submit" value="OK - Login" /> ';
								echo '<input type="submit" name="cancel" value="Abbruch" />';
							echo '</td>';
						echo '</tr>';
					echo '</table>';
				echo '</form>';
		
				// additional login message
				$temp = $this->framework->iniRead('useredit.loginmsg', '');
				if( $temp != '' )
				{
					echo '<p class="loginmsg">' . $temp . '</p>';
				}
			}

		echo $this->framework->getEpilogue();
	}
	
	function renderLogoutScreen()
	{
	    $loggedInAnbieterId = isset($_SESSION['loggedInAnbieterId']) ? $_SESSION['loggedInAnbieterId'] : null;
		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->log('anbieter', $loggedInAnbieterId, $this->getAdminAnbieterUserId20(), 'logout');
	
		session_destroy();
		setcookie($this->framework->editCookieName, '', 0, '/'); // remove cookie

		$redirect = "a".$loggedInAnbieterId;
		header('Location: '.$redirect);
	}
	

	
	
	/**************************************************************************
	 * Konto bearbeiten
	 **************************************************************************/

	function renderEditKonto()
	{
		echo $this->framework->getPrologue(array('title'=>'Ihr Konto', 'bodyClass'=>'wisyp_edit'));
		// echo $this->framework->getSearchField();

		if( !$this->canPromote() )
		{
			echo '<p class="wisy_topnote">Das Bewerben von Angeboten ist f&uuml;r dieses Portal und/oder diesen Anbieterzugang gesperrt. Bitte wenden Sie sich an den Systemadministrator, der Ihnen den Zugang zu diesem Bereich gew&auml;hrt hat.</p>';
			echo $this->framework->getEpilogue();
			exit();
		}
	    
		$loggedInAnbieterId = isset($_SESSION['loggedInAnbieterId']) ? $_SESSION['loggedInAnbieterId'] : null;
		
		$credits = $this->promoter->getCredits( $loggedInAnbieterId );
		echo "\n\n<h1>Kontostand: $credits Einblendungen</h1>\n";
		
		echo "<p>";
			echo "Ihr aktuelles Guthaben betr&auml;gt <b>$credits Einblendungen</b>.";
		echo "</p>";
		
		echo "<p>";
			echo "Mit Ihren Einblendungen k&ouml;nnen Sie beliebige Kurse in den Suchergebnissen an die ersten Stellen bringen. ";
			echo $this->billingRenderer->allPrices[0][0]." Einblendungen kosten aktuell&nbsp;<b>".str_replace('.', ',', $this->billingRenderer->allPrices[0][1])."&nbsp;&euro;</b>.";
		echo "</p>";
		
		echo "\n\n<h1>Einblendungen kaufen</h1>\n";

		$this->billingRenderer->renderButton($loggedInAnbieterId);
		
		echo "\n\n<h1>Beworbene Kurse</h1>\n";
		
		// WENN es kredite gibt, den Status der Tabelle anbieter_promote auf "aktiv" setzen, damit wieder Kurse geschaltet werden koennen
		$this->promoter->setAllPromotionsActive( $loggedInAnbieterId , $credits > 0? 1 : 0);
		
		
		echo '<br /><table border="1" cellspacing="0" cellpadding="6">';
			
			echo '<tr>';
				echo '<th>Titel</th>';
				echo '<th>Status</th>';
				echo '<th>Einstellung</th>';
			echo '</tr>';
			
			$showInactiveHints = false;
			$cnt = 0;
			global $wisyPortalId;
			$db = new DB_Admin;
			$db->query("SELECT kurse.id, titel, promote_active, promote_mode, promote_param FROM anbieter_promote LEFT JOIN kurse ON kurse.id=kurs_id WHERE anbieter_id=".$loggedInAnbieterId. " AND portal_id=$wisyPortalId ORDER BY titel;");
			while( $db->next_record() )
			{
				$kurs_id = intval($db->f('id'));
				$titel   = $db->f('titel');
				$promote_active  = intval($db->f('promote_active'));
				$promote_mode    = $db->f('promote_mode');
				$promote_param   = $db->f('promote_param');
				echo '<tr>';
					echo '<td valign="top">';
					
						echo '<a href="'.$this->framework->getUrl('edit', array('action'=>'ek', 'id'=>$kurs_id)).'">' . isohtmlspecialchars($titel) . '</a>';
						
					echo '</td><td valign="top" nowrap="nowrap">';
						
						if( $promote_active )
						{
							echo '<span style="color: #00C000;">Bewerbung aktiv</span>';
						}
						else
						{
							echo '<span style="color: #C00000;">Bewerbung inaktiv</span> (*)';
							$showInactiveHints = true;
						}
						
						
					echo '</td><td valign="top">';
					
						switch( $promote_mode )
						{
							case 'times':	echo "Bewerben mit $promote_param verbleibenden Einblendungen"; break;
							case 'date':	echo "Bewerben bis zum ".sql_date_to_human($promote_param, 'dateopt editable'); break;
						}
						
					echo '</td>';
				echo '</tr>';
				
				$cnt++;
			}
			
			if( $cnt == 0 )
			{
				echo '<tr><td colspan="3"><i>Derzeit werden keine Kurse beworben.</i></td></tr>';
			}
			
		echo '</table>';
		if( $showInactiveHints )
		{
			echo "(*) wenn eine Bewerbung inaktiv ist liegt dies entweder daran, dass kein Kredit mehr zur Verf&uuml;gung steht oder dass die Bedingung f&uuml;r die Bewerbung abgelaufen ist";
		}

		echo "<p>";
			echo "Um einen Kurs zu bewerben, klicken Sie beim Bearbeiten eines Kurses einfach auf \"Diesen Kurs bewerben\". ";
			echo "Kurse, die Sie nicht explizit beworben werden, tauchen wie gewohnt in den Suchergebnissen auf. ";
		echo "</p>";
		
		
		echo $this->framework->getEpilogue();
	}
	
	/**************************************************************************
	 * einzelnen Kurs bearbeiten / loeschen
	 **************************************************************************/
	
	function loadKursFromDb($kursId /* may be "0" for "new kurs"; defaults loaded in this case */ )
	{
		// kurs inkl. aller durchfuehrungen laden
		// 		das zurueckgegebene Array ist wie bei loadKursFromPOST() beschrieben formatiert
		
		// kursdatensatz und alle durchfuehrungen lesen
		$db = new DB_Admin;
		$db->query("SELECT * FROM kurse WHERE id=$kursId;");
		if( $db->next_record() )
		{
			$kurs = $db->Record;
			if( $db->f('freigeschaltet')==0 /*in vorbereitung*/ && $db->f('user_created')==$this->getAdminAnbieterUserId20() )
			{
				$kurs['rights_editTitel'] = true;
				$kurs['rights_editAbschluss'] = true;
			}
			
			$kurs['durchf'] = array();
			$db->query("SELECT * FROM durchfuehrung LEFT JOIN kurse_durchfuehrung ON durchfuehrung.id=kurse_durchfuehrung.secondary_id WHERE kurse_durchfuehrung.primary_id=$kursId ORDER BY kurse_durchfuehrung.structure_pos;");
			while( $db->next_record() )
			{
				$kurs['durchf'][] = $db->Record;
			}
		}
		else if( $kursId == 0 )
		{
			$kurs = array();
			$kurs['id'] = 0;
			$kurs['durchf'] = array();
			$kurs['durchf'][0]['id'] = 0;
			$kurs['rights_editTitel'] = true;
			$kurs['rights_editAbschluss'] = true;
		}
		else
		{
			$kurs['error'][] = 'Die angegebene Kurs-ID existiert nicht oder nicht mehr.';
			return $kurs;
		}
		
		// foerderung/abschlussart aus den stichwoertern extrahieren
		$kurs['abschluss']  = 0;
		$kurs['foerderung'] = 0;
		$db->query("SELECT s.id, s.eigenschaften FROM stichwoerter s LEFT JOIN kurse_stichwort ks ON s.id=ks.attr_id WHERE ks.primary_id=$kursId AND s.eigenschaften&3 ORDER BY ks.structure_pos;");
		while( $db->next_record() )
		{
			$eigenschaften = intval($db->f('eigenschaften'));
			$id = intval($db->f('id'));
			if( $eigenschaften&1 && $kurs['abschluss'] == 0 )  $kurs['abschluss'] = $id;
			if( $eigenschaften&2 && $kurs['foerderung'] == 0 )  $kurs['foerderung'] = $id;
		}

		// kreditinformationen laden
		global $wisyPortalId;
		$db->query("SELECT * FROM anbieter_promote WHERE kurs_id=$kursId AND portal_id=$wisyPortalId");
		$db->next_record(); // may be unexistant ... in this case the lines below evaluate to zero/emptyString
		$kurs['promote_active'] = intval($db->f('promote_active'));
		$kurs['promote_mode']   = $db->fs('promote_mode');
		$kurs['promote_param']  = $db->fs('promote_param');
		
		return $kurs;
	}

	function loadKursFromPOST($kursId /* may be "0" for "new kurs" */)
	{
		// kurs aus datanbank laden und mit den POST-Daten aktualisieren
		//
		// kurs ist ein array wie folgt:
		// 		$kurs['titel']
		// 		$kurs['beschreibung']
		//      $kurs['bu_nummer']
		//      $kurs['fu_knr']
		//      $kurs['azwv_knr']
		//		$kurs['msgtooperator']
		//		$kurs['error'][]					(array mit Fehlermeldungen)
		// 		$kurs['durchf'][0]['nr']
		// 		$kurs['durchf'][0]['beginn']		(und 'ende', 'zeit_von', 'zeit_bis', 'kurstage', 'beginnoptionen')
		// 		$kurs['durchf'][0]['stunden'] 	
		// 		$kurs['durchf'][0]['teilnehmer']
		// 		$kurs['durchf'][0]['preis']			(und 'sonderpreis', 'sonderpreistage', 'preishinweise')
		// 		$kurs['durchf'][0]['strasse']		(und 'plz', 'ort', 'stadtteil', 'bemerkungen')
		
		$kurs = $this->loadKursFromDb($kursId);
		if( sizeof((array) $kurs['error']) )
			return $kurs;
		
		$kurs['titel'] 			= $_POST['titel'];
		$kurs['beschreibung'] 	= $_POST['beschreibung'];
		$kurs['bu_nummer']    	= $_POST['bu_nummer'];
		$kurs['fu_knr']       	= $_POST['fu_knr'];
		$kurs['azwv_knr']     	= $_POST['azwv_knr'];
		$kurs['abschluss']		= intval($_POST['abschluss']);
		$kurs['foerderung']		= intval($_POST['foerderung']);
		$kurs['msgtooperator']	= $_POST['msgtooperator'];
		$kurs['durchf'] = array();
		for( $i = 0; $i < sizeof((array) $_POST['nr']); $i ++ )
		{	
			// id, if any (may be 0 for copied areas)
			$kurs['durchf'][$i]['id'] = intval($_POST['durchfid'][$i]);
			
			// nr
			$posted =  $_POST['nr'][$i];
			if( preg_match('/^k\s*\.?\s*\s*a\.?$/i' /*k. A.*/, $posted) ) { $posted = ''; };
			$kurs['durchf'][$i]['nr'] = $posted;
			
			// datum
			$kurs['durchf'][$i]['beginn'] 			= $this->checkDate( $_POST['beginn'][$i], $kurs['error']);
			$kurs['durchf'][$i]['ende'] 			= $this->checkDate( $_POST['ende'][$i], $kurs['error']);
			
			$kurs['durchf'][$i]['zeit_von'] 		= $this->checkTime( $_POST['zeit_von'][$i], $kurs['error']);
			$kurs['durchf'][$i]['zeit_bis'] 		= $this->checkTime( $_POST['zeit_bis'][$i], $kurs['error']);
						
			$kurs['durchf'][$i]['kurstage'] = intval(0);
			global $codes_kurstage;
			$bits = explode('###', $codes_kurstage);
			for( $j = 0; $j < sizeof($bits); $j += 2 )
			{
				if( intval($_POST["kurstage$j"][$i]) == 1 ) 
				{
					$kurs['durchf'][$i]['kurstage'] |= intval($bits[$j]);
				}
			}
			
			$kurs['durchf'][$i]['beginnoptionen'] 	= intval( $_POST['beginnoptionen'][$i]	);
			$kurs['durchf'][$i]['dauer'] 			= intval( $_POST['dauer'][$i]	);
			$kurs['durchf'][$i]['tagescode'] 		= intval( $_POST['tagescode'][$i]	);
			
			// stunden
			$kurs['durchf'][$i]['stunden'] 			= $this->checkEmptyOnNull($_POST['stunden'][$i], $kurs['error'], "Fehler: Ung&uuml;ltiger Wert f&uuml;r die Unterrichtsstunden; wenn Sie die Anzahl der Unterrichtsstunden nicht wissen, lassen Sie dieses Feld leer.");
			
			// teilnehmer
			$kurs['durchf'][$i]['teilnehmer'] 		= $this->checkEmptyOnNull($_POST['teilnehmer'][$i], $kurs['error'], "Fehler: Ung&uuml;ltiger Wert f&uuml;r die Teilnehmenden; wenn Sie die Anzahl der Teilnehmenden nicht wissen, lassen Sie dieses Feld leer.");
			
			// preis
			$kurs['durchf'][$i]['preis'] 			= $this->checkEmptyOnMinusOne($_POST['preis'][$i], $kurs['error'], "Fehler: Ung&uuml;ltiger Wert f&uuml;r den Preis; wenn Sie den Preis nicht kennen, lassen Sie dieses Feld leer; f&uuml;r &quot;kostenlos&quot; verwenden Sie bitte den Wert 0.");
			$kurs['durchf'][$i]['sonderpreis']		= $this->checkEmptyOnMinusOne($_POST['sonderpreis'][$i], $kurs['error'], "Fehler: Ung&uuml;ltiger Wert f&uuml;r den Sonderpreis; wenn Sie den Sonderpreis nicht verwenden m&ouml;chten, lassen Sie dieses Feld leer.");
			$kurs['durchf'][$i]['sonderpreistage'] 	= $this->checkEmptyOnNull($_POST['sonderpreistage'][$i], $kurs['error'], "Fehler: Ung&uuml;ltiger Wert f&uuml;r die Tage beim Sonderpreis; wenn Sie den Sonderpreis nicht verwenden m&ouml;chten, lassen Sie dieses Feld leer.");
			$kurs['durchf'][$i]['preishinweise'] 	=  $_POST['preishinweise'][$i];
			
			// ort
			$posted =  $_POST['strasse'][$i];
			if( $posted == 'Strasse und Hausnr.' ) $posted = '';
			$kurs['durchf'][$i]['strasse'] = $posted;
			
			$posted =  $_POST['plz'][$i];
			if( $posted == 'PLZ' ) $posted = '';
			$kurs['durchf'][$i]['plz'] = $posted;
			
			$posted =  $_POST['ort'][$i];	
			if( $posted == 'Ort' ) $posted = '';
			$kurs['durchf'][$i]['ort'] = $posted;

			if( ($kurs['durchf'][$i]['strasse'].','.$kurs['durchf'][$i]['plz'].','.$kurs['durchf'][$i]['ort']) == $_POST['stadtteil_for'][$i] )
				$kurs['durchf'][$i]['stadtteil'] = $_POST['stadtteil'][$i];
			else
				$kurs['durchf'][$i]['stadtteil'] = '';
			
			$kurs['durchf'][$i]['bemerkungen'] 		=  $_POST['bemerkungen'][$i]		;
			
			// additional data validation
			if( $kurs['durchf'][$i]['ende']!='0000-00-00 00:00:00' && $kurs['durchf'][$i]['beginn']!='0000-00-00 00:00:00' 
			 && $kurs['durchf'][$i]['ende']<$kurs['durchf'][$i]['beginn'] ) {
				$kurs['error'][] = "Fehler: Durchf&uuml;hrung ".($i+1).": Das Enddatum muss NACH dem Beginndatum liegen.";
			}

			$today = strftime("%Y-%m-%d %H:%M:%S");
			if( ($kurs['durchf'][$i]['ende']!='0000-00-00 00:00:00' && $kurs['durchf'][$i]['ende']<$today)
			 || ($kurs['durchf'][$i]['beginn']!='0000-00-00 00:00:00' && $kurs['durchf'][$i]['beginn']<$today) ) {
				$kurs['info'][] = "Kursbeginn und/oder Kursende liegen in der Vergangenheit.";
			}

			if( ($kurs['durchf'][$i]['sonderpreis']>=0 && $kurs['durchf'][$i]['sonderpreistage']==0)
			 || ($kurs['durchf'][$i]['sonderpreis']==-1 && $kurs['durchf'][$i]['sonderpreistage']>0) 
			 || ($kurs['durchf'][$i]['sonderpreis']>=0 && $kurs['durchf'][$i]['preis']==-1) ) {
				$kurs['error'][] = "Fehler: Um den Sonderpreis zu aktivieren, m&uuml;ssen Sie neben dem regul&auml;ren Preis die Anzahl der Tage und den Sonderpreis angeben.";
			}
			else if( $kurs['durchf'][$i]['sonderpreis']>=0 && $kurs['durchf'][$i]['sonderpreis']>=$kurs['durchf'][$i]['preis'] ) {
				$kurs['error'][] = "Fehler: Der Sonderpreis muss kleiner als der regul&auml;re Gesamtpreis sein.";
			}
		}

		$loggedInAnbieterId = isset($_SESSION['loggedInAnbieterId']) ? $_SESSION['loggedInAnbieterId'] : null;
		
		// additional data validation
		if( $kurs['rights_editTitel'] )
		{
			if( $kurs['titel'] == '' )
			{
				$kurs['error'][] = 'Fehler: Kein Kurstitel angegeben.';
			}
			else if( $kursId == 0 ) // neuer Kurs?
			{
				$db = new DB_Admin;
				$db->query("SELECT id FROM kurse WHERE freigeschaltet IN (0,1,3,4) AND titel=".$db->quote(trim($kurs['titel']))." AND anbieter=".intval($loggedInAnbieterId));
				if( $db->next_record() )			// ^^^^^^^^^^^^^^^^^^^^^^^^^^^ otherwise, if there is a deleted and an available offer, we may get the deleted one - which is not editable!
				{
					$andere_kurs_id = $db->fs('id');
					if( $this->isEditable($andere_kurs_id)=='yes' )
					{
						// meine knappe Variante waere gewesen: "Ein Kurse mit dem Titel <i><titel><i> <b>ist bereits vorhanden.</b> Bitte aendern Sie den bestehenden Kurs und fuegen dort ggf. Durchfuehrungen hinzu. <a>bestehenden Kurs bearbeiten</a>"
						$otherUrl = $this->framework->getUrl('edit', array('action'=>'ek', 'id'=>$andere_kurs_id));
						$kurs['error'][] = 
							'
							Fehler: Ein Kurs mit dem Titel <i>'.isohtmlspecialchars($kurs['titel']).'</i> <b>ist bereits vorhanden</b>. 
							Um Verwirrungen zu vermeiden, k&ouml;nnen Sie das folgende tun:<br /><br />
							
							&bull; <b>Sie wollen weitere Termine des Kurses angelegen?</b> <a href="'.$otherUrl.'">Gehen Sie zum bereits vorhandenen Kurs</a> - 
							eventuell ist er nur abgelaufen. Geben Sie beim vorhandenen Kurs in der Durchf&uuml;hrung die neuen Termine ein. 
							Mit Klick  auf &quot;Durchf&uuml;hrung duplizieren&quot; k&ouml;nnen Sie mehrere Termine, auch an unterschiedlichen Orten, an den 
							Kurs anh&auml;ngen. Falls erforderlich, k&ouml;nnen Sie auch die Kursbeschreibung aktualisieren.<br /><br />
							
							&bull; <b>Soll der neue Kurs eine v&ouml;llig andere Kursbeschreibung erhalten als der schon vorhandene Kurs?</b>
							W&auml;hlen Sie f&uuml;r diesen Kurs einen Titel, der ihn vom vorhandenen Kurs unterscheidet. Eventuell reicht es ja, 
							einfach nur eine Zahl anh&auml;ngen, z.B. Englisch 1 und Englisch 2.<br /><br />
							 
							&bull; <b>Soll der neue Kurs nur eine kleine &Auml;nderung im Titel erhalten, inhaltlich aber gleich bleiben?</b>
							Senden Sie einfach den gew&uuml;nschten neuen Titel per E-Mail an den Tr&auml;ger dieser Datenbank. Die 
							Datenredaktion kann den Titel f&uuml;r Sie &auml;ndern; dann m&uuml;ssen Sie nicht alle Angaben zum Kurs komplett neu eingeben.<br />
							';
					}
				}
			}
			
		}
		
		if( $kurs['beschreibung'] == '' )
		{
			$kurs['error'][] = 'Fehler: Keine Kursbeschreibung angegeben.';
		}
		
		if( sizeof((array) $kurs['durchf']) < 1 )
		{
			$kurs['error'][] = 'Fehler: Der Kurs muss mindestens eine Durchf&uuml;hrung haben.';
		}
		
		$max_df = $this->framework->iniRead('useredit.durchf.max', 25);
		if( sizeof((array) $kurs['durchf']) > $max_df )
		{
			$kurs['error'][] =	'
								Fehler: <b>Die Anzahl &uuml;berschaubarer Durchf&uuml;hrungen ist &uuml;berschritten</b> -  
								erlaubt sind maximal '.$max_df.' Durchf&uuml;hrungen pro Kurs; der aktuelle Kurs hat jedoch '.sizeof((array) $kurs['durchf']).' Durchf&uuml;hrungen.<br />
								Bei h&auml;ufigeren Beginnterminen w&auml;hlen Sie bitte eine Terminoption wie beispielsweise <i>Beginnt laufend</i> oder <i>Beginnt w&ouml;chentlich</i>
								und denken Sie auch daran, abgelaufene Durchf&uuml;hrungen zu l&ouml;schen.<br />
								';
		}
		
		// new 20:55 01.06.2014: es ist nur eine URL erlaubt - entweder in der Kursbeschreibung oder in den Durchfuehrungsbemerkungen
		$stopwords = $this->tools->loadStopwords('useredit.stopwords');
		$maxlen_preishinweise = 160;
		$maxlen_bemerkungen = 250;
		$has_durchf_urls = false;
		foreach( $kurs['durchf'] as $durchf )
		{
			$durchf_urls = $this->tools->getUrls($durchf['preishinweise']);
			if( sizeof((array) $durchf_urls) ) {
				$kurs['error'][] = 'Fehler: Im Feld <i>Preishinweise</i> sind keine URLs erlaubt.';
			}
			if( strlen($durchf['preishinweise']) > $maxlen_preishinweise ) {
				$kurs['error'][] = 'Fehler: Im Feld <i>Preishinweise</i> sind max. '.$maxlen_preishinweise.' Zeichen erlaubt. Eingegebene Zeichen: '.strlen($durchf['preishinweise']);
			}
			
			$check_maxlen_bemerkungen = 0;
			$durchf_urls = $this->tools->getUrls($durchf['bemerkungen']);
			if( sizeof((array) $durchf_urls) ) {
				$has_durchf_urls = true;
				if( sizeof((array) $durchf_urls) > 1 ) {
					$kurs['error'][] = 'Fehler: Pro Feld <i>Bemerkungen</i> ist nur eine URL erlaubt. Gefundene URLs: '.implode(', ', $durchf_urls);
				}
				else if( $this->tools->isAnbieterUrl($durchf_urls) ) {
					$kurs['error'][] = 'Fehler: Die URL im Feld <i>Bemerkungen</i> ist die Standard-URL des Anbieters; bitte verwenden Sie kurspezifische URLs.';
				}
				else {
					$check_maxlen_bemerkungen = $maxlen_bemerkungen + strlen($durchf_urls[0]) + 6 /*do not count the URL and special characters needed for the URL - but the URL text _is_ counted*/;
				}
			}
			else {
				$check_maxlen_bemerkungen = $maxlen_bemerkungen;
			}
			
			if( $check_maxlen_bemerkungen ) { /*bei unklaren URL-Verhaeltnissen wird die Laenge nicht geprueft, da sowieso ein Fehler ausgegeben wird - mit dem Hinweis nur max. 1 URL zu verwenden*/
				if( strlen($durchf['bemerkungen']) > $check_maxlen_bemerkungen ) {
					$kurs['error'][] = 'Fehler: Im Feld <i>Bemerkungen</i> sind max. '.$maxlen_bemerkungen.' Zeichen erlaubt; URLs werden dabei nicht mitgez&auml;hlt. Eingegebene Zeichen: '.strlen($durchf['bemerkungen']);
				}
			}
			
			if( ($badWord=$this->tools->containsStopword($durchf['bemerkungen'], $stopwords))!==false ) {
				$kurs['error'][] = 'Fehler: Im Feld <i>Bemerkungen</i> sind Angaben zu <i>'.$badWord.'</i> nicht erlaubt.';
			}
		}
		
		$kurs_urls = $this->tools->getUrls($kurs['beschreibung']);
		if( sizeof((array) $kurs_urls) ) {
			if( $has_durchf_urls ) {
				$kurs['error'][] = 'Fehler: URLs k&ouml;nnen nicht gleichzeitig im Feld <i>Kursbeschreibung</i> und im Feld <i>Bemerkungen</i> angegeben werden.';
			}
			if( sizeof((array) $kurs_urls) > 1 ) {
				$kurs['error'][] = 'Fehler: Im Feld <i>Kursbeschreibung</i> ist nur eine URL erlaubt. Gefundene URLs: '.implode(', ', $kurs_urls);
			}			
			else if( $this->tools->isAnbieterUrl($kurs_urls) ) {
				$kurs['error'][] = 'Fehler: Die URL im Feld <i>Kursbeschreibung</i> ist die Standard-URL des Anbieters; bitte verwenden Sie kurspezifische URLs.';
			}
		}
		
		
		// promotion laden
		if( $_POST['promote_mode'] == 'times' || $_POST['promote_mode'] == 'date' )
		{
			if( $_POST['promote_mode'] == 'times' )
			{
				$kurs['promote_mode'] = $_POST['promote_mode'];
				$kurs['promote_param'] = intval($_POST['promote_param_times']);
				$kurs['promote_active'] = $kurs['promote_param']>0? 1 : 0;
			}
			else if( $_POST['promote_mode'] == 'date' )
			{
				$kurs['promote_mode'] = $_POST['promote_mode'];
				$kurs['promote_param'] = substr($this->checkDate($_POST['promote_param_date'], $kurs['error']), 0, 10);
				$kurs['promote_active'] = (sizeof((array) $kurs['error'])==0 && $kurs['promote_param']>strftime("%Y-%m-%d"))? 1 : 0;
			}
			
					// TODEL: Promote AGB
			if( intval($_POST['promote_agb_read']) != 1 )
			{
				$kurs['error'][] = "Fehler: Um einen Kurs zu bewerben, m&uuml;ssen Sie zun&auml;chst die AGB best&auml;tigen.";
			}
					// /TODEL: Promote AGB
		}
		else
		{
			$kurs['promote_mode'] = '';
			$kurs['promote_param'] = '';
			$kurs['promote_active'] = 0;
		}
		
		return $kurs;
	}

	protected function ist_bagatelle($oldData, $newData)
	{
		/*
		echo '<table><tr><td width="50%" valign="top"><pre>';
			print_r($oldData);
		echo '</pre></td><td width="50%" valign="top"><pre>';
			print_r($newData);
		echo '</pre></td></tr></table>';
		*/
		
		if( !$oldData['rights_editTitel'] ) 	{ $newData['titel'] = $oldData['titel']; }
		if( !$oldData['rights_editAbschluss'] )	{ $newData['abschluss'] = $oldData['abschluss']; $newData['msgtooperator'] = $oldData['msgtooperator']; }
		
		$allowed_kfields = array('error', 'info', 'durchf', 'msgtooperator');
		$allowed_dfields = array('id', 'nr', 'stunden', 'teilnehmer', 'preis', 'preishinweise', 'sonderpreis', 'sonderpreistage', 'beginn', 'ende',
								 'beginnoptionen', 'zeit_von', 'zeit_bis', 'kurstage', 'tagescode', 'stadtteil');

		// nach �nderungen im Kurs suchen
		reset($newData);
		foreach($newData as $name => $newValue) {
			if( $newValue != $oldData[$name] ) {
				if( !in_array($name, $allowed_kfields) ) {
					$this->keine_bagatelle_why = "$name";
					return false;
				}
			}
		}
		
		$diff_df = "";
		// nach Aenderungen in den Durchfuehrungen suchen (Loeschen von Df sind Bagatellen)
		for( $n = 0; $n < sizeof((array) $newData['durchf']); $n++ ) 
		{	
			// suche nach einer alten Df, die dieselben Daten wie die Neue hat bzw. nur Aenderungen, die erlaubt sind
			$template_found = false;
			
			for( $o = 0; $o < sizeof((array) $oldData['durchf']); $o++ ) 
			{
				$o_is_fine = true;
				reset($newData['durchf'][$n]);
				foreach($newData['durchf'][$n] as $name => $newValue)
				{
					if( $newValue != $oldData['durchf'][$o][$name] 
					 && !in_array($name, $allowed_dfields) )
					{
						$o_is_fine = false;
						$this->keine_bagatelle_why = "$name";
						break;
					}
				}
				
				if( $o_is_fine )
				{
					$template_found = true;
					break;
				}
			}
			
			if( !$template_found ) 
			{
				return false; // neue Durchfuehrung oder Durchfuehrungsaenderungen, die ueber eine Bagatelle hinausgehen
			}
			
			// weiter mit der naechsten, neuen/geaenderten Durchfuehrung
		}
		
		return true; // alle Aenderungen sind Bagatell-Aenderungen
	}
	
	function saveKursToDb(&$newData)
	{
		// kurs in datenbank speichern
		//
		// $kurs ist ein array wie unter loadKursFromPOST() beschrieben, der Aufruf dieser Funktion kann dabei das 
		// Feld $kurs['error'] erweitern; alle anderen Felder werden nur gelesen

		$db			= new DB_Admin;
		$user		= $this->getAdminAnbieterUserId20();
		$today		= strftime("%Y-%m-%d %H:%M:%S");
		$kursId		= $newData['id'];
		$oldData	= $this->loadKursFromDb($kursId);
		if( sizeof((array) $oldData['error']) )
		{
			$newData['error'] = $oldData['error'];
			return;
		}

		$actions	= '';
		$protocol	= false;
		
		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->addDataFromTable('kurse', $kursId, 'preparediff');
		
		// BAGATELLE?
		if( $this->ist_bagatelle($oldData, $newData) )
		{
			// die Aenderung IST eine BAGATELLE
			$logwriter->addData('ist_bagatelle', 1);
			if( $oldData['user_modified'] == $this->getAdminAnbieterUserId20() ) 
			{
				// wenn die letzte Aenderung eine Onlinepflege war, die potentiell noch nicht von der Redaktion eingesehen wurde, 
				// ist auch die neue Aenderung keine Bagatelle
			}
			else
			{
				$user =  $this->getAdminAnbieterUserId19();
			}
		}
		else
		{
			// die Aenderung ist KEINE BAGATELLE - Nicht-Bagatellaenderung erlaubt?
			if( $this->canEditBagatelleOnly() )
			{
				$newData['error'][] = 'Fehler: Der angemeldete Benutzer hat <b>nicht das Recht</b> diese &Auml;nderungen am Feld <i>'.isohtmlspecialchars($this->keine_bagatelle_why).'</i> vorzunehmen.<br />
									   Es d&uuml;rfen nur Datum und Preis und andere Felder in gewissen Grenzen ge&auml;ndert werden. 
									   <a href="'.$this->framework->getHelpUrl($this->framework->iniRead('useredit.help.norights', '20')).'" target="_blank" rel="noopener noreferrer">Weitere Informationen hierzu ...</a><br />';
				return;
			}
		}

		$loggedInAnbieterId = isset($_SESSION['loggedInAnbieterId']) ? $_SESSION['loggedInAnbieterId'] : null;
		
		// CREATE A NEW RECORD?
		if( $kursId == 0 )
		{
			$anbieter = intval($_SESSION['loggedInAnbieterId']);
			$db->query("SELECT user_grp, user_access FROM anbieter WHERE id=$anbieter AND freigeschaltet = 1");
			$db->next_record();
			$user_grp = intval($db->f('user_grp'));
			$user_access =  intval($db->f('user_access'));
			$db->query("INSERT INTO kurse  (user_created, date_created, user_modified, date_modified, user_grp,  user_access,  anbieter,  freigeschaltet) 
									VALUES ($user, 		  '$today',     $user,         '$today',      $user_grp, $user_access, $anbieter, 0)
									;");
			$kursId = $db->insert_id();
			$newData['id'] = $kursId;
		}
		
		// DURCHFUEHRUNGS-Aenderungen ablegen
		for( $d = 0; $d < sizeof((array) $newData['durchf']); $d++ )
		{
			// neue daten holen
			$newDurchf = $newData['durchf'][$d];
			
			// passende alten daten suchen, wenn es keine gibt, ist dies eine neue Durchfuehrung!
			$isNew = false;
			$oldDurchf = array();
			if( $newDurchf['id'] )
			{
				// existierende durchfuehrung
				for( $d2 = 0; $d2 < sizeof((array) $oldData['durchf']); $d2++ )
				{
					if( $oldData['durchf'][$d2]['id'] == $newDurchf['id'] )
					{
						$oldDurchf = $oldData['durchf'][$d2];
						$oldData['durchf'][$d2]['id'] = 0; // mark as used
						break;
					}
				}
				if( sizeof((array) $oldDurchf) == 0 )
					{ $newData['error'][] = "Fataler Fehler: Die Durchf&uuml;hrung ID ".$newDurchf['id']." kann nicht gefunden werden!"; return; }
			}
			else
			{
				// neue Durchfuehrung!				
				$db->query("SELECT user_grp, user_access FROM kurse WHERE id=$kursId;");
				$db->next_record();
				$user_grp = intval($db->f('user_grp'));
				$user_access =  intval($db->f('user_access'));

				$db->query("INSERT INTO durchfuehrung (user_created, date_created, user_modified, date_modified, user_grp, user_access) VALUES ($user, '$today', $user, '$today', $user_grp, $user_access)");
				$newDurchf['id'] = $db->insert_id();
				$oldDurchf['id'] = $newDurchf['id']; 		// damit diese unten nicht aktualisiert werden muss ...
				$newData['durchf'][$d]['id'] = $newDurchf['id'];	// damit die neue ID beim caller ankommt
				
				$db->query("SELECT MAX(structure_pos) AS temp FROM kurse_durchfuehrung WHERE primary_id=$kursId");
				$db->next_record();
				
				$db->query("INSERT INTO kurse_durchfuehrung (primary_id, secondary_id, structure_pos) VALUES ($kursId, ".$newDurchf['id'].", ".($db->f('temp')+1).")");
				
				$isNew = true;
				
				$actions .= ' DURCHF-INSERT ';
				
				$triggerParam = array( 'action' => 'afterinsert', 'primary_id' => $kursId, 'origin' => 'Onlinepflege' );
				trigger_durchfuehrung($triggerParam);
			}
			
			// Aenderungen ueberpruefen
			$sqlExpr = '';
			reset( $newDurchf );
			foreach($newDurchf as $name => $value)
			{
				if( strval($value) != strval($oldDurchf[$name]) || !isset($oldDurchf[$name]) )
				{
					// sql
					$sqlExpr .= ", $name='" . addslashes($value) . "'";

					// protocol
					if( !$isNew )
					{
						$oldVal = $oldDurchf[$name];
						$newVal = $newDurchf[$name];
		
						$protocol = true;
					}
				}
			}
			
			// aenderungen schreiben
			if( $sqlExpr != '' )
			{
				$sqlExpr = "UPDATE durchfuehrung SET user_modified={$user}, date_modified='{$today}'{$sqlExpr} WHERE id=" . $newDurchf['id'];
				$db->query($sqlExpr);
				
				// protocol
				if( $isNew )
				{
					$protocol = true;
				}
				
				$actions .=  ' DURCHF-UPDATE ' ;
				
				if( !$isNew ) { // only protocoll updates not simply new DF touched again
				    $triggerParam = array( 'action' => 'afterupdate', 'primary_id' => $kursId, 'origin' => 'Onlinepflege' );
				    trigger_durchfuehrung($triggerParam);
				}
			}
		}
		
		// UEBERSCHUESSIGE durchfuehrungen loeschen
		$delCnt = 0;
		for( $d2 = 0; $d2 < sizeof((array) $oldData['durchf']); $d2++ )
		{
			if( $oldData['durchf'][$d2]['id'] )
			{
				$toDel = $oldData['durchf'][$d2]['id'];
				$db->query("DELETE FROM kurse_durchfuehrung WHERE primary_id=$kursId AND secondary_id=$toDel");
				$db->query("DELETE FROM durchfuehrung WHERE id=$toDel");
				$delCnt ++;
				
				$actions .=  ' DURCHF-DELETE ' ;
				
				$triggerParam = array( 'action' => 'afterdelete', 'primary_id' => $kursId, 'origin' => 'Onlinepflege' );
				trigger_durchfuehrung($triggerParam);
			}
		}
		
		if( $delCnt )
		{
			// protocol
			$protocol = true;
		}
		
		// PROMOTION speichern
		if( $this->canPromote() )
		{
			if( $oldData['promote_active'] != $newData['promote_active']
			 || $oldData['promote_mode']   != $newData['promote_mode']
			 || $oldData['promote_param']  != $newData['promote_param'] )
			{
				// insert record, if it does not exist
				global $wisyPortalId;
				$db->query("SELECT * FROM anbieter_promote WHERE kurs_id=$kursId AND portal_id=$wisyPortalId;");
				if( !$db->next_record() )
				{
					$db->query("INSERT INTO anbieter_promote (kurs_id, portal_id, anbieter_id) VALUES ($kursId, $wisyPortalId, ".$_SESSION['loggedInAnbieterId'].");");
				}
				
				// update data
				if( $newData['promote_mode'] == '' )
				{
					$db->query("DELETE FROM anbieter_promote WHERE kurs_id=$kursId AND portal_id=$wisyPortalId;");
				}
				else
				{
					$db->query("UPDATE anbieter_promote SET promote_active=".intval($newData['promote_active']).", promote_mode='".addslashes($newData['promote_mode'])."', promote_param='".addslashes($newData['promote_param'])."' WHERE kurs_id=$kursId AND portal_id=$wisyPortalId;");
				}
				
				$actions .=  ' PROMOTION-UPDATE '; // ACHTUNG: dies erzeugt ein Update des KURS-Datensatzes, das auch notwendig ist,  damit der neue active-status uebernommen wird (der cache wird geleert)
			}
		}

		// KURS-�nderungen ablegen
		if( !$oldData['rights_editTitel'] ) 	{ $newData['titel'] = $oldData['titel']; }
		if( !$oldData['rights_editAbschluss'] )	{ $newData['abschluss'] = $oldData['abschluss']; $newData['msgtooperator'] = $oldData['msgtooperator']; }
		
		if( $actions != ''
		 || $oldData['titel'] 			!= $newData['titel']
		 || $oldData['beschreibung'] 	!= $newData['beschreibung']
		 || $oldData['bu_nummer']      	!= $newData['bu_nummer'] 
		 || $oldData['fu_knr']      	!= $newData['fu_knr']
		 || $oldData['azwv_knr']    	!= $newData['azwv_knr'] 
		 || $oldData['abschluss'] 		!= $newData['abschluss']
		 || $oldData['foerderung'] 		!= $newData['foerderung']
		 || $oldData['msgtooperator'] 	!= $newData['msgtooperator']
		 )
		{
			// protocol
			if( $oldData['beschreibung'] != $newData['beschreibung'] )
			{
				$protocol = true;
			}

			$fields = array('titel', 'bu_nummer', 'fu_knr', 'azwv_knrd', 'foerderung', 'abschluss', 'msgtooperator');
			foreach($fields as $key => $value)
			{
				if( $oldData[$value] != $newData[$value] ) 
					{ $protocol = true; }
			}
			
			// update record
			$sql = "UPDATE kurse SET titel='".addslashes($newData['titel'])."',
									 beschreibung='".addslashes($newData['beschreibung'])."', 
									 msgtooperator='".addslashes($newData['msgtooperator'])."', 
									 bu_nummer='".addslashes($newData['bu_nummer'])."',
									 fu_knr='".addslashes($newData['fu_knr'])."',
									 azwv_knr='".addslashes($newData['azwv_knr'])."', ";
			
			if($this->framework->iniRead('onlinepflege.invorbereitung', "") == 1)
			    $sql .= "freigeschaltet=0, ";
			
			if( $protocol != '' )
			{
			 	$sql .=			   " user_modified={$user}, ";	// der Benutzer, wird nur geaendert, wenn etwas im Protokoll steht; dies ist notwendig, da durch die Suche nach dem Benutzer (20) die Redaktion die Aenderungen im Protokoll ueberprueft
			}													// das Datum muss dagegen auch geaendert werden, wenn nur bei der Promotion etwas geaendert wurde, da ansonsten die Aenderungen nicht "live" geschaltet werden (bzw. nur stark verzoegert)
			$sql .=				   " date_modified='{$today}' WHERE id=$kursId;";
			$db->query($sql);

			// update stichwoerter
			$this->saveStichwort($kursId, $oldData['foerderung'], $newData['foerderung']);
			$this->saveStichwort($kursId, $oldData['abschluss'], $newData['abschluss']);

			// trigger - dies berechnet u.a. die neue Vollstaendigkeit
			update_kurs_state($kursId, array('from_cms'=>0, 'set_plz_stadtteil'=>2, 'write'=>1));
			
			// log after the record being written
			$logwriter->addDataFromTable('kurse', $kursId, 'creatediff');
			$logwriter->log('kurse', $kursId, $user, 'edit');

			// done.
			$actions .= 'KURS-UPDATE ';
		}
				
		// echo $actions;
	}

	function deleteKurs($kursId)
	{
		// kurs als geloescht markieren ...
		$user = $this->getAdminAnbieterUserId20();
		$today = strftime("%Y-%m-%d %H:%M:%S");
	
		// alten Wert fuer "freigeschaltet" holen
		$db = new DB_Admin;
		$db->query("SELECT freigeschaltet FROM kurse WHERE id=$kursId;");
		if( $db->next_record() )
		{
			$oldFreigeschaltet = $db->f('freigeschaltet');
			
			// neuen Wert fuer "freigeschaltet" setzen
			$db->query("UPDATE kurse SET freigeschaltet=2, user_modified={$user}, date_modified='{$today}' WHERE id=$kursId;");
			
			// ab ins Protokoll
			$logwriter = new LOG_WRITER_CLASS;
			$logwriter->addData('freigeschaltet', array($oldFreigeschaltet, 2));
			$logwriter->log('kurse', $kursId, $user, 'edit');
		}
	}

	function renderEditorToolbar($addKursUrl)
	{
		$ret = '<small>';
			$ret .= '<a href="" onclick="add_chars($(this), \'\\\'\\\'\\\'\', \'\\\'\\\'\\\'\'); return false;" style="font-weight:bold; letter-spacing: 1px;" title="Markieren Sie den zu fettenden Text und klicken Sie dann diese Schaltfl&auml;che" >\'\'\'Fett\'\'\'</a> &nbsp; ';
			$ret .= '<a href="" onclick="add_chars($(this), \'\\\'\\\'\', \'\\\'\\\'\'); return false;" style="font-style:italic; letter-spacing: 1px;" title="Markieren Sie den kursiv darzustellenden Text und klicken Sie dann diese Schaltfl&auml;che" >\'\'Kursiv\'\'</a> &nbsp; ';
			if( $addKursUrl )
			{
				$ret .= '<a href="" onclick="add_chars($(this), \'[[http://verweis.com | Kurs-URL\', \']]\'); return false;" style="letter-spacing: 1px;" title="Markieren Sie den Text, den Sie als Verweis verwenden m&ouml;chten, und klicken Sie dann diese Schaltfl&auml;che">[[Verweis]]</a> &nbsp; ';
			}
		$ret .= '</small><br />';
		return $ret;
		// must be followed by the textarea element! if you change the hierarchy, please also change "parent().parent()" in add_chars() in jquery.wisy.js - see (**)!
	}

	function renderVollstMsg($id, $always)
	{
		$msg = '';
		$db = new DB_Admin;
		$temp = update_kurs_state($id, array('write'=>0));
		if( $temp['vmsg'] != '' )
		{
			$vollst = $this->framework->getVollstaendigkeitMsg($db, $id, 'quality.edit');
			$msg .= '<b>Informationen zu Vollst&auml;ndigkeit:</b> ' . $vollst['msg'];
			$msg .= $temp['vmsg'];
		}
		else if ( $always )
		{
			$vollst = $this->framework->getVollstaendigkeitMsg($db, $id, 'quality.edit');
			$msg .= $vollst['msg'];
		}
		return $msg;
	}
	
	function renderEditKurs($kursId__ /* may be "0" for "new kurs" */)
	{
	    $loggedInAnbieterId = isset($_SESSION['loggedInAnbieterId']) ? $_SESSION['loggedInAnbieterId'] : null;
	    
		// check rights, check, if the kursId belongs to the anbieter logged in
		$db = new DB_Admin;
		$topnotes = array();
		$showForm = true;
		switch( $this->isEditable($kursId__) )
		{
			case 'loginneeded':	$this->renderLoginScreen(); return;
			case 'no':			$topnotes[] = "Der Kurs kann nicht bearbeitet werden."; $topnotes[] = "Der Kurs ist nicht oder nicht mehr vorhanden oder der Kurs ist gesperrt."; $showForm = false; break;
		}
		
		// see what to do ...
		if( intval($_GET['deletekurs']) == 1 )
		{
			// ... "Delete" hit - maybe this is a subsequent call, but not necessarily
			$this->deleteKurs($kursId__);
			header('Location: ' . $this->bwd);
			exit();
		}
		else if( $_POST['subseq'] == 1 && isset($_POST['cancel']) )
		{
			// ... a subsequent call: "Cancel" hit
			header('Location: ' . $this->bwd);
			exit();
		}
		else if( $_POST['subseq'] == 1 )
		{
			// ... a subsequent call: "OK" hit
			$kurs = $this->loadKursFromPOST($kursId__);
			if( sizeof((array) $kurs['error']) == 0 )
			{
				$this->saveKursToDb($kurs);
			} /* no else: saveKursToDb() may also add errors */
			
			if( sizeof((array) $kurs['error']) > 0 )
			{
				$kurs['error'][] = 'Der Kurs wurde aufgrund der angegebenen Fehler <b>nicht gespeichert.</b>';
			}

			if( sizeof((array) $kurs['error']) )
			{
				$topnotes = $kurs['error'];
			}
			else
			{
				$msg = 'Der Kurs <a href="'.$this->framework->getUrl('k', array('id'=>$kurs['id'])).'">'.isohtmlspecialchars($kurs['titel']).'</a> wurde <b>erfolgreich gespeichert.</b>';
				$temp = $this->renderVollstMsg($kurs['id'], false);
				$msg .= ($temp? '<br /><br />' : '') . $temp;
				
				setcookie('editmsg', $msg);
				header('Location: '.$this->framework->getUrl('k', array('id'=>$kurs['id'])));
				exit();
			}
		}
		else
		{
			// the first call
			$kurs = $this->loadKursFromDb($kursId__);
			if( sizeof((array) $kurs['error']) )
			{
				$topnotes = $kurs['error'];
				$showForm = false;
			}
		}
	
		// page out
		$kursId__ = -666; // use $kurs['id'] instead - esp. for ID #0, the ID may change in saveKursToDb()!
		$pageTitle = $kurs['id']==0? 'Neuer Kurs' : 'Kurs bearbeiten';
		echo $this->framework->getPrologue(array('title'=>$pageTitle, 'bodyClass'=>'wisyp_edit'));
		
		if( !isset($_SESSION['statusMsgShown']) || !$_SESSION['statusMsgShown'] )
		{
		    $db->query("SELECT pflege_msg FROM anbieter WHERE id=".$loggedInAnbieterId);
			$db->next_record();
			$msg = trim($db->fs('pflege_msg'));
			if( $msg != '' )
			{
				echo '<div id="pflege_msg"><h1>Nachricht vom Portalbetreiber</h1>';
				$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
				echo $wiki2html->run($msg);
				echo "<p><a href=\"#\" onclick=\"$('#pflege_msg').hide();\">[ Nachricht schlie&szlig;en ]</a></p></div>";
			}
			$_SESSION['statusMsgShown'] = true;
		}
		
		echo "\n\n<h1>$pageTitle</h1>\n";
		
		if( sizeof((array) $topnotes) )
		{
			echo "<p class=\"wisy_topnote\">" .implode('<br />', $topnotes). "</p>";
		}
		else
		{
			$temp = $this->renderVollstMsg($kurs['id'], true);
			echo $temp? "<p>$temp</p>" : '';
		}
		
		$secureaction = "";
		
		if( $this->framework->iniRead('useredit.secure', 1)==1
		    && substr($_SERVER['HTTP_HOST'], -6)!='.local'
		    && $_SERVER['HTTPS'] == "on")
		{
		    $secureaction = 'https://' . $_SERVER['HTTP_HOST'] . '/edit';
		}
		
		$editurl = ($secureaction != "") ? $secureaction : "edit";
		
		echo '<form action="'.$editurl.'" method="post" name="anbieter">' . "\n";
			echo '<input type="hidden" name="action" value="ek" /> ' . "\n";
			echo '<input type="hidden" name="subseq" value="1" /> ' . "\n";
			echo '<input type="hidden" name="id" value="'.$kurs['id'].'" /> ' . "\n";
			echo '<input type="hidden" name="bwd" value="'.isohtmlspecialchars($this->bwd).'" /> ' . "\n";
		
			if( $showForm )
			{
				echo '<br />';
				echo '<table cellspacing="2" cellpadding="0" width="100%">';
				
					// TITEL 
					echo '<tr>';
						echo '<td width="10%" valign="top"><strong>Kurstitel:</strong></td>';
						echo '<td width="90%" valign="top">';
							if( $kurs['rights_editTitel'] )
							{
								$this->controlText('titel', $kurs['titel'], 64, 200, '', '');
								echo '<br />';
							}
							else
							{
								echo '<strong>' .  isohtmlspecialchars($kurs['titel']) . '</strong>';
								$this->controlHidden('titel', $kurs['titel']);
							}

							// Optionen einblenden ...
							$this->moeglicheAbschluesseUndFoerderungen($abschlussOptionen, $foerderungsOptionen);
							
							$styleFoerderung = '';
							if( $kurs['bu_nummer']=='' && $kurs['azwv_knr']=='' && $kurs['foerderung']==0 )
							{
								echo "<span class=\"editFoerderungLink\"> <a href=\"#\" onclick=\"editShowHide($(this), '.editFoerderungDiv', '.editFoerderungLink'); return false;\" title=\"F&ouml;rderungsm&ouml;glichkeiten hinzuf&uuml;gen\"><small>+F&ouml;rderung</small></a></span>";
								$styleFoerderung = ' style="display: none;" ';
							}

							/* only via import:
							 * $styleFernunterricht = '';
							if( $kurs['fu_knr']=='' )
							{
							    $styleFernunterricht = ' style="display: none;" ';
							    echo "<span class=\"editFernunterrichtLink\" ".$styleFernunterricht."> <a href=\"#\" onclick=\"editShowHide($(this), '.editFernunterrichtDiv', '.editFernunterrichtLink'); return false;\" title=\"Kursnummer fuer Fernunterricht hinzuf&uuml;gen\"><small>+Fernunterricht</small></a></span>";
							} */

							$styleBewerben = '';
							if( $this->canPromote() )
							{
								if( $kurs['promote_mode'] == '' )
								{
									echo "<span class=\"editBewerbenLink\"> <a href=\"#\" onclick=\"editShowHide($(this), '.editBewerbenDiv', '.editBewerbenLink'); return false;\" title=\"Diesen Kurs bewerben\"><small>+Kurs bewerben</small></a></span>";
									$styleBewerben = ' style="opacity: 0; height: 1px;" ';
								}
							}

							// originaltitel
							if( $kurs['org_titel'] != '' && $kurs['org_titel'] != $kurs['titel'] )
							{
								echo '<br /><small style="color: #AAA;">Originaltitel: '.isohtmlspecialchars($kurs['org_titel']).'</small>';
							}
							
							echo '<br />&nbsp;';

							// ... Foerderung
							echo "<div class=\"editFoerderungDiv\" $styleFoerderung>";
								echo '<table cellpadding="0" cellspacing="2" border="0">';
									echo '<tr><td>Bildungsurlaubs-Nr.:</td><td><input type="text" name="bu_nummer" value="'.isohtmlspecialchars($kurs['bu_nummer']).'" /> <small>(N&ouml;tig zur Anzeige als Bildungsurlaub/Freistellung)</small></td></tr>';
									echo '<tr><td>AZAV-Zertifikatsnr.:</td><td><input type="text" name="azwv_knr" value="'.isohtmlspecialchars($kurs['azwv_knr']).'" />  <small>(N&ouml;tig zur Suche nach Bildungs- oder Aktivierungsgutschein)</small></td></tr>';
									if( $foerderungsOptionen != '' )
									{
										echo '<tr><td>sonstige F&ouml;rderung:</td><td>'; 
											$this->controlSelect('foerderung', $kurs['foerderung'], '0######'.$foerderungsOptionen);
										echo '</td></tr>';
									}
								echo '</table>';
								echo '&nbsp;';
							echo '</div>';

							/* only via import:
							 * // ... Fernunterricht
							echo "<div class=\"editFernunterrichtDiv\" $styleFernunterricht>";
								echo '<table cellpadding="0" cellspacing="2" border="0">';
									echo '<tr><td>ZFU-Fernunterrichts-Nr.:</td><td><input type="text" name="fu_knr" value="'.isohtmlspecialchars($kurs['fu_knr']).'" /> <small>(Noetig zur Anzeige als Fernunterricht)</small></td></tr>';
								echo '</table>';
								echo '&nbsp;';
							echo '</div>'; */
	
							// ... Kurs bewerben?							
							if( $this->canPromote() )
							{
								
								echo "<div class=\"editBewerbenDiv\" $styleBewerben>";
								
									$radio = $kurs['promote_mode']=='times'? ' checked="checked" ' : '';
									$param = $kurs['promote_mode']=='times'? $kurs['promote_param'] : '1000';
									echo '<input type="radio" name="promote_mode" id="pl1" value="times" '.$radio.' /> <label for="pl1">Kurs kostenpflichtig bewerben mit max.</label> <input type="text" size="6" name="promote_param_times" value="'.$param.'" /> Einblendungen (Bruttopreis '.str_replace('.', ',', $this->billingRenderer->allPrices[0][1]).' &euro; f&uuml;r '.$this->billingRenderer->allPrices[0][0].' Einblendungen) ';
									echo '<a href="' .$this->framework->getHelpUrl(3367). '" class="wisy_help" target="_blank" rel="noopener noreferrer" title="Hilfe">i</a>';
									echo '<br />';
									
									$radio = $kurs['promote_mode']=='date'? ' checked="checked" ' : '';
									$param = $kurs['promote_mode']=='date'? sql_date_to_human($kurs['promote_param'], 'dateopt editable') : strftime("%d.%m.%Y", time()+(7*24*60*60));
									echo '<input type="radio" name="promote_mode" id="pl2" value="date" '.$radio.' /> <label for="pl2">Kurs kostenpflichtig bewerben bis zum</label> <input type="text" size="10" name="promote_param_date" value="'.$param.'" /> (Anzahl Einblendungen variabel)';
									echo '<br />';
									
									$radio = $kurs['promote_mode']==''? ' checked="checked" ' : '';
									echo '<input type="radio" name="promote_mode" id="pl3" value="" '.$radio.' /> <label for="pl3">Kurs nicht bewerben oder laufende Werbung unterbrechen</label>';
									
									if( $kurs['promote_mode'] != '' &&  $kurs['promote_active'] == 0 )
									{
										echo '<br />';
										echo '<br />';
										echo '<span style="color: #C00000">Die Bewerbung ist momentan inaktiv</span> - <a href="'.$this->framework->getUrl('edit', array('action'=>'kt')).'">Details ...</a>';
									}
	
											// TODEL: Promote AGB
									$agb_reading_required = $this->framework->iniRead('useredit.promote.agb', 3370);
									if( $agb_reading_required )
									{
										global $wisyPortalId;
										$db->query("SELECT kurs_id FROM anbieter_promote WHERE anbieter_id=".$_SESSION['loggedInAnbieterId']. " AND portal_id=$wisyPortalId;");
										if( $db->next_record() )
										{
											$agb_reading_required = 0; // es existiert bereits mind. ein beworbener Kurse; eine erneute bestaetigung ist daher nicht erforderlich
										}
									}
									
									if( $agb_reading_required )
									{
										echo '<br />';
										echo '<br />';
										echo '<input type="checkbox" name="promote_agb_read" value="1" /> ';
										echo 'Ich habe die <a href="'.$this->framework->getHelpUrl($agb_reading_required).'" target="_blank" rel="noopener noreferrer">AGB zum Bewerben von Kursen</a> gelesen und akzeptiere diese';
									}
									else
									{
										echo '<input type="hidden" name="promote_agb_read" value="1" />';
									}
											// /TODEL: Promote AGB

									echo '<br />&nbsp;';
								echo '</div>';
							} // /canPromote
							
						echo '</td>';
					echo '</tr>';

					// STICHWORTVORSCHLAEGE
					if( $kurs['rights_editAbschluss'] )
					{
						echo '<tr>';
							echo '<td width="10%" valign="top" nowrap="nowrap"><strong>Stichwortvorschl&auml;ge:</strong>&nbsp;&nbsp;</td>';
							echo '<td>';
								if( $abschlussOptionen!='' )
								{
									echo '<label title="Fehlt ein Abschluss? Dann bitte unter &quot;Stichwortvorschl&auml;ge&quot; eintragen.">Abschluss: '; 
										$this->controlSelect('abschluss', $kurs['abschluss'], '0######'.$abschlussOptionen);
									echo '</label><br />';
									echo '<label title="weitere Stichwort- oder Abschlussvorschl&auml;ge">weitere Vorschl&auml;ge: ';
								}
								else
								{
									echo '<label title="Stichwort- oder Abschlussvorschl&auml;ge">';
								}
								$this->controlText('msgtooperator', $kurs['msgtooperator'], 40, 200, '', '');
								echo '</label> &nbsp; <a href="' .$this->framework->getHelpUrl(4100). '" class="wisy_help" target="_blank" rel="noopener noreferrer" title="Hilfe">i</a> <br />&nbsp;';
							echo '</td>';
						echo '</tr>';
					}
					
					// KURSBESCHREIBUNG
					echo '<tr>';
						echo '<td valign="top" nowrap="nowrap"><strong>Kursbeschreibung:</strong>&nbsp;</td>';
						echo '<td>';
							echo '<div style="border-top: 2px solid black; border-left: 2px solid black; padding-top: 6px;  padding-left: 6px; margin-bottom: 1.4em; width: 99%;">';
								echo $this->renderEditorToolbar(false);
								echo '<textarea name="beschreibung" rows="14" style="border: 0; width: 99%; border-top: 1px solid #ddd;">' . isohtmlspecialchars($kurs['beschreibung']) . '</textarea>';
							echo '</div>';
						echo '</td>';
					echo '</tr>';
					
					// DURCHF�HRUNGEN
					for( $d = 0; $d < sizeof((array) $kurs['durchf']); $d++ )
					{
						$durchf = $kurs['durchf'][$d];
						echo '<tr class="editDurchfRow">';
							echo '<td valign="top"><strong>Durchf&uuml;hrung:</strong><br />';
								echo '<small>';
									echo '<input type="hidden" name="durchfid[]" value="'.$durchf['id'].'" class="hiddenId" />';
									echo '<a href="#" onclick="editDurchfKopieren($(this)); return false;" title="Eine Kopie dieser Durchf&uuml;hrung zur weiteren Bearbeitung anlegen">+kopieren</a> ';
									echo '<a href="#" onclick="editDurchfLoeschen($(this)); return false;" title="Diese Durchf&uuml;hrung l&ouml;schen">-l&ouml;schen</a> ';
								echo '</small>';
							echo '</td>';
							echo '<td>';
								echo '<div style="border-top: 2px solid black; border-left: 2px solid black; padding: .5em; margin-bottom: 1.4em; width: 99%;">';
									
									echo '<table cellspacing="6" cellpadding="0">';
									
										// DURCHFUEHRUNGS-NR
										echo '<tr>';
											echo '<td valign="top" nowrap="nowrap">Durchf&uuml;hrungs-Nr.:&nbsp;&nbsp;&nbsp;</td>';
											echo '<td>';
												$this->controlText('nr[]', $durchf['nr'], 24, 64, 'Geben Sie hier eine f&uuml;r Sie eindeutige numerische oder alphanumerische Kennung dieser Durchf&uuml;hrung ein', 'k. A.');
											echo '</td>';
										echo '</tr>';
										
										// TERMIN
										echo '<tr>';
											echo '<td valign="top">Termin:</td>';
											echo '<td>';
												$temp = sql_date_to_human($durchf['beginn'], 'dateopt editable');
												$this->controlText('beginn[]', $temp, 10, 10, 'Geben Sie hier - soweit bekannt - das Beginndatum dieser Durchf&uuml;hrungein', 'tt.mm.jjjj');
												echo ' bis ';
												$temp = sql_date_to_human($durchf['ende'], 'dateopt editable');
												$this->controlText('ende[]', $temp, 10, 10, 'Geben Sie hier - soweit bekannt - das Datum des letzten Termins dieser Durch&uuml;hrung ein', 'tt.mm.jjjj');
												echo ' ';
												
												echo ' &nbsp;&nbsp; ';
												echo '<label title="W&auml;hlen Sie hier - sofern bekannt und eindeutig - die Wochentage aus, an denen die Durchf&uuml;hrung stattfindet">';
													global $codes_kurstage;
													$bits = explode('###', $codes_kurstage);
													for( $i = 0; $i < sizeof($bits); $i+=2 ) 
													{
														// normally, we would use the normal <input type="checkbox" /> - however this does
														// not work with our array'ed durchfuehrungen as a checkbox value is not appended to an array it it is not checked ...
														echo '<span>'; // needed to get the both items on one level
															$value = $durchf['kurstage']&intval($bits[$i])? 1 : 0;
															echo "<input type=\"hidden\" name=\"kurstage$i"."[]\" value=\"$value\" />";
															echo '<span onclick="editWeekdays($(this));" class="'.($value?'wisy_editweekdayssel':'wisy_editweekdaysnorm').'">' . trim(str_replace('.', '', $bits[$i+1])) . '</span>';
														echo '</span>';
														
													}
												echo '</label>';

												echo ' &nbsp;&nbsp; ';
												$this->controlText('zeit_von[]', $durchf['zeit_von'], 5, 5, 'Geben Sie hier - soweit bekannt und eindeutig - die Uhrzeit ein, wann die Durchf&uuml;hrung beginnt', 'hh:mm');
												echo '-';
												$this->controlText('zeit_bis[]', $durchf['zeit_bis'], 5, 5, 'Geben Sie hier - soweit bekannt und eindeutig - die Uhrzeit ein, wann die Durchf&uuml;hrung endet', 'hh:mm');
												echo ' Uhr';

												$do_expand = false;
												if( $durchf['beginnoptionen'] ) { $do_expand = true; }
												if( berechne_dauer($durchf['beginn'], $durchf['ende'])==0 && $durchf['dauer']!=0 ) { $do_expand = true; }
												if( berechne_tagescode($durchf['zeit_von'], $durchf['zeit_bis'], $durchf['kurstage'])==0 && $durchf['tagescode']!=0 ) { $do_expand = true; }
												
												$titleBeginnoptionen = 'Hiermit k&ouml;nnen Sie f&uuml;r diese Durchf&uuml;hrung eine Terminoption festlegen, etwa wenn die Durchf&uuml;hrung regelm&auml;&szlig;ig stattfindet';
												$styleBeginnoptionen = '';
												if( !$do_expand )
												{
													echo "<span class=\"editBeginnoptionenLink\"> <a href=\"#\" onclick=\"editShowHide($(this), '.editBeginnoptionenDiv', '.editBeginnoptionenLink'); return false;\" title=\"$titleBeginnoptionen\"><small>+Optionen</small></a></span>";
													$styleBeginnoptionen = ' style="display:none;" ';
												}
												
												echo "<div class=\"editBeginnoptionenDiv\" $styleBeginnoptionen>";
													echo '<label title="'.$titleBeginnoptionen.'">';
														echo "Terminoptionen: ";
														$this->controlSelect('beginnoptionen[]', $durchf['beginnoptionen'], $GLOBALS['codes_beginnoptionen']);
														
														echo "<br />Dauer: ";
														$this->controlSelect('dauer[]', $durchf['dauer'], $GLOBALS['codes_dauer']);			
														echo '<small> (wird, wenn m&ouml;glich, aus Beginn-/Endedatum automatisch berechnet)</small>';
														
														echo "<br />Tagescode: ";
														$this->controlSelect('tagescode[]', $durchf['tagescode'], $GLOBALS['codes_tagescode']);		
														echo '<small>  (wird, wenn m&ouml;glich, aus Wochentag/Uhrzeit automatisch berechnet)</small>';
													echo '</label>';
											echo '</div>';
											echo '</td>';
										echo '</tr>';
										echo '<tr>';
											echo '<td valign="top">Stunden:</td>';
											echo '<td>';
												$temp = $durchf['stunden'] == 0? '' : $durchf['stunden'];
												$this->controlText('stunden[]', $temp, 4, 6, 'Geben Sie hier - soweit bekannt - die Gesamtzahl der Unterrichtsstunden ein', 'k. A.');
												echo ' Unterrichtsstunden mit max. ';
												$temp = $durchf['teilnehmer'] == 0? '' : $durchf['teilnehmer'];
												$this->controlText('teilnehmer[]', $temp, 3, 6, 'Geben Sie hier - soweit bekannt - die maximale Anzahl von Teilnehmenden ein, die insgesamt diese Durchf&uuml;hrung belegen werden', 'k. A.');
												echo ' Teilnehmende';
											echo '</td>';
										echo '</tr>';
										echo '<tr>';
											echo '<td valign="top">Gesamtpreis inkl. MwSt:</td>';
											echo '<td>';
											
												$temp = $durchf['preis'] == -1? '' : $durchf['preis'];
												$this->controlText('preis[]', $temp, 5, 6, 'Geben Sie hier - soweit bekannt - den Gesamtpreis inkl. MwSt dieser Durchf&uuml;hrung in Euro ohne Nachkommastellen ein; geben Sie eine Null f&uuml;r &quot;kostenlos&quot; ein', 'k. A.');
												echo '&nbsp;EUR';
												
												if( $durchf['sonderpreistage'] == 0	) 
													{ $durchf['sonderpreistage'] = ''; }
												if( $durchf['sonderpreis'] == -1 || $durchf['sonderpreistage'] == ''	) 
													{ $durchf['sonderpreis'] = ''; }
												
												$styleSonderpreis = '';
												if( !$durchf['sonderpreis'] )
												{
													echo "<span class=\"editSonderpreisLink\"> <a href=\"#\" onclick=\"editShowHide($(this), '.editSonderpreisDiv', '.editSonderpreisLink'); return false;\" title=\"Sonderpreis f&uuml;r diese Durchf&uuml;hrung hinzuf&uuml;gen\"><small>+Sonderpreis</small></a></span>";
													$styleSonderpreis = ' style="display:none;" ';
												}
												
												$stylePreishinweise = '';
												if( !$durchf['preishinweise'] )
												{
													echo "<span class=\"editPreishinweiseLink\"> <a href=\"#\" onclick=\"editShowHide($(this), '.editPreishinweiseDiv', '.editPreishinweiseLink'); return false;\" title=\"Preishinweise hinzuf&uuml;gen\"><small>+Preishinweise</small></a></span>";
													$stylePreishinweise = ' style="display:none;" ';
												}
												
												echo "<div class=\"editSonderpreisDiv\" $styleSonderpreis>";
													echo 'ab ';
													$this->controlText('sonderpreistage[]', $durchf['sonderpreistage'], 3, 4, 'Anzahl der Tage vor Beginntermin, wo der nachstehende Sonderpreis gelten soll', 'k. A.');
													echo ' Tagen vor Beginn dieser Durchf&uuml;hrung gilt ein erm&auml;&szlig;gter Sonderpreis von&nbsp;';
													$this->controlText('sonderpreis[]', $durchf['sonderpreis'], 5, 5, 'Sonderpreis dieser Durchf&uuml;hrung in Euro ohne Nachkommastellen', 'k. A.');
													echo '&nbsp;EUR';
												echo "</div>";
												
												echo "<div class=\"editPreishinweiseDiv\" $stylePreishinweise>";
													echo 'Preishinweise: ';
													$this->controlText('preishinweise[]', $durchf['preishinweise'], 50, 200, 'Geben Sie hier eventuelle sonstige Anmerkungen zum Preis ein');
												echo "</div>";
								
											echo '</td>';
										echo '</tr>';
										echo '<tr>';
											echo '<td valign="top">Veranstaltungsort:</td>';
											echo '<td>';
												$this->controlText('strasse[]', $durchf['strasse'], 25, 100, 'Geben Sie hier - soweit bekannt und eindeutig - die Strasse und die Hausnummer des Veranstaltungsortes ein', 'Strasse und Hausnr.');
									
												echo ' &nbsp; ';
								
												$this->controlText('plz[]', $durchf['plz'], 5, 16, 'Geben Sie hier - soweit bekannt und eindeutig - die Postleitzahl des Veranstaltungsortes ein', 'PLZ');
												echo ' ';
												$this->controlText('ort[]', $durchf['ort'], 12, 60, 'Geben Sie hier - soweit bekannt und eindeutig - den Ort bzw. die Stadt, in der die Veranstaltung stattfindet ein', 'Ort');

												$this->controlHidden('stadtteil[]', $durchf['stadtteil']);
												$this->controlHidden('stadtteil_for[]', $durchf['strasse'].','.$durchf['plz'].','.$durchf['ort']);


											echo '</td>';
										echo '</tr>';
										echo '<tr>';
											echo '<td valign="top">Kurs-URL/Bemerkungen:</td>';
											echo '<td>';
											
												$style = '';
												if( !$durchf['bemerkungen'] )
												{
													echo "<span class=\"editAdvOrtLink\"> <a href=\"#\" onclick=\"editShowHide($(this), '.editAdvOrtDiv', '.editAdvOrtLink'); return false;\" title=\"URL und/oder Bemerkungen zur Durchf&uuml;hrung hinzuf&uuml;gen\"><small>+Hinzuf&uuml;gen</small></a></span>";
													$style = ' style="display:none;" ';
												}

												echo "<div class=\"editAdvOrtDiv\" $style>";
													echo $this->renderEditorToolbar(true);
													echo "<textarea name=\"bemerkungen[]\" title=\"Geben Sie hier die Kurs-URL oder sonstige Hinweise ein zur Durchf&uuml;hrung ein\" cols=\"40\" rows=\"3\" style=\"width: 90%; border: 1px solid #ddd;\" />" . isohtmlentities(strval($durchf['bemerkungen'])) . '</textarea>';
												echo '<div>';
											
											echo '</td>';
										echo '</tr>';
									echo '</table>';
								echo '</div>';
							echo '</td>';
						echo '</tr>';
					}

				echo '</table>';

			}
		
			echo '<p>' . "\n";
				if( $showForm )
				{
					echo '<input type="submit" value="OK - Kurs speichern" title="Alle &Auml;nderungen &uuml;bernehmen und Kurs speichern" style="font-weight: bold;" /> ' . "\n";
				}
				
				echo '<input type="submit" name="cancel" value="Abbruch" title="&Auml;nderungen verwerfen und Kurs nicht speichern" />' . "\n";
			echo '</p>' . "\n";
			
			if ($showForm )
			{
				echo '<p>';
					echo 'Ich versichere mit dem Speichern, dass ich den Beitrag selbst verfasst habe bzw. 
							dass er keine fremden Rechte verletzt und willige ein, ihn unter der 
							<a href="http://creativecommons.org/licenses/by-sa/3.0/deed.de" target="_blank" rel="noopener noreferrer" title="Weitere Informationen auf creativecommons.org">Lizenz f&uuml;r freie Dokumentation</a> zu ver&ouml;ffentlichen.';
					echo '<br><br>Hinweis: Neue Angebote, neue Durchf&uuml;hrungen und neue Stichworte stehen evtl. erst am n&auml;chsten Tag &uuml;ber die Stichwort-Suche zur Verf&uuml;gung.<br>Auf den Detailseiten sind &Auml;nderungen sofort sichtbar.';
				echo '</p>';
				if( $kurs['rights_editTitel'] )
				{
					echo '<p>';
						echo 'Achtung: Neue Kurse m&uuml;ssen i.d.R. zun&uuml;chst <b>von der Redaktion freigeschaltet</b> werden. 
							Bis die neuen Kurse in den Ergebnislisten auftauchen, finden Sie sie unter unter der Ergebnisliste im Bereich <b>Kurse in Vorbereitung</b>.';
					echo '</p>';
				}
				echo '<p>';
					echo 'Weitere Optionen: ';
					echo '<a href="edit?action=ek&amp;id='.$kurs['id'].'&amp;deletekurs=1&amp;bwd='.urlencode($this->bwd).'" onclick="return editKursLoeschen($(this));">Diesen Kurs l&ouml;schen</a>';
					//echo ' | <a href="http://kursportal.info/cgi-bin/export/export_start.pl?id=' . $_SESSION['loggedInAnbieterId'] . '" target="_blank" rel="noopener noreferrer">Alle Kursdaten als CSV oder XML herunterladen</a>';
				echo '</p>';
			}
		
		echo '</form>' . "\n";

		echo $this->framework->getEpilogue();
	}


	/**************************************************************************
	 * Anbieterprofil bearbeiten
	 **************************************************************************/

	function loadAnbieterFromDb($anbieterId)
	{
		// anbieter laden - das zurueckgegebene Array ist wie bei loadKursFromPOST() beschrieben formatiert

		// kursdatensatz und alle durchfuehrungen lesen
		$db = new DB_Admin;
		$db->query("SELECT * FROM anbieter WHERE id=$anbieterId AND freigeschaltet = 1");
		if( $db->next_record() )
		{
			$anbieter = $db->Record;
		}
		else
		{
			$anbieter['error'][] = 'Die angegebene Kurs-ID existiert nicht oder nicht mehr.';
			return $anbieter;
		}
		
		return $anbieter;
	}

	function loadAnbieterFromPOST($anbieterId)
	{
		// kurs aus datanbank laden und mit den POST-Daten aktualisieren
		//
		// kurs ist ein array wie folgt:
		// 		$kurs['suchname']
		// 		$kurs['postname']
		//		.
		//		.
		//		$kurs['error'][]					(array mit Fehlermeldungen)
		
		$anbieter = $this->loadAnbieterFromDb($anbieterId);
		if( (array) sizeof($anbieter['error']) ) {
			return $anbieter;
		}

		// adresse
		$posted =  $_POST['strasse'];
		if( $posted == 'Strasse und Hausnr.' ) $posted = '';
		$anbieter['strasse'] = $posted;
			
		$posted =  $_POST['plz'];
		if( $posted == 'PLZ' ) $posted = '';
		$anbieter['plz'] = $posted;
		
		$posted =  $_POST['ort'];	
		if( $posted == 'Ort' ) $posted = '';
		$anbieter['ort'] = $posted;

		if( ($anbieter['strasse'].','.$anbieter['plz'].','.$anbieter['ort']) == $_POST['stadtteil_for'] )
			$anbieter['stadtteil'] = $_POST['stadtteil'];
		else
			$anbieter['stadtteil'] = '';

		// misc.
		$anbieter['rechtsform']		= intval($_POST['rechtsform']);
		$anbieter['gruendungsjahr']	= intval($_POST['gruendungsjahr']);
		$anbieter['leitung_name']	= $_POST['leitung_name'];
		$anbieter['homepage']		= $_POST['homepage'];
		$anbieter['anspr_name']		= $_POST['anspr_name'];
		$anbieter['anspr_zeit']		= $_POST['anspr_zeit'];
		$anbieter['anspr_tel']		= $_POST['anspr_tel'];
		$anbieter['anspr_fax']		= $_POST['anspr_fax'];
		$anbieter['anspr_email']	= $_POST['anspr_email'];
		$anbieter['pflege_name']	= $_POST['pflege_name'];
		$anbieter['pflege_tel']		= $_POST['pflege_tel'];
		$anbieter['pflege_fax']		= $_POST['pflege_fax'];
		$anbieter['pflege_email']	= $_POST['pflege_email'];
		
		return $anbieter;
	}
	
	function saveAnbieterToDb(&$newData)
	{
		// anbieter in datenbank speichern
		//
		// $kurs ist ein array wie unter loadAnbieterFromPOST() beschrieben, der Aufruf dieser Funktion kann dabei das 
		// Feld $anbieter['error'] erweitern; alle anderen Felder werden nur gelesen

		$db			= new DB_Admin;
		$user		= $this->getAdminAnbieterUserId20();
		$today		= strftime("%Y-%m-%d %H:%M:%S");
		$anbieterId	= $newData['id'];
		$oldData	= $this->loadAnbieterFromDb($anbieterId);
		if( sizeof((array) $oldData['error']) )
		{
			$newData['error'] = $oldData['error'];
			return;
		}
	
		$logwriter = new LOG_WRITER_CLASS;
		$logwriter->addDataFromTable('anbieter', $anbieterId, 'preparediff');
		
		if( $oldData['rechtsform']		!= $newData['rechtsform']
		 || $oldData['gruendungsjahr']	!= $newData['gruendungsjahr']
		 || $oldData['leitung_name']	!= $newData['leitung_name']
		 || $oldData['homepage']      	!= $newData['homepage'] 
		 || $oldData['strasse']      	!= $newData['strasse']
		 || $oldData['plz']      		!= $newData['plz']
		 || $oldData['ort']    			!= $newData['ort'] 
		 || $oldData['stadtteil']		!= $newData['stadtteil'] 		 
		 || $oldData['anspr_name']		!= $newData['anspr_name']
		 || $oldData['anspr_zeit']		!= $newData['anspr_zeit']
		 || $oldData['anspr_tel']		!= $newData['anspr_tel']
		 || $oldData['anspr_fax']		!= $newData['anspr_fax']
		 || $oldData['anspr_email']		!= $newData['anspr_email']
		 || $oldData['pflege_name']		!= $newData['pflege_name']
		 || $oldData['pflege_tel']		!= $newData['pflege_tel']
		 || $oldData['pflege_fax']		!= $newData['pflege_fax']
		 || $oldData['pflege_email']	!= $newData['pflege_email']
		 )
		{
			// update record
			$sql = "UPDATE anbieter SET rechtsform=".intval($newData['rechtsform']).",
									 gruendungsjahr=".intval($newData['gruendungsjahr']).",
									 leitung_name='".addslashes($newData['leitung_name'])."',
									 homepage='".addslashes($newData['homepage'])."', 
									 strasse='".addslashes($newData['strasse'])."',
									 plz='".addslashes($newData['plz'])."',
									 ort='".addslashes($newData['ort'])."',
									 stadtteil='".addslashes($newData['stadtteil'])."',
									 anspr_name='".addslashes($newData['anspr_name'])."',
									 anspr_zeit='".addslashes($newData['anspr_zeit'])."',
									 anspr_tel='".addslashes($newData['anspr_tel'])."',
									 anspr_fax='".addslashes($newData['anspr_fax'])."',
									 anspr_email='".addslashes($newData['anspr_email'])."',
									 pflege_name='".addslashes($newData['pflege_name'])."',
									 pflege_tel='".addslashes($newData['pflege_tel'])."',
									 pflege_fax='".addslashes($newData['pflege_fax'])."',
									 pflege_email='".addslashes($newData['pflege_email'])."', ";
			$sql .=			       " user_modified={$user}, ";	// der Benutzer, wird nur geaendert, wenn etwas im Protokoll steht; dies ist notwendig, da durch die Suche nach dem Benutzer (20) die Redaktion die Aenderungen im Protokoll ueberprueft
																// das Datum muss dagegen auch geaendert werden, wenn nur bei der Promotion etwas geaendert wurde, da ansonsten die Aenderungen nicht "live" geschaltet werden (bzw. nur stark verzoegert)
			$sql .=				   " date_modified='{$today}' WHERE id=$anbieterId;";
			$db->query($sql);

			
			// log after the record being written
			$logwriter->addDataFromTable('anbieter', $anbieterId, 'creatediff');
			$logwriter->log('anbieter', $anbieterId, $user, 'edit');
		}
	}
	
	function renderEditAnbieter()
	{
	    $loggedInAnbieterId = isset($_SESSION['loggedInAnbieterId']) ? $_SESSION['loggedInAnbieterId'] : null;
		$anbieterId = intval($_SESSION['loggedInAnbieterId']);
		$topnotes   = array();
		$showForm   = true;

		// see what to do ...
		if( $_POST['subseq'] == 1 && isset($_POST['cancel']) )
		{
			// ... a subsequent call: "Cancel" hit
			header('Location: ' . $this->bwd);
			exit();
		}
		else if( $_POST['subseq'] == 1 )
		{
			// ... save data
			$anbieter = $this->loadAnbieterFromPOST($anbieterId);
			if( sizeof( (array) $anbieter['error']) == 0 )
			{
				$this->saveAnbieterToDb($anbieter);
			} /* no else: saveAnbieterToDb() may also add errors */
						
			if( sizeof( (array) $anbieter['error']) )
			{
				$topnotes = $anbieter['error'];
			}
			else
			{
				$msg = 'Das Anbieterprofil wurde <b>erfolgreich gespeichert.</b>';
				setcookie('editmsg', $msg);
				
				$bwd = $this->bwd . (strpos($this->bwd,'?')===false?'?':'&') . 'rnd='.time();
				header('Location: ' . $bwd);
				exit();
			}	
		}
		else
		{
			// ... first call
			$anbieter = $this->loadAnbieterFromDb($anbieterId);
			if( sizeof( (array) $anbieter['error']) )
			{
				$topnotes = $anbieter['error'];
				$showForm = false;
			}
		}
		
		// render form		
		echo $this->framework->getPrologue(array('title'=>'Anbieterprofil bearbeiten', 'bodyClass'=>'wisyp_edit'));
		echo '<h1>Anbieterprofil bearbeiten</h1>';

		if( sizeof( (array) $topnotes) )
		{
			echo "<p class=\"wisy_topnote\">" .implode('<br />', $topnotes). "</p>";
		}

		$secureaction = "";
		
		if( $this->framework->iniRead('useredit.secure', 1)==1
		    && substr($_SERVER['HTTP_HOST'], -6)!='.local'
		    && $_SERVER['HTTPS'] == "on")
		{
		    $secureaction = 'https://' . $_SERVER['HTTP_HOST'] . '/edit';
		}
		
		$editurl = ($secureaction != "") ? $secureaction : "edit";
		
		echo '<form action="'.$editurl.'" method="post" name="anbieter">' . "\n";
			echo '<input type="hidden" name="action" value="ea" /> ' . "\n";
			echo '<input type="hidden" name="subseq" value="1" /> ' . "\n";
			echo '<input type="hidden" name="bwd" value="'.isohtmlspecialchars($this->bwd).'" /> ' . "\n";		

			if( $showForm )
			{
				echo '<table cellspacing="2" cellpadding="0" width="100%">';
				
					// Titel, ID
					echo '<tr>';
						echo '<td width="10%">Suchname:</td>';
						echo '<td width="90%">' .  isohtmlspecialchars($anbieter['suchname']) . '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">Anbieternummer:</td>';
						echo '<td width="90%">'.  isohtmlspecialchars($anbieter['id']) . '</td>';
					echo '</tr>';
					
					// firmenportrait
					echo '<tr>';
						echo '<td width="10%">Rechtsform:</td>';
						echo '<td width="90%">';
							$this->controlSelect('rechtsform', $anbieter['rechtsform'], $GLOBALS['codes_rechtsform']);
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">Gr&uuml;ndungsjahr:</td>';
						echo '<td width="90%">';
							$ausgabe_jahr = $anbieter['gruendungsjahr']<=0? '' : $anbieter['gruendungsjahr'];
							$this->controlText('gruendungsjahr', $ausgabe_jahr, 6, 4, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%" nowrap="nowrap">Name des Leiters:</td>';
						echo '<td width="90%">';
							$this->controlText('leitung_name', $anbieter['leitung_name'], 50, 200, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">Homepage:</td>';		
						echo '<td width="90%">';
							$this->controlText('homepage', $anbieter['homepage'], 64, 200, '', '');
						echo '</td>';
					echo '</tr>';

					// Adresse
					echo '<tr>';
						echo '<td width="10%">Adresse:</td>';		
						echo '<td width="90%">';
							$this->controlText('strasse', $anbieter['strasse'], 25, 100, 'Geben Sie hier - soweit bekannt und eindeutig - die Strasse und die Hausnummer ein', 'Strasse und Hausnr.');
				
							echo ' &nbsp; ';
			
							$this->controlText('plz', $anbieter['plz'], 5, 16, 'Geben Sie hier - soweit bekannt und eindeutig - die Postleitzahl ein', 'PLZ');
							echo ' ';
							$this->controlText('ort', $anbieter['ort'], 12, 60, 'Geben Sie hier - soweit bekannt und eindeutig - den Ort bzw. die Stadt ein', 'Ort');

							$this->controlHidden('stadtteil', $anbieter['stadtteil']);
							$this->controlHidden('stadtteil_for', $anbieter['strasse'].','.$anbieter['plz'].','.$anbieter['ort']);
						echo '</td>';
					echo '</tr>';

					// Kundenkontakt
					echo '<tr>';
						echo '<td colspan="2">&nbsp;<br /><strong>Kundenkontakt:</strong> (&ouml;ffentlich im Web sichtbar)</td>';		
					echo '</tr>';					
					echo '<tr>';
						echo '<td width="10%" nowrap="nowrap">Name:</td>';		
						echo '<td width="90%">';
							$this->controlText('anspr_name', $anbieter['anspr_name'], 50, 50, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">Sprechzeiten:</td>';		
						echo '<td width="90%">';
							$this->controlText('anspr_zeit', $anbieter['anspr_zeit'], 64, 200, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">Telefon:</td>';		
						echo '<td width="90%">';
							$this->controlText('anspr_tel', $anbieter['anspr_tel'], 32, 200, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">Telefax:</td>';		
						echo '<td width="90%">';
							$this->controlText('anspr_fax', $anbieter['anspr_fax'], 32, 200, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">E-Mail:</td>';		
						echo '<td width="90%">';
							$this->controlText('anspr_email', $anbieter['anspr_email'], 50, 200, '', '');
						echo '</td>';
					echo '</tr>';

					// Pflegekontakt
					echo '<tr>';
						echo '<td colspan="2">&nbsp;<br /><strong>Pflegekontakt:</strong> (nur f&uuml;r die interne Datenredaktion)</td>';		
					echo '</tr>';					
					echo '<tr>';
						echo '<td width="10%" nowrap="nowrap">Name:</td>';		
						echo '<td width="90%">';
							$this->controlText('pflege_name', $anbieter['pflege_name'], 50, 50, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">Telefon:</td>';		
						echo '<td width="90%">';
							$this->controlText('pflege_tel', $anbieter['pflege_tel'], 32, 200, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">Telefax:</td>';		
						echo '<td width="90%">';
							$this->controlText('pflege_fax', $anbieter['pflege_fax'], 32, 200, '', '');
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td width="10%">E-Mail:</td>';		
						echo '<td width="90%">';
							$this->controlText('pflege_email', $anbieter['pflege_email'], 50, 200, '', '');
						echo '</td>';
					echo '</tr>';
								
				echo '</table>';
			}
		
			echo '<p>' . "\n";
				if( $showForm )
				{
					echo '<input type="submit" value="OK - Anbieterprofil speichern" title="Alle &Auml;nderungen &uuml;bernehmen und Anbieterprofil speichern" style="font-weight: bold;" /> ' . "\n";
				}
				
				echo '<input type="submit" name="cancel" value="Abbruch" title="&Auml;nderungen verwerfen und Kurs nicht speichern" />' . "\n";
			echo '</p>' . "\n";
			
			if( $showForm )
			{
				$a = '';
				$aend = '';			
				$email = $this->framework->iniRead('useredit.help.mail.to', '');
				if( $email != '' ) {
					$a    = "<a href=\"mailto:{$email}\">";
					$aend = "</a>";
				}

				echo "<p>&Auml;nderungsbedarf in der Anbieterbeschreibung und weiteren Merkmalen bitte {$a}an die Redaktion mailen{$aend}.</p>";
			}
		
		echo '</form>' . "\n";
		
		echo $this->framework->getEpilogue();
	}

	
	 /**************************************************************************
	 * 20.09.2013 new AGB stuff
	 **************************************************************************/

	protected function _agb_get_hash()
	{
		$agb_glossar_entry = intval($this->framework->iniRead('useredit.agb', 0));
		if( $agb_glossar_entry <= 0 ) 
			return ''; // no AGB required

		$db = new DB_Admin;
		$db->query("SELECT erklaerung FROM glossar WHERE id=".$agb_glossar_entry);
		if( !$db->next_record() )
			return ''; // AGB record does not exist

		$temp = $db->fs('erklaerung');
		if( $temp == '' )
			return ''; // AGB are empty
			
		$temp = strtr($temp, "\n\r\t", "   "); $temp = str_replace(' ', '', $temp);
		$hash = md5($temp);
		
		return $hash; // AGB-hash to confirm
	}
	
	protected function _dataprotection_get_hash()
	{
		$dataprotection_glossar_entry = intval($this->framework->iniRead('useredit.datenschutz', 0));
		if( $dataprotection_glossar_entry <= 0 ) 
			return ''; 

		$db = new DB_Admin;
		$db->query("SELECT erklaerung FROM glossar WHERE id=".$dataprotection_glossar_entry);
		if( !$db->next_record() )
			return ''; // DP record does not exist

		$temp = $db->f8('erklaerung');
		if( $temp == '' )
			return ''; // DP are empty
			
		$temp = strtr($temp, "\n\r\t", "   "); $temp = str_replace(' ', '', $temp);
		$hash = md5($temp);
		
		// $db->close();
		return $hash;
	}
	
	protected function _agb_reading_required()
	{
	    if( isset($_SESSION['_agb_ok_for_this_session']) && $_SESSION['_agb_ok_for_this_session'] )
			return false; // AGB were okay at the beginning of the session, keep this state to avoid annoying AGB popups during editing

		$soll_hash = $this->_agb_get_hash().$this->_dataprotection_get_hash();
		if( $soll_hash == '' )
			return false; // no AGB reading required
		
		if( $this->_anbieter_ini_read('useredit.agb.accepted_hash', '') == $soll_hash )
			return false; // AGB already read

	    $loggedInAnbieterId = isset($_SESSION['loggedInAnbieterId']) ? $_SESSION['loggedInAnbieterId'] : null;
		$need_to_accept_dataprotection = intval($this->framework->iniRead('useredit.datenschutz', 0));
		if( $_REQUEST['agb_accepted'] == "on" && $soll_hash == $_REQUEST['agb_hash'] && ($need_to_accept_dataprotection && $_REQUEST['dataprotection_accepted'] == "on" || !$need_to_accept_dataprotection) ) 
		{
		    if( !isset($_SESSION['_login_as']) || !$_SESSION['_login_as'] ) {
				$this->_anbieter_ini_settings['useredit.agb.accepted_hash'] = $soll_hash;
				$this->_anbieter_ini_write();
				$logwriter = new LOG_WRITER_CLASS;
				$logwriter->log('anbieter', intval($loggedInAnbieterId), $this->getAdminAnbieterUserId20(), 'agbaccepted');
				
				$db = new DB_Admin;
				$today = strftime("%d.%m.%y");
				$db->query("UPDATE anbieter SET notizen = CONCAT('$today: AGB akzeptiert\n', notizen) WHERE id=".intval($_SESSION['loggedInAnbieterId']));
			}
			
			$_SESSION['_agb_ok_for_this_session'] = true;
			
			if( isset($_REQUEST['fwd']) )
			 header("Location: ".$_REQUEST['fwd']);
			exit();
		}
			
		return true; // AGB reading required!
	}
	
	protected function _render_agb_screen()
	{
		$agb_glossar_entry = intval($this->framework->iniRead('useredit.agb', 0));
		$db = new DB_Admin;
		$db->query("SELECT begriff, erklaerung FROM glossar WHERE id=".$agb_glossar_entry);
		$db->next_record();
		$begriff = $db->fs('begriff');
		$erklaerung = $db->fs('erklaerung');
		
		$dataprotection_glossar_entry = intval($this->framework->iniRead('useredit.datenschutz', 0));
		if($dataprotection_glossar_entry > 0) {
			$db = new DB_Admin;
			$db->query("SELECT begriff, erklaerung FROM glossar WHERE id=".$dataprotection_glossar_entry);
			$db->next_record();
			$begriff_dataprotection = $db->f('begriff');
			$erklaerung_dataprotection = $db->f('erklaerung');
		}

		echo $this->framework->getPrologue(array('title'=>$begriff, 'bodyClass'=>'wisyp_edit'));
		
			echo '<h1>Zur Bearbeitung Ihrer Daten ist Ihre Zustimmung zu den AGB'.($erklaerung_dataprotection ? ' und der Datenschutzerkl&auml;rung' : '').' n&ouml;tig.</h1>';
			echo '<h1>Grund: &Auml;nderung seit Ihrem letzten Login oder Ihr erster Login.</h1>';
			echo '<br><br><br>';
			
			echo '<a name="top"></a>'; // make [[toplinks()]] work
			echo '<h1>' . isohtmlspecialchars($begriff) . '</h1>';
			$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
			$wiki2html->forceBlankTarget = true;
			echo $wiki2html->run($erklaerung);
			
			if($dataprotection_glossar_entry > 0 && $erklaerung_dataprotection) {
				echo '<hr><hr>';
				echo '<h1>' . htmlspecialchars($begriff_dataprotection) . '</h1><br><br>';
				$wiki2html =& createWisyObject('WISY_WIKI2HTML_CLASS', $this->framework);
				$wiki2html->forceBlankTarget = true;
				echo $wiki2html->run($erklaerung_dataprotection);
			}
			
			$fwd = 'search';
			if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'ek' ) {
			    if( isset($_REQUEST['id']) )
				    $fwd = "edit?action=ek&id=".intval($_REQUEST['id']);
			}
			else if( isset($_REQUEST['fwd']) ) {
				$fwd = $_REQUEST['fwd'];
			}
			
			echo '<hr><br><br><form action="edit" method="post">';
				echo '<input type="hidden" name="fwd" value="'.htmlspecialchars($fwd).'" />';
				echo '<input type="hidden" name="agb_hash" value="'.htmlspecialchars($this->_agb_get_hash()).htmlspecialchars($this->_dataprotection_get_hash()).'" />';
				echo '<input type="checkbox" name="agb_accepted"> <b>Ich habe die AGB gelesen und akzeptiere diese.</b><br>';
				if($dataprotection_glossar_entry > 0)
					echo '<input type="checkbox" name="dataprotection_accepted"> <b>Ich habe die Datenschutzerkl&auml;rung gelesen und stimme dieser zu.</b><br>';
					
				echo '<br><input type="submit" value="speichern" style="font-weight: bold; font-size: 1em;" />';
				echo '<br><br><a href="'.$this->framework->getUrl('edit', array('action'=>'logout')).'">Abbruch - Ich stimme einigen Bedingungen NICHT ZU</a>';
			echo '</form><br><br>';
			
			if( isset($_SESSION['_login_as']) && $_SESSION['_login_as'] ) {
				echo '<p style="background:red; color:white; padding:1em; "><b>Achtung:</b> Sie haben sich als Redakteur im Namen eines Anbieters, 
					der die AGB noch nicht best&auml;tigt hat, eingeloggt. Wenn Sie die AGB jetzt best&auml;tigen, gilt dies nur f&uuml;r die aktuelle Sitzung; 
					der Anbieter wird die AGB sobald er sich selbst einloggt erneut best&auml;tigen m&uuml;ssen. Dieser Hinweis erscheint nur f&uuml;r Redakteure.</p>';
			}
			
		echo $this->framework->getEpilogue();
	}
	 
	 /**************************************************************************
	 * edit main() - see what to do
	 **************************************************************************/
	
	function render()
	{
	    $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
	
		if( $action == 'forgotpw' )
		{
			$ob =& createWisyObject('WISY_EDIT_FORGOTPW_CLASS', $this->framework, array('adminAnbieterUserId'=>$this->getAdminAnbieterUserId20()));
			$ob->renderForgotPwScreen();
		}
		else if( $this->framework->getEditAnbieterId() <= 0 )
		{	
			$this->renderLoginScreen();
		}
		else if( $action == 'logout' )
		{
			$this->renderLogoutScreen();
		}
		else if( $this->_agb_reading_required() )
		{
			$this->_render_agb_screen();
		}
		else 
		{
			// these are the normal edit actions, for these actions the AGB must be accepted!
			
			$_SESSION['_agb_ok_for_this_session'] = true; // only check the AGB once a session - otherwise it may happend that during editing new AGB occur!
			switch( $action )
			{
				 case 'loginSubseq': // diese Bedingung sollte eigentlich komplett von renderLoginScreen() oben augehandet sein ... aber es schadet auch nichts ...
					$this->renderLoginScreen();
					break;

				 case 'ek':
				     $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
				     $this->renderEditKurs(intval($id) /*may be "0" for "new kurs"*/ );
				     break;
				     
				 case 'ea':
					$this->renderEditAnbieter(); // always edit the "logged in" anbieter

					break;

				case 'kt':
					$this->renderEditKonto();
					break;
		
				default:
					$this->framework->error404();
					break;
			}
		}
	}
	
};