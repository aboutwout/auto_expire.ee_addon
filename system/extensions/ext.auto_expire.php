<?php if (!defined('EXT')) exit('Invalid file request');

/**
*
* @package ExpressionEngine
* @author Wouter Vervloet
* @copyright	Copyright (c) 2010, Baseworks
* @license		http://creativecommons.org/licenses/by-sa/3.0/
* 
* This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
* To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
* or send a letter to Creative Commons, 171 Second Street, Suite 300,
* San Francisco, California, 94105, USA.
* 
*/
class Auto_expire
{
  public $settings            = array();
  
  public $name                = 'Auto Expire';
  public $version             = '0.2';
  public $description         = "Automatically set an entry's expiration date.";
  public $settings_exist      = 'n';
  public $docs_url            = '';
  
  private $_expire_value      = false;
  private $_expire_period     = false;
  
  /**
  * Constructor - Extensions use this for settings
  * @param array $settings settings for this extension
  */
  function Auto_expire($settings='')
  { 
    $this->__construct($settings);
  }
  
  function __construct($settings='')
  {
    $this->settings = $settings;
  }
  // END


  /**
  * Set the expiration date if needed
  */
  function set_expiration_date()
  {
		global $EE, $IN;
		
		if ($EE === NULL) return;
		
		// weblog has auto expire settings set and has no expiration date set
		if ($exp_time = $this->_auto_expire_weblog($_POST['weblog_id']) && empty($_POST['expiration_date'])) {
      $entry_date = new DateTime($IN->GBL('entry_date'));
      $expiration_date = clone $entry_date;
      
		}

		return;
    
  }
  // END
  
  
  /**
  * Modifies control panel html by adding the required category
	* settings panel to Admin > Weblog Administration > Weblog Management > Edit Weblog
  */
  function edit_weblog_prefs($out)
  {
		global $DB, $EXT, $IN, $DSP, $LANG;

		// check if someone else uses this
		if ($EXT->last_call !== false)
		{
			$out = $EXT->last_call;
		}

		//	=============================================
		//	Only Alter Weblog Preferences (on update too!)
		//	=============================================
		if($IN->GBL('M') != 'blog_admin' || ($IN->GBL('P') != 'blog_prefs' && $IN->GBL('P') !=  'update_preferences'))
		{
			return $out;
		}

		// now we can fetch the language file
		$LANG->fetch_language_file('auto_expire_ext');

		//	=============================================
		//	Set preferences from DB based on weblog id
		//	=============================================
		$weblog_id = isset($_POST['weblog_id']) ? $_POST['weblog_id'] : $IN->GBL('weblog_id');
		if (!is_numeric($weblog_id))
		{
			$weblog_id = false;
		}
		
///		$this->_set_preferences($weblog_id);

		//	=============================================
		//	Find Table
		//	=============================================
		preg_match('/id=[\'"]posting_on[\'"].*?<\/table>/si', $out, $table);

    $periods = array('minutes', 'hours', 'days', 'weeks', 'months', 'years');

    $period_select = $DSP->input_select_header('auto_expire_period', null, 1, '45%');
    $period_select .= $DSP->input_select_option('false', $LANG->line('select_period'));
    
    foreach( $periods as $period ) {
      $period_select .= $DSP->input_select_option($period, $LANG->line($period), $this->_expire_value == $period ? 'y' : null);
    }
    
    $period_select .= $DSP->input_select_footer();

		//	=============================================
		//	Create Fields
		//	=============================================
		$r = $DSP->br();

		$r .= $DSP->table('tableBorder', '0', '', '100%');
		$r .= $DSP->tr();
		$r .= '<td class="tableHeadingAlt" colspan="2" align="left">'.NBS.$LANG->line('heading_preferences').$DSP->td_c();
		$r .= $DSP->tr_c();

		$r .= $DSP->tr();
		$r .= $DSP->table_qcell('tableCellOne', $DSP->qspan('defaultBold', $LANG->line('pref_auto_expire')), '50%');

		$r .= $DSP->table_qcell('tableCellOne', $DSP->input_text('auto_expire_value', $this->_expire_value, '', '', '', '25%') . $period_select , '50%');
		$r .= $DSP->tr_c();

		$r.= $DSP->table_c();

		//	=============================================
		//	Add Fields
		//	=============================================
		$out = @str_replace($table[0], $table[0].$r, $out);
		
		return $out;
  }
  // END
  
  
  /**
  * Saves the auto expire settings.
  */
  function save_weblog_settings()
  {
    global $DB;
    
      
    
  }
  // END
  
	/**
	 * Checks whether the expiration date should be set for this weblog
	 *
	 * @param   string $weblog_id A weblog id.
	 * @return  boolean True if a weblog requires at least one category, false else.
	 */  
  function _auto_expire_weblog($weblog_id)
  {
    global $DB;
    
    if( ! $weblog_id ) return false;
    
		$query = $DB->query("SELECT weblog_id, value, period FROM exp_auto_expire_settings WHERE weblog_id = {$weblog_id}");
		
		// If no settings have been set for this weblog, unset variables and return false
		if($query->num_rows === 0) {
		  
      $this->_expire_value = false;
      $this->_expire_period = false;

      return false;
		}
		
    $this->_expire_value = $query->row['value'];
    $this->_expire_period = $query->row['period'];    
    
    return true;
    
  }
  // END
  
  
  /**
  * Activate Extension
  * @return bool Has the extension been activated successfully?
  */
  function activate_extension() {
    global $DB;

		// hooks array
		$hooks = array(
		  'sessions_start' => 'save_weblog_settings',
			'submit_new_entry_start' => 'set_expiration_date',
			'show_full_control_panel_end'	=> 'edit_weblog_prefs'
    );

    foreach ($hooks as $hook => $method) {
      $sql[] = $DB->insert_string( 'exp_extensions',
        array(
        	'extension_id'	=> '',
        	'class'			=> get_class($this),
        	'method'		=> $method,
        	'hook'			=> $hook,
        	'settings'		=> '',
        	'priority'		=> 15,
        	'version'		=> $this->version,
        	'enabled'		=> 'y'
        )
      );
    }

    // add extension table
    $sql[] = 'DROP TABLE IF EXISTS `exp_auto_expire`';
    $sql[] = "CREATE TABLE `exp_auto_expire` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `weblog_id` INT NOT NULL, `time_diff` INT NOT NULL, `time_unit` INT NOT NULL, `enabled` TINYINT(1) NOT NULL)";

    // run all sql queries
    foreach ($sql as $query) {
      $DB->query($query);
    }

    return true;
  }
  // END


  /**
  * Update Extension
  * @param string $current Current version
  */
  function update_extension($current='') {
    global $DB;

    if ($current == '' OR $current == $this->version) return FALSE;
    if ($current < '0.1') { }// Update to next version 0.5
    $DB->query("UPDATE exp_extensions SET version = '".$DB->escape_str($this->version)."' WHERE class = '".get_class($this)."'");
  }
  // END


  /**
  * Disable Extension
  */
  function disable_extension() {
    global $DB;
    $sql[] = 'DROP TABLE IF EXISTS `exp_auto_expire`';			
    $sql[] = "DELETE FROM exp_extensions WHERE class = '".get_class($this)."'";

    foreach($sql as $query) {
      $DB->query($query);
    }

  }
  // END

}
// END CLASS