<?php

/*=============================================================================
login functions
===============================================================================

file:	
	login.inc.php
	
author:	
	Bjoern Petersen

parameters:
	none

usage:
	login_check();
	login_screen();

	you can also login using "<your loginname> as <other loginname>" if you
	have write-access to "<other loginname>"

=============================================================================*/




function get_first_accecssible_table()
{
	global $Table_Def;
	for( $t = 0; $t < sizeof((array) $Table_Def); $t++ ) {
		if( ($Table_Def[$t]->flags & TABLE_PRIMARY)
		 && acl_get_access($Table_Def[$t]->name.'.COMMON') ) {
			return $Table_Def[$t]->name;
		}
	}
	return '';
}



function login_screen()
{    
    global $site;
    global $denymessage;
    
    $enter_loginname = isset( $_REQUEST['enter_loginname'] ) ? @strval( $_REQUEST['enter_loginname'] ) : '';
    $enter_password = '';
    $resetlogin = isset( $_REQUEST['resetlogin'] ) ? $_REQUEST['resetlogin'] : null;
    
    require_lang('lang/login');
    
    $db = new DB_Admin;
    
    if( defined('FORCE_SECURE_HOST') && FORCE_SECURE_HOST && ( !isset( $_SERVER['HTTPS']  ) || $_SERVER['HTTPS']!='on' )
        && ( !isset( $_REQUEST['enter_subsequent'] ) || !$_REQUEST['enter_subsequent'] ) && !isset($_REQUEST['logout']) ) {
            redirect('https://' . SECURE_HOST . '/'.$site->adminDir.'/index.php?enter_subsequent=1');
        }

	//
	// get login data from cookie
	//
	/*
	if( regGet('login.remember', 0) 
	 && !$enter_loginname 
	 && $_COOKIE['g_cookie_login'] ) 
	{
		$enter_loginname = explode('&', $_COOKIE['g_cookie_login']);
		$enter_password = '';
		for( $i = 0; $i < $enter_loginname[2]; $i++ ) {
			$enter_password .= '*';
		}
		$enter_loginname = urldecode($enter_loginname[1]);
	}
	else 
	{
		$enter_password = '';
	}
	*/

	//
	// additional message
	//
        if( isset( $_REQUEST['logout'] ) && $_REQUEST['logout'] == 'pwchanged' ) {
		$site->msgAdd(htmlconstant('_LOGIN_PASSWORDCHANGED'), 'i');
	}
	else if(isset( $_REQUEST['logout'] ) && $_REQUEST['logout'] && regGet('msg.afterlogout', '') ) {
		$msg = regGet('msg.afterlogout', '');
		if( $msg ) {
			$site->msgAdd("\n\n$msg\n\n", 'i');
		}
	}
	else if( $site->msgCount() == 0 ) {
		$msg = regGet('msg.beforelogin', '');
		if( $msg ) {
			$site->msgAdd("\n\n$msg\n\n", 'i');
		}
	}
	
	//
	// render page...
	//

	$site->pageStart();
	
	if ($denymessage) {
	    print        $denymessage;
	    $site->pageEnd();
	    exit();
	}
	
	$site->menuHelpScope = '.';
	$site->menuOut();

	form_tag('form_enter', 'index.php');
	form_hidden('enter_subsequent', 1);

	$site->skin->dialogStart();
			
			//
			// ...login name
			//
			form_control_start(htmlconstant('_LOGINNAME'));
			
				$userlist = intval(regGet('login.userlist', 0));
				
				if( $enter_loginname == ' as ' ) 
				{
					$enter_loginname = '';
					$userlist = 0;
				}
				
				if( $userlist )
				{
					$opts = '';
					$isInList = 0;
					$db->query("SELECT loginname FROM user WHERE NOT(loginname LIKE 'template%') ORDER BY loginname");
					while( $db->next_record() ) {
						$currLoginname = $db->fs('loginname');
						$opts .= $currLoginname . '###' . isohtmlentities( strval( $currLoginname ) ) . '###';
						if( $enter_loginname=='' || $enter_loginname==$currLoginname ) {
							$isInList = 1;
						}
					}

					$opts .= ' as ###' . htmlconstant('_MORE___');
					
					if( $isInList ) {
						form_control_enum('enter_loginname', 
							$enter_loginname, 
							$opts, 
							0, 
							'width:20em;', 
							"if(this.options[this.selectedIndex].value==' as '){this.form.submit();}", 
							$userlist<5? 5 : $userlist);
					}
					else {
						$userlist = 0;
					}
				}
				
				if( !$userlist )
				{
					form_control_text('enter_loginname', $enter_loginname);
				}
				
			form_control_end();
			
			//
			// ...password
			//
			form_control_start(htmlconstant('_PASSWORD'));
				form_control_password('enter_password', $enter_password, 40, $userlist? 'style="width:20em;"' : '');
			form_control_end();
			
			//
			// ...language
			//
			$langs = get_avail_lang_from_folder(regGet('login.lang', ''));
			reset($langs);
			if( sizeof((array) $langs)==1 )
			{
				$abbr = array_keys($langs);
				$abbr = $abbr[0]; // array_key_first() only > php7
				form_hidden('g_do_session_language_change', $abbr);
			}
			else
			{
				$opts = '';
				foreach($langs as $abbr => $name) {
					if( $opts ) { $opts .= '###'; }
					$opts .= "$abbr###$name ($abbr)";
				}
				form_control_start(htmlconstant('_LANGUAGE'));
				form_control_enum('g_do_session_language_change', ( isset( $_SESSION['g_session_language'] ) ? $_SESSION['g_session_language'] : null ), $opts);
				form_control_end();
			}
			
			//
			// ...remember login
			//
			/*
			if( regGet('login.remember', 0) ) {
				form_control_start(htmlconstant('_REMEMBERLOGIN'));
					form_control_check('enter_rememberlogin', $_COOKIE['g_cookie_login']?1:0);
				form_control_end();
			}
			*/
			
	$site->skin->dialogEnd();

	$site->skin->buttonsStart();
		form_button('enter_ok', htmlconstant('_OK'));
		echo '<label for="resetlogin">Suchanfragen zur&uuml;cksetzen</label>';
		form_control_check('resetlogin', $resetlogin);
	$site->skin->buttonsEnd();

	//
	// test for java script
	//
	echo "<script type=\"text/javascript\"><!--\n";
		echo "document.write('<input type=\"hidden\" name=\"enter_js\" value=\"1\" />');";
	echo "\n/"."/--></script>";

	//
	// test for display:none and formular parameters
	//
	echo '<div style="display:none;">';
		echo 'the following controls are for testing purpose only and should normally not appear:';
		echo '<input type="text" name="enter_displaynoneform1" value="works1" />';
		echo '<input type="checkbox" name="enter_displaynoneform2" value="works2" checked="checked" />';
		echo '<input type="checkbox" name="enter_displaynoneform3" value="works3" />';
		echo '<select name="enter_displaynoneform4"><option value="worksdummy">worksdummy</option><option value="works4" selected="selected">works4</option></select>';
		echo '<textarea name="enter_displaynoneform5">works5</textarea>';
	echo '</div>';
	
	echo '</form>';
		
	$site->pageEnd();
	
	exit();
}



function login_check()
{
    global $site;
    global $salt;
    global $denymessage;
    $denymessage = "";
    
    $db = new DB_Admin;
    $ip = $_SERVER['REMOTE_ADDR'];
    
    $sqlstmt = "DELETE FROM x_logins WHERE timestamp < DATE_SUB( NOW( ) , INTERVAL 24 HOUR )";  // alles was aelter als 24 h loeschen
    $db->query($sqlstmt);
    
    /* if( $_REQUEST['enter_subsequent'] && $_REQUEST['enter_loginname'] && !is_array($_REQUEST['enter_loginname']) ) {
     $sqlstmt = "INSERT INTO x_logins (ip, login_name) VALUES (AES_ENCRYPT('$ip', '$salt'), '".addslashes(strval( $_REQUEST['enter_loginname'] ))."')";
     $db->query($sqlstmt);
     } */
    
    $sqlstmt = "SELECT COUNT(*) AS COUNT FROM x_logins WHERE AES_DECRYPT(ip, '$salt') = '$ip' and freischalten > NOW() OR freischalten = '0000-00-00 00:00:00'";
    $db->query($sqlstmt);
    $db->next_record();
    $loginversuche = $db->fs('COUNT');
    if ($loginversuche > 50) {
        /* if($_REQUEST['enter_subsequent']) {
            $sqlstmt = "UPDATE x_logins SET freischalten = now()+ 144000 WHERE AES_DECRYPT(ip, '$salt') = '$ip'";
            $db->query($sqlstmt);
        }
        $denymessage = "Zu viele falsche Login-Versuche. Ihre IP-Adresse wurde f&uuml;r 24 Stunden gesperrt"; */
    } else if ($loginversuche > 10) {
     /* if($_REQUEST['enter_subsequent']) {
            $sqlstmt = "UPDATE x_logins SET freischalten = now()+ 1000 WHERE AES_DECRYPT(ip, '$salt') = '$ip'";
            $db->query($sqlstmt);
        }
        $denymessage = "Zu viele falsche Login-Versuche. Ihre IP-Adresse wurde f&uuml;r 10 Minuten gesperrt"; */
    }
    

	// if a role-confirmation screen was printed, the user already entered the password successfully; read it from the session in this case
	if( isset($_REQUEST['role_confirm_ok']) ) {
	    $_REQUEST['enter_password'] = isset($_SESSION['g_role_confirm_login_credential_pw']) ? strval($_SESSION['g_role_confirm_login_credential_pw']) : '';
	}
	else if( isset($_REQUEST['role_confirm_cancel']) && isset($_REQUEST['enter_subsequent'])  ) {
		unset($_REQUEST['enter_subsequent']);
	}
	if( isset( $_SESSION['g_role_confirm_login_credential_pw'] ) )
	   unset( $_SESSION['g_role_confirm_login_credential_pw'] );

	// get loginname/password from the request
	$enter_loginname = isset( $_REQUEST['enter_loginname'] ) ? @strval( $_REQUEST['enter_loginname'] ) : null;
	$enter_password = isset( $_REQUEST['enter_password'] ) ? @strval( $_REQUEST['enter_password'] ) : null;
	$resetlogin = isset( $_REQUEST['resetlogin'] ) ? $_REQUEST['resetlogin'] : null;
	
	require_lang('lang/login');

	$logwriter = new LOG_WRITER_CLASS;
	
	//
	// create root user with the access to create users (if no user exists at all)
	//
	$db = new DB_Admin;
	$db->query("SELECT COUNT(*) AS cnt FROM user;");
	$db->next_record();
	if( $db->f('cnt') <= 1 /*user 'template' may be created before, so there should exist two users*/ ) 
	{
	    $today = ftime("%Y-%m-%d %H:%M:%S");
		$db->query("");
		$logwriter->log('user', $db->insert_id(), 0, 'create');
	}
	
	//
	// anything to check?
	//
	if( ( !isset( $_REQUEST['enter_subsequent'] ) || !$_REQUEST['enter_subsequent'] ) 
	 ||  $enter_loginname == '' 
	 ||  $enter_loginname == ' as ' 
	 || !isset($enter_password) )
	{
		login_screen();
		exit();
	}

	//
	// ENVIRONMENT CHECK, part 2: things that can be changed by the user
	//
	if( !isset($_REQUEST['enter_skip_env_tests']) ) 
	{
		$missing_features = array();
	
		if( !isset($_REQUEST['enter_js']) || $_REQUEST['enter_js']!=1 )
		{
			$missing_features[] = 'JavaScript';
		}

		if( !isset($_COOKIE[session_name()]) )
		{
			$missing_features[] = 'Cookies';
		}
	
		if( !isset($_REQUEST['enter_displaynoneform1']) || strval($_REQUEST['enter_displaynoneform1'])!='works1'
		 || !isset($_REQUEST['enter_displaynoneform2']) || strval($_REQUEST['enter_displaynoneform2'])!='works2'
		 ||  isset($_REQUEST['enter_displaynoneform3'])
		 || !isset($_REQUEST['enter_displaynoneform4']) || strval($_REQUEST['enter_displaynoneform4'])!='works4'
		 || !isset($_REQUEST['enter_displaynoneform5']) || strval($_REQUEST['enter_displaynoneform5'])!='works5' )
		{
			$missing_features[] = 'SubmitInvisibleFormElements'; //  this feature is missing in some older Opera versions, however, always check this!
		}
	
		if( sizeof($missing_features) )
		{
			$site->msgAdd(htmlconstant('_LOGIN_FEATUREMISSING', implode(', ', $missing_features)), 'e');
			login_screen();
			exit();
		}
	}
	
	//
	// get loginname for the user and the loginname to log-in as
	//
	$logwriter->addData('ip', (isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '') );
	$logwriter->addData('browser', ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' ) );
	$logwriter->addData('loginname', $enter_loginname);
	
	if( regGet('login.as', 0) && strlen($enter_loginname) && strpos($enter_loginname, ' as ') ) {
		list($enter_loginnameuser, $enter_loginnameas) = explode(' as ', $enter_loginname);
		$enter_as = 1;
	}
	else {
		$enter_loginnameuser= $enter_loginname;
		$enter_loginnameas	= $enter_loginname;
		$enter_as = 0;
	}

	//
	// try to log-in: read record
	//
	$db->query("SELECT id, password, last_login, num_login_errors FROM user WHERE loginname='" .addslashes($enter_loginnameuser). "'");
	if( !$db->next_record() ) 
	{
		$site->msgAdd(htmlconstant('_LOGIN_ERR'));
		
		$logwriter->addData('msg', 'Benutzer "'.$enter_loginnameuser.'" existiert nicht.');
		$logwriter->log('user', 0, 0, 'loginfailed');
		
		sleep(1);
		login_screen(); // error
		exit();
	}

	//
	// record read successfully, check password
	//
	$db_id				= intval($db->f('id'));
	$db_password		= $db->fs('password');
	$db_last_login		= $db->f('last_login');
	$db_num_login_errors= $db->f('num_login_errors');
	$cookie_password	= isset( $_COOKIE['g_cookie_login'] ) ? explode('&', $_COOKIE['g_cookie_login']) : '';
	$cookie_passwordlen	= isset( $cookie_password[2] ) ? $cookie_password[2] : 0;
	$cookie_password	= isset( $cookie_password[3] ) ? urldecode( $cookie_password[3] ) : '';
	if	(
			$db_num_login_errors<1000 /* about 500 login errors to lock the accout */
	 		&&
	 		(
	 			crypt($enter_password, $db_password) == $db_password 
				/*
				||
				(
					regGet('login.remember', 0)
					&&
					$cookie_passwordlen == strlen($enter_password)
					&&
	 				crypt($db_password, $cookie_password) == $cookie_password 
	 			)
				*/
				||
				(
					strlen($db_password) < 12 
					&& 
					$db_password == $enter_password
				)
			) 
		)
	{
		// if the password is not yet crypted, crypt it now
		if( strlen($db_password) < 12 ) {
			$db_password = crypt($db_password, $salt);
			$db->query("UPDATE user SET password='" .addslashes($db_password). "' WHERE id=$db_id");
		}
		
		// set / remove cookie
		/*
		if( regGet('login.remember', 0) ) {
			if( isset($_REQUEST['enter_rememberlogin']) ) {
				setcookie('g_cookie_login', urlencode($_SESSION['g_session_language'])."&".urlencode($enter_loginname)."&".strlen($enter_password)."&".urlencode(crypt($db_password, $salt)), time()+(10*24*60*60)); // expires in 10 days
			}
			else if( $_COOKIE['g_cookie_login'] ) {
				setcookie('g_cookie_login'); // remove cookie
			}
		}
		*/
		
		// set last login time, reset login error counter
		$new_login_errors = $enter_as? 0 : 1;
		// last_login_id NOT (to be used) for security purposes!
		$db->query("UPDATE user SET last_login_id='" .berechne_loginid(). "', last_login='" .ftime("%Y-%m-%d %H:%M:%S"). "', num_login_errors=$new_login_errors WHERE id=$db_id");
		
		
		// setcookie("editor", "yes");
	}
	else
	{
		// invalid password
	    $db->query("UPDATE user SET last_login_error='" .ftime("%Y-%m-%d %H:%M:%S"). "', num_login_errors=" .($db_num_login_errors+2). " WHERE id=$db_id");
		$site->msgAdd(htmlconstant('_LOGIN_ERR'));

		$logwriter->addData('msg', 'Benutzer "'.$enter_loginnameuser.'" hat ein falsches Passwort eingegeben.');
		$logwriter->log('user', $db_id, $db_id, 'loginfailed');
		
		sleep(1);
		login_screen(); // error
		exit();
	}
	
	//
	// login as the given user
	//
	$db->query("SELECT id, loginname, name, last_login, last_login_error, num_login_errors, msg_to_user FROM user WHERE loginname='" .addslashes($enter_loginnameas). "'");
	if( !$db->next_record() ) {
		$site->msgAdd(htmlconstant('_LOGIN_ERR'));
		
		$logwriter->addData('msg', 'Benutzer "'.$enter_loginnameas.'" existiert nicht.');
		$logwriter->log('user', 0, 0, 'loginfailed');
		
		login_screen();
		exit();
	}
	
	if( $enter_as ) {
		if( !acl_check_access('user.COMMON', $db->f('id'), ACL_EDIT, $db_id, 0) ) {
			$site->msgAdd(htmlconstant('_LOGIN_ERR'));
			
			$logwriter->addData('msg', 'Benutzer "'.$enter_loginnameuser.'" hat zuwenig Rechte um sich als "'.$enter_loginnameas.'" einzuloggen.');
			$logwriter->log('user', $db->f('id'), $db->f('id'), 'loginfailed');
			
			login_screen();
			exit();
		}

		$db_num_login_errors= $db->f('num_login_errors');
		$db_last_login		= $db->f('last_login');
	}

	$user_about_to_log_in = $db->f('id');
	
	//
	// check, if the user needs to confirm a new text
	//
	if( defined('USE_ROLES') )
	{
		$db3 = new DB_Admin;
		$db3->query("SELECT r.text_to_confirm, u.settings FROM user u LEFT JOIN user_roles r ON r.id=u.attr_role WHERE u.id=".$user_about_to_log_in);
		if( $db3->next_record() ) {
			$text_to_confirm = $db3->fs('text_to_confirm');
			$temp_settings = regLoadFromDb__($db3->fs('settings'));
			if( $text_to_confirm )
			{
				$md5_to_confirm = md5($text_to_confirm);
				if( strval($temp_settings['role.confirmed']) !== strval($md5_to_confirm) ) { // regGet() does not yet work!
					require_once('roleconfirm.inc.php');
					roleconfirm_check($user_about_to_log_in); // the function may call exit()
					$db_num_login_errors = 0;
				}
			}
		}
	}	
	
	//		
	// get common values to session
	//
	$_SESSION['g_session_userid'] = $user_about_to_log_in;
	$_SESSION['g_session_userloginname'] = $db->fs('loginname');
	
	$userID = isset( $_SESSION['g_session_userid'] ) ? $_SESSION['g_session_userid'] : null;
	$logwriter->log('user', $userID, $userID, 'login');
	
	//
	// get 'real' user name
	//
	$username = $db->fs('name');
	if( !$username ) {
	    $username = isset( $_SESSION['g_session_userloginname'] ) ? $_SESSION['g_session_userloginname'] : '';
	}
	$msg_to_user = $db->fs('msg_to_user');

	//
	// make settings non-editable if logged in as another user 
	// (should be done _after_ settings common values)
	//
	if( $enter_as && substr($enter_loginnameas, 0, 8)!='template' ) {
		regSet('settings.editable', 0, 1);
	}

		// DEPRECATED 13:23 26.09.2013
		if( regGet('edit.oldeditor', 0) ) {
			setcookie('oldeditor', 1, time()+60*60*24*100);
			$_COOKIE['oldeditor'] = 1; // we use as cookie as at the time, the editor decision takes place, the session is not yet available :-|
		}
		// /DEPRECATED 13:23 26.09.2013
	
	//
	// messages
	//
	require_lang('lang/overview');
	$site->msgAdd(htmlconstant('_LOGIN_WELCOME', "<b>$username</b>", sql_date_to_human($db_last_login, 'datetime')) . "\n", 'i');
	
	$sqlstmt = "DELETE FROM x_logins WHERE AES_DECRYPT(ip, '$salt') = '$ip'";  // bei erfolgreichem login werden die Versuche geloescht
	$db->query($sqlstmt);
	
	if( isset( $_REQUEST['resetlogin'] ) && $_REQUEST['resetlogin'] ) {
	    regSet("index.view.kurse.lastquery", '', '');
	    regSet("index.view.kurse.rows", '20', '');
	    regSet("index.view.anbieter.lastquery", '', '');
	    regSet("index.view.anbieter.rows", '20', '');
	    regSet("index.view.glossar.lastquery", '', '');
	    regSet("index.view.glossar.rows", '20', '');
	    regSet("index.view.portale.lastquery", '', '');
	    regSet("index.view.portale.rows", '20', '');
	    regSet("index.view.stichwoerter.lastquery", '', '');
	    regSet("index.view.stichwoerter.rows", '20', '');
	}
	
	$msg = regGet('msg.afterlogin', '');
	if( $msg ) {
		$site->msgAdd("\n\n$msg\n\n", 'i');
	}

	if( $msg_to_user ) {
		$site->msgAdd("\n\n$msg_to_user\n\n", 'i');
	}

		// DEPRECATED
	// $site->msgAdd("\n\n" . '<b>Neuer Editor:</b> Unter &quot;Einstellungen / Ansicht&quot; steht Ihnen ab sofort ein neuer, modernerer Editor zur Verfuegung. <a href="https://b2b.kursportal.info/index.php?title=Neuer_Editor" target="_blank" rel="noopener noreferrer">Weitere Informationen...</a>' . "\n\n", 'i');
		// DEPRECATED

	if( regGet('settings.editable', 1) && $db_num_login_errors ) { 
		$site->msgAdd(htmlconstant('_LOGIN_WARNING', sql_date_to_human($db->f('last_login_error'))), 'w');
	}

	if( regGet('login.tipsntricks.use', 1)	// global setting
	 && regGet('login.tipsntricks', 1) ) 	// uset setting
	{
		require_once('tipsntricks.php');
		$site->msgAdd(tipsntricks_get_next(), 'i'); // regSave() done below
	}
	
	$sync_tools = new SYNC_TOOLS_CLASS();
	$sync_tools->validate_sync_values($msgsync, $msgtype);
	if( $msgsync != '' )
	{
		$site->msgAdd($msgsync, $msgtype);
	}
	
	//
	// save some data from role confirmation (we cannot do this in roleconfirm_check() as the user is not set up there and eg. regSet() does not work)
	//
	if( isset($GLOBALS['role_just_confirmed']) ) {
		roleconfirm_after_login($_SESSION['g_session_userid']);
	}
	
	//
	// goto desired page
	//
	if( regGet('login.lastpage', '') ) {
		$url = regGet('login.lastpage', '');
	}
	else {
		$table = get_first_accecssible_table();
		$url = $table? "index.php?table=$table" : 'etc.php';
	}
	
	regSet('login.lastpage', '', ''); // clear this to avoid continuous errors
	regSave();
	redirect($url);
	exit();
}