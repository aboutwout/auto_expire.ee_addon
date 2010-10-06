<?php

/**
* @package ExpressionEngine
* @author Wouter Vervloet
* @copyright  Copyright (c) 2010, Baseworks
* @license    http://creativecommons.org/licenses/by-sa/3.0/
* 
* This work is licensed under the Creative Commons Attribution-Share Alike 3.0 Unported.
* To view a copy of this license, visit http://creativecommons.org/licenses/by-sa/3.0/
* or send a letter to Creative Commons, 171 Second Street, Suite 300,
* San Francisco, California, 94105, USA.
* 
*/

if ( ! defined('EXT')) { exit('Invalid file request'); }

class Auto_expire
{
  public $settings            = array();
  
  public $name                = 'Auto Expire';
  public $version             = 1.3;
  public $description         = "Automatically set an entry's expiration date.";
  public $settings_exist      = 'y';
  public $docs_url            = '';
  
  private $_time_diff         = false;
  private $_time_unit         = false;
  private $status             = false;
  
  public $time_units          = array(
                                  1 => 'minutes',
                                  2 => 'hours',
                                  3 => 'days',
                                  4 => 'weeks',
                                  5 => 'months',
                                  6 => 'years'
                                );
			
	// -------------------------------
	// Constructor
	// -------------------------------
	function Auto_expire($settings='')
	{
	  $this->__construct($settings);
	}
	
	function __construct($settings='')
	{	  
		$this->settings = $settings;	
	}
	// END Auto_expire_ext
	
	
  /**
  * Set the expiration date if needed
  */
  function set_expiration_date($weblog_id=0, $autosave=false)
  {
    
    global $IN;
    
    $weblog_id = $IN->GBL('weblog_id', 'POST');
    $expiration_date_in = $IN->GBL('expiration_date', 'POST');
    
    if(!$weblog_id || $autosave === true) return;
        
    if ($this->_auto_expire_weblog($weblog_id) && !$expiration_date_in) {

      $entry_date = new DateTime($IN->GBL('entry_date', 'POST'));
      $expiration_date = clone $entry_date;
      
      $expiration_date->modify('+'.$this->_time_diff.' '.$this->time_units[$this->_time_unit]);
          
      $_POST['expiration_date'] = $expiration_date->format('Y-m-d H:i');
            
    }

  }
  // END set_expiration_date
  
  /**
  * Set the expiration date if needed
  */
  function set_expiration_date_saef()
  {
    global $IN;
    
    $weblog_id = $IN->GBL('weblog_id', 'POST');
    $expiration_date_in = $IN->GBL('expiration_date', 'POST');
        
    // weblog has auto expire settings set and has no expiration date set
    if ($this->_auto_expire_weblog($weblog_id) && !$expiration_date_in) {

      $entry_date = new DateTime($IN->GBL('entry_date', 'POST'));
      $expiration_date = clone $entry_date;
      
      $expiration_date->modify('+'.$this->_time_diff.' '.$this->time_units[$this->_time_unit]);
      
      $_POST['expiration_date'] = $expiration_date->format('Y-m-d H:i');
    }

  }
  // END set_expiration_date
  
  
  /**
  * Modifies control panel html by adding the Auto Expire
  * settings panel to Admin > Weblog Administration > Weblog Management > Edit Weblog
  */
  function settings_form($current)
  {
    global $IN, $DB, $DSP, $LANG;
    
    if($IN->GBL('time_diff', 'POST') && $IN->GBL('time_unit', 'POST')) {
      $this->save_settings_form();
    }

    $DSP->crumbline = TRUE;
    
    $DSP->title  = $LANG->line('auto_expire_extension_name');
    $DSP->crumb  = $DSP->anchor(BASE.AMP.'C=admin'.AMP.'area=utilities', $LANG->line('utilities')).
      $DSP->crumb_item($DSP->anchor(BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=extensions_manager', $LANG->line('extensions_manager')));
    $DSP->crumb .= $DSP->crumb_item($LANG->line('auto_expire_extension_name'));
    
    $DSP->right_crumb($LANG->line('disable_extension'), BASE.AMP.'C=admin'.AMP.'M=utilities'.AMP.'P=toggle_extension_confirm'.AMP.'which=disable'.AMP.'name=auto_expire');

		$DSP->body .= $DSP->heading($LANG->line('auto_expire_extension_name'));

    
    $weblog_query = $DB->query("SELECT weblog_id, blog_title FROM exp_weblogs");

    $weblogs = array();
    
    foreach($weblog_query->result as $row) {
      
      $statuses = $DB->query("SELECT status_id as id, status as name FROM exp_statuses s NATURAL JOIN exp_status_groups sg NATURAL JOIN exp_weblogs c WHERE c.weblog_id = ".$row['weblog_id']);
            
      $expire = $this->_fetch_preferences($row['weblog_id']);
      
      $weblogs[] = array(
        'id' => $row['weblog_id'],
        'title' => $row['blog_title'],
        'time_diff' => $expire['time_diff'],
        'time_unit' => $expire['time_unit'],
        'status' => $expire['status'],
        'statuses' => $statuses
      );
    }
    
    $vars = array(
      'time_units' => $this->time_units,
      'weblogs' => $weblogs,
      'settings_saved' => $_SERVER['REQUEST_METHOD']=='POST'
    );
    
    $DSP->body .= $DSP->view(PATH_EXT.'auto_expire/views/settings_form.php', $vars, TRUE);
   
  }
  // END settings_form

  /**
  * Check if there are any expired entries and change the status if needed
  */
  function change_status_expired_entries()
  {
    global $DB;
    
    $query = $DB->query("SELECT ae.weblog_id, ae.status, s.status as status_name FROM exp_auto_expire ae LEFT JOIN exp_statuses s ON ae.status = s.status_id WHERE ae.status != 0");


    if($query->num_rows == 0) return false;
    
    foreach($query->result as $row) {      
           
      $data = array(
        'status' => $row['status_name']
      );
      
      $sql = $DB->update_string('exp_weblog_titles', $data, "weblog_id = '".$row['weblog_id']."' AND status != '".$row['status_name']."' AND expiration_date != '0' AND expiration_date <  ".time());
            
      $DB->query($sql);
      
    }            
  }

  /**
  * Saves the auto expire settings.
  */
  function save_settings_form()
  {
    global $IN, $DB;
    
    $time_diffs = $IN->GBL('time_diff', 'POST');
    $time_units = $IN->GBL('time_unit', 'POST');    
    $statuses = $IN->GBL('status', 'POST');    

    foreach($time_diffs as $weblog_id => $value)
    {
      // Default values
      $data = array(
        'weblog_id' => $weblog_id,
        'time_diff' => 0,
        'time_unit' => 0,
        'status' => 0
      );
       
      // Values have been set
      if(is_numeric($time_diffs[$weblog_id]) && $time_diffs[$weblog_id] && is_numeric($time_units[$weblog_id]) && $time_units[$weblog_id])
      {
        $data['time_diff'] = $time_diffs[$weblog_id];
        $data['time_unit'] = $time_units[$weblog_id];
        $data['status'] = $statuses[$weblog_id];
      }
      
      $DB->query("INSERT INTO exp_auto_expire (weblog_id, time_diff, time_unit, status) VALUES (".$weblog_id.", ".$data['time_diff'].", ".$data['time_unit'].", ".$data['status'].") ON DUPLICATE KEY UPDATE weblog_id=VALUES(weblog_id), time_diff=VALUES(time_diff), time_unit=VALUES(time_unit), status=VALUES(status)");
          
    }
    
    
  }
  // END save_settings_form
  
  
  function _fetch_preferences($weblog_id)
  {
    
    global $DB;
    
    if( !$weblog_id ) return false;
    
    $return = array(
      'time_diff' => 0,
      'time_unit' => 0,
      'status' => 0
    );
    
    $query = $DB->query("SELECT time_diff, time_unit, status FROM exp_auto_expire WHERE weblog_id = $weblog_id");
    
    if($query->num_rows > 0) {
      /**
      * @todo
      */
      $return['time_diff'] = $query->row['time_diff'];
      $return['time_unit']  = $query->row['time_unit'];
      $return['status']  = $query->row['status'];
      
    }
    
    return $return;
    
  }
  // END _fetch_preferences
  
  
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
    
    $query = $DB->query("SELECT weblog_id, time_diff, time_unit, status FROM exp_auto_expire WHERE weblog_id = {$weblog_id}");
    
    // If no settings have been set for this weblog, unset variables and return false
    if($query->num_rows === 0) {
      $this->_time_diff = false;
      $this->_time_unit = false;

      return false;
    }

    /**
    * @todo
    */        
    $this->_time_diff = $query->row['time_diff'];
    $this->_time_unit = $query->row['time_unit'];    
    $this->_status = $query->row['status'];    
    
    return ! $this->_time_diff || ! $this->_time_unit ? false : true;
    
  }
  // END	_auto_expire_weblog	
	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
	  
	  global $DB;

    $sql = array();

    /**
    * @todo
    */

    // hooks array
    $hooks = array(
      'submit_new_entry_start' => 'set_expiration_date',
      'weblog_standalone_insert_entry' => 'set_expiration_date',
      'sessions_end' => 'change_status_expired_entries'
    );

    // insert hooks and methods
    foreach ($hooks AS $hook => $method)
    {
      // data to insert
      $data = array(
        'class'		=> get_class($this),
        'method'	=> $method,
        'hook'		=> $hook,
        'priority'	=> 1,
        'version'	=> $this->version,
        'enabled'	=> 'y',
        'settings'	=> ''
      );

      // insert in database
      $sql[] = $DB->insert_string('exp_extensions', $data);
    }

    // add extension table
    $sql[] = 'DROP TABLE IF EXISTS `exp_auto_expire`';
    $sql[] = "CREATE TABLE `exp_auto_expire` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `weblog_id` INT NOT NULL UNIQUE KEY, `time_diff` INT NOT NULL, `time_unit` INT NOT NULL, `status` INT NOT NULL)";

    // run all sql queries
    foreach ($sql as $query) {
      $DB->query($query);
    }

    return true;
	}
	// END activate_extension
	 
	 
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
	  global $DB;
		
    if ($current == '' OR $current == $this->version)
    {
      return FALSE;
    }
    
    if($current < $this->version) { }

    // init data array
    $data = array();

    // Add version to data array
    $data['version'] = $this->version;    

    // Update records using data array
    $sql = $DB->update_string('exp_extensions', $data, "class = '".get_class($this)."'");
    $DB->query($sql);
  }
  // END update_extension

	// --------------------------------
	//  Disable Extension
	// --------------------------------
	function disable_extension()
	{	
	  global $DB;
	
    // Delete records
    $DB->query("DELETE FROM exp_extensions WHERE class = '".get_class($this)."'");
  }
  // END disable_extension

	 
}
// END CLASS
?>