<?php

define('IMP_ROWS_PER_PAGE', 15);
require_once('index_tools.inc.php');


class IMP_OPTIONSRENDERER_CLASS extends IMP_FUNCTIONS_CLASS
{
	var $arrow_green	= '<img id="impa%s" src="lib/imp/img/agreen%s.gif" />';	
	var $arrow_red 		= '<img id="impa%s" src="lib/imp/img/ared%s.gif" />';	
	var $arrow_equal	= '<img id="impa%s" src="lib/imp/img/aequal.gif" />';	
	var $arrow_delete	= '<img id="impa%s" src="lib/imp/img/adel%s.gif" />';	

	function __construct($page, $mix)
	{
		parent::__construct($page, $mix);
	}

	private function create_browsing_list($showall)
	{
		set_time_limit(0); // may take a while ...

		$possible_changes = array();
		$equal_records = 0;
		$equal_date = '0000-00-00 00:00:00';
		
		$del_cnt = 0;
		$ovw_cnt = 0;
		$tables = $this->mixfile->get_tables();
		for( $t = 0; $t < sizeof((array) $tables); $t++ )
		{
			$records = $this->mixfile->get_records($tables[$t], GET_UPDATES|GET_DELETE);
			reset($records);
			foreach($records as $id => $currRecord)
			{	
			    $srcDateModified = isset($currRecord['src_date_modified']) ? $currRecord['src_date_modified'] : '';
			    $destDateModified = isset($currRecord['dest_date_modified']) ? $currRecord['dest_date_modified'] : '';
			    
				if( $showall || $destDateModified != $srcDateModified ) {
					$possible_changes[] = array(
						'table'					=> $tables[$t],
						'id'					=> $id,
					    'dest_date_modified'	=> $destDateModified,
					    'src_date_modified'		=> $srcDateModified
					);
					
					if( !isset($currRecord['src_date_modified']) ) {
						$del_cnt++;
					}
					else if( $destDateModified > $srcDateModified ) {
						$ovw_cnt ++;
					}
				}
				else {
					$equal_records ++;
					if( $srcDateModified > $equal_date )
					    $equal_date = $srcDateModified;
				}
			}
		}
		return array( 	
			'equal_records' => $equal_records,
			'equal_date' => $equal_date,
			'possible_changes' => $possible_changes,
			'update_time' => ftime("%Y-%m-%d %H:%M:%S"),
			'del_cnt' => $del_cnt,
			'ovw_cnt' => $ovw_cnt,
		);
	}

	private function get_browsing_list($showall)
	{
		$key = $this->mix.'-v2-'.$showall;
		
		// check, if the cache is valid
		$recreate = false;
		if( isset($_REQUEST['update']) || !isset($_SESSION['mixfile_bl_cache'][$key]) ) 
		{
			$recreate = true;
		}
		else
		{
			$db = new DB_Admin;
			$tables = $this->mixfile->get_tables();
			for( $t = 0; $t < sizeof((array) $tables); $t++ ) {
				//$sql = "SELECT MAX(date_modified) AS Update_time FROM " . $tables[$t];
				$sql = "SHOW TABLE STATUS LIKE '{$tables[$t]}'"; // this is a much better approch as this also includes deletion or insertion of records with older dates (as usual on imports)
				$db->query($sql); 
				if( $db->next_record() ) {
				    $updateTime = isset($_SESSION['mixfile_bl_cache'][$key]['update_time']) ? $_SESSION['mixfile_bl_cache'][$key]['update_time'] : null;
					if( $db->fs('Update_time') > $updateTime ) {
						$recreate = true;
						break;
					}
				}
			}
		}
	
		// create a new list, if needed
		if( $recreate ) 
		{
			$_SESSION['mixfile_bl_cache'][$key] = $this->create_browsing_list($showall);
			$this->recreated = true;
		}
		
		// done
		return (isset($_SESSION['mixfile_bl_cache'][$key]) ? $_SESSION['mixfile_bl_cache'][$key] : null);
	}
	
	function render()
	{
		global $site;
		
		// get settings from mixfile
		$offset    		= isset($_SESSION['mixfile_offsets'][$this->mix]) ? intval($_SESSION['mixfile_offsets'][$this->mix]) : 0;
		$showall   		= isset($_SESSION['mixfile_showall'][$this->mix]) ? intval($_SESSION['mixfile_showall'][$this->mix]) : null;
		$overwrite 		= $this->mixfile->ini_read('option_overwrite', IMP_OVERWRITE_OLDER);
		$delete    		= $this->mixfile->ini_read('option_delete', IMP_DELETE_NEVER);
		$further_options= $this->mixfile->ini_read('option_further_options', '');
		
		// page start
		$this->imp_render_page_start();
		form_tag('imp','imp.php', '', '', 'GET');
		
		// dialog
		$site->skin->submenuStart();
			echo 'Einstellungen f&uuml;r ' . isohtmlspecialchars($this->mix);
		$site->skin->submenuEnd();

		$browsing_list = $this->get_browsing_list($showall);
		
		$site->skin->dialogStart();
			form_hidden('page', 'options');
			form_hidden('mix', $this->mix);
			form_control_start(htmlconstant('_IMP_OVERWRITE'));
				$options = IMP_OVERWRITE_OLDER.'###'.htmlconstant('_IMP_OVERWRITEOLDER').'###'.IMP_OVERWRITE_ALWAYS.'###'.htmlconstant('_IMP_OVERWRITEALWAYS').'###'.IMP_OVERWRITE_NEVER.'###'.htmlconstant('_IMP_OVERWRITENEVER');
				form_control_enum('overwrite', $overwrite, $options, 0, '', 'this.form.submit(); return true;');
				if( $browsing_list['ovw_cnt'] > 0 && $overwrite == IMP_OVERWRITE_ALWAYS ) {
					echo '  <b style="color: #E1001A;">!</b> ' . $browsing_list['ovw_cnt'] . ' Datens&auml;tze werden im Bestand &uuml;berschrieben, obwohl sie dort neuer sind';
				}
			form_control_end();
			form_control_start(htmlconstant('_IMP_DELETE'));	
				$options = IMP_DELETE_DELETED.'###'.htmlconstant('_IMP_DELETEDELETED').'###'.IMP_DELETE_NEVER.'###'.htmlconstant('_IMP_DELETENEVER');
				form_control_enum('delete', $delete, $options, 0, '', 'this.form.submit(); return true;');
				if( $browsing_list['del_cnt'] > 0 && $delete == IMP_DELETE_DELETED ) {
					echo '  <b style="color: #E1001A;">!</b> ' . $browsing_list['del_cnt'] . ' Datens&auml;tze werden im Bestand gel&ouml;scht';
				}
			form_control_end();
			form_control_start(htmlconstant('_IMP_FURTHEROPTIONS'));	
				form_control_text('further_options', $further_options, 60 /*width*/);
				echo '<br />z.B. <i>kurse.stichwort=protect; anbieter.stichwort=protect;</i> um das &Uuml;berschreiben eigener Stichw&ouml;rter zu verhindern';
			form_control_end();
			
			
			$import_end_time = $this->mixfile->ini_read('import_end_time', '');
			if( $import_end_time != '' ) {
				form_control_start('Diese Datei wurde bereits Importiert');
					echo sql_date_to_human($import_end_time, 'datetime');
				form_control_end();
			}
		$site->skin->DialogEnd();
		
		$site->skin->WorkspaceStart();
		
			echo '<p><span class="dllcontinue">Vorschau:</span></p>'; 
			
		$site->skin->WorkspaceEnd();

		// paging
		$site->skin->mainmenuStart();
			$record_cnt = sizeof((array) $browsing_list['possible_changes']);
			$baseurl = "imp.php?page=options&mix=".urlencode($this->mix)."&offset=";
			echo page_sel($baseurl, IMP_ROWS_PER_PAGE, $offset, $record_cnt, 1);
		$site->skin->mainmenuEnd();
		
		// list of records
		$site->skin->tableStart();
			echo	'<colgroup>'
				.		'<col style="width:40%;" />'
				.		'<col style="width:8%;" />'
				.		'<col style="width:4%;" />'
				.		'<col style="width:8%;" />'
				.		'<col style="width:40%;" />'
				.	"</colgroup>";
			$site->skin->headStart();
				$site->skin->cellStart();
					echo 'Datensatz in <b>Mix-Datei</b>';
				$site->skin->cellEnd();
				$site->skin->cellStart();
					echo htmlconstant('_OVERVIEW_MODIFIED'); //. ' ' . $site->skin->ti_sortdesc; -- die sortierung besser nicht anzeigen, da uneindeutig - es wird primaer nach Tabellen sortiert, dann nach Aenderungsdatum, hinzu kommen die zu loeschenden Datensaetze
				$site->skin->cellEnd();
				$site->skin->cellStart('style="text-align: center;"');
					echo 'Aktion';
				$site->skin->cellEnd();
				$site->skin->cellStart();
					echo htmlconstant('_OVERVIEW_MODIFIED');
				$site->skin->cellEnd();
				$site->skin->cellStart();
					echo 'Datensatz im <b>Bestand</b>';
				$site->skin->cellEnd();
			$site->skin->headEnd();		
			

			// go through all records in file
			$this->mixfile->create_db_object_to_use(); // this will init sqliteDb2
			$sqliteSummarizer = new G_SUMMARIZER_CLASS($this->mixfile->sqliteDb2);
			$records_rendered = 0;
			
			$gSessionUserID = isset($_SESSION['g_session_userid']) ? $_SESSION['g_session_userid'] : null;
			$sqlite_db_filename = 'imp-'.$gSessionUserID.'-'.$this->mix;
			for( $i = $offset; $i < sizeof((array) $browsing_list['possible_changes']); $i++ )
			{
				$record = $browsing_list['possible_changes'][$i];
				$site->skin->rowStart();

					// left ///////////////////////////////////////////////
					$site->skin->cellStart();
						$currTableDef = Table_Find_Def($record['table'], 0);
						$prefix = ($currTableDef? $currTableDef->descr : $record['table']) .': ';
						if( isset($record['src_date_modified']) ) {
							echo $prefix . isohtmlspecialchars($sqliteSummarizer->get_summary($record['table'], $record['id'], ' / '));
							echo '<a href="edit.php?db='.urlencode($sqlite_db_filename).'&amp;table='.$record['table'].'&amp;id='.$record['id'].'">&nbsp;&#8599;</a>'; // target and title set via jQuery
						}
						else {
							echo '<span style="color: #ccc;">' . $prefix . 'nicht vorhanden' . '</span>';
						}
					$site->skin->cellEnd();
					$site->skin->cellStart('nowrap');
						if( isset($record['src_date_modified']) ) {
							echo sql_date_to_human($record['src_date_modified'], 'datetime');
						}
						else {
							echo '<span style="color: #ccc;">' . htmlconstant('_NA') . '</span>';
						}
					$site->skin->cellEnd();

					// action /////////////////////////////////////////////
					$site->skin->cellStart('style="text-align: center;"');
						$arr = '';
						$disabled = false;
						$date_alert = false;
						if( isset($record['dest_date_modified']) ) {
							if( !isset($record['src_date_modified']) ) {
							    $arr = isset($this->arrow_delete) ? $this->arrow_delete : null;
								if( $delete == IMP_DELETE_NEVER ) {
									$disabled = true;
								}
							}
							else if( $record['dest_date_modified'] == $record['src_date_modified'] ) {
							    $arr = isset($this->arrow_equal) ? $this->arrow_equal : null;
							}
							else if( $record['dest_date_modified'] > $record['src_date_modified'] ) {
							    $arr = isset($this->arrow_red) ? $this->arrow_red : null;
								$date_alert = true;
								if( $overwrite == IMP_OVERWRITE_NEVER || $overwrite == IMP_OVERWRITE_OLDER ) {
									$disabled = true;
								}
							}
							else {
							    $arr = isset($this->arrow_green) ? $this->arrow_green : null;
								if( $overwrite == IMP_OVERWRITE_NEVER )
									$disabled = true;
							}
						}
						else {
						    $arr = isset($this->arrow_green) ? $this->arrow_green : null;
						}
						
						echo sprintf($arr, $record['id'], $disabled? '-dis' : '');
					$site->skin->cellEnd();				

					// right //////////////////////////////////////////////
					$site->skin->cellStart('nowrap');
						echo isset($record['dest_date_modified'])? sql_date_to_human($record['dest_date_modified'], 'datetime') : '&nbsp;';
						if( $date_alert ) echo ' <b style="color: #E1001A;" title="Achtung: Der Datensatz im Bestand ist neuer als der zu importierende!">!</b>';
					$site->skin->cellEnd();
					$site->skin->cellStart();
						if( isset($record['dest_date_modified']) ) {
							echo $currTableDef? isohtmlspecialchars($currTableDef->get_summary($record['id'], ' / ')) : $record['id'];
							echo '<a href="edit.php?table='.$record['table'].'&amp;id='.$record['id'].'">&nbsp;&#8599;</a>'; // target and title set via jQuery
						}
						else {
							echo '&nbsp;';
						}
					$site->skin->cellEnd();
					
				$site->skin->rowEnd();

				$records_rendered++;
				if( $records_rendered == IMP_ROWS_PER_PAGE )
					{ break; }
			}
			
			$showurl = "imp.php?page=options&mix=".urlencode($this->mix)."&offset=0&showall=";
			if( $browsing_list['equal_records'] > 0 )
			{
				$site->skin->rowStart();
					$site->skin->cellStart();
						echo htmlconstant('_IMP_NEQUALERCORDS', $browsing_list['equal_records']);
						echo ' <a href="'.$showurl.'1">[anzeigen...]</a>';
					$site->skin->cellEnd();
					$site->skin->cellStart('nowrap');
						echo sql_date_to_human($browsing_list['equal_date'], 'datetime') ;
					$site->skin->cellEnd();
					$site->skin->cellStart('style="text-align: center;"');
						echo sprintf($this->arrow_equal, 0, '');
					$site->skin->cellEnd();
					$site->skin->cellStart('nowrap');
						echo sql_date_to_human($browsing_list['equal_date'], 'datetime') ;
					$site->skin->cellEnd();
					$site->skin->cellStart();
						echo htmlconstant('_IMP_NEQUALERCORDS', $browsing_list['equal_records']);
					$site->skin->cellEnd();
				$site->skin->rowEnd();
			}
			if( $showall )
			{
				$site->skin->rowStart();
					$site->skin->cellStart('colspan="5"');
						echo '<a href="'.$showurl.'0">[identische Datens&auml;tze ausblenden...]</a>';
					$site->skin->cellEnd();
				$site->skin->rowEnd();
			}
	
		$site->skin->tableEnd();

		// page end
		$site->skin->buttonsStart();
			form_button('doimport', 'Datens&auml;tze importieren', "return confirm('M&ouml;chten Sie den Import starten und dabei die markierten Datens&auml;tze im Bestand &uuml;berschreiben und/oder l&ouml;schen?');");
			form_button('close', htmlconstant('_CLOSE')); // as we save the settings, we call the button "close" instead of "cancel"
		$site->skin->buttonsBreak();
		    $title = isset($this->recreated) && $this->recreated ? 'Vorschau aktualisiert' : 'Vorschau aktualisieren';
			echo '<a href="imp.php?page=options&amp;mix='.urlencode($this->mix).'&amp;update">'.$title.'</a> | ';
			echo "<a href=\"log.php\" target=\"_blank\" rel=\"noopener noreferrer\">" . htmlconstant('_LOG') . '</a>';
		$site->skin->buttonsEnd();

		echo '</form>';		
		$this->imp_render_page_end();
	}

	function handle_request()
	{
		// open mixfile
		$this->mixfile = new IMP_MIXFILE_CLASS;
		
		$gSessionUserID = isset($_SESSION['g_session_userid']) ? $_SESSION['g_session_userid'] : null;
		$gTmpDir = isset($GLOBALS['g_temp_dir']) ? $GLOBALS['g_temp_dir'] : '';
		if( !$this->mixfile->open($gTmpDir.'/imp-'.$gSessionUserID.'-'.$this->mix) )
		{
			$GLOBALS['site']->msgAdd($this->mixfile->error_str, 'e');
			redirect('imp.php?page=files');
		}
		$this->mixfile->prepare_for_browse();
		
		// save settings to Mix-File
		if( isset($_REQUEST['offset']) )    		{ $_SESSION['mixfile_offsets'][$this->mix] = (isset($_REQUEST['offset']) ? intval($_REQUEST['offset']) : 0); } /*using ini_write() is too slow for paging ... and the offset is not that important */
		if( isset($_REQUEST['showall']) )  			{ $_SESSION['mixfile_showall'][$this->mix] = (isset($_REQUEST['showall']) ? intval($_REQUEST['showall']) : null); }
		if( isset($_REQUEST['overwrite']) ) 		{ $this->mixfile->ini_write('option_overwrite', (isset($_REQUEST['overwrite']) ? intval($_REQUEST['overwrite']) : null) ); }
		if( isset($_REQUEST['delete']) )   		 	{ $this->mixfile->ini_write('option_delete', (isset($_REQUEST['delete']) ? intval($_REQUEST['delete']) : null) ); }
		if( isset($_REQUEST['further_options']) )   { $this->mixfile->ini_write('option_further_options', (isset($_REQUEST['further_options']) ? $_REQUEST['further_options'] : null)); }
		
		// set forward or render
		$fwd = '';
		if( isset($_REQUEST['doimport']) ) {
			$fwd = 'imp.php?page=import&mix=' . urlencode($this->mix)
			    .	"&overwrite="  . (isset($_REQUEST['overwrite']) ? intval($_REQUEST['overwrite']) : null)
			    .	"&delete="     . (isset($_REQUEST['delete']) ? intval($_REQUEST['delete']) : null)
			    .	"&further_options=" . (isset($_REQUEST['further_options']) ? urlencode($_REQUEST['further_options']) : null);
		}
		else if( isset($_REQUEST['close']) ) {
			$fwd = 'imp.php?page=files';
		}
		else {
			$this->render();
		}
		
		// close forward
		$this->mixfile->close();
		
		// do forward, if needed (must be done after closing the mixfile)
		if( $fwd ) {	
			redirect($fwd);
		}
	}
};