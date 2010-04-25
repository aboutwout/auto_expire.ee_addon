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
  public $version             = '0.1';
  public $description         = "Automatically set an entry's expiration date.";
  public $settings_exist      = 'n';
  public $docs_url            = '';
  
	/**
	* ...
	*/
  function submit_new_entry_absolute_end($entry_id, $data) {
    global $DB;
    
    $this->entry_id = $entry_id;
    $this->data = $data;
    
    echo "<pre>";
    print_r($data);
    echo "</pre>";
    
  }
	
  /**
  * System Functions
  */

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
  * Save extension settings
  */
  function save_settings()
  {
    global $DB, $DSP, $LANG, $IN, $PREFS;   
    
  }


  /**
  * Activate Extension
  * @return bool Has the extension been activated successfully?
  */
  function activate_extension() {
    global $DB;

		// hooks array
		$hooks = array(
      'submit_new_entry_absolute_end' => 'submit_new_entry_absolute_end'
    );

		foreach ($hooks as $hook => $method) {
			$sql[] = $DB->insert_string( 'exp_extensions',
				array(
					'extension_id'	=> '',
					'class'			=> get_class($this),
					'method'		=> $method,
					'hook'			=> $hook,
					'settings'		=> '',
					'priority'		=> 9,
					'version'		=> $this->version,
					'enabled'		=> 'y'
				)
			);
		}

    // add extension table
    $sql[] = 'DROP TABLE IF EXISTS `exp_auto_expire_settings`';
		$sql[] = "CREATE TABLE `exp_auto_expire_settings` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `weblog_id` INT NOT NULL, `value` INT NOT NULL, `period` VARCHAR() NOT NULL)";

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
    $DB->query("UPDATE exp_extensions SET version = '".$DB->escape_str($this->version)."' WHERE class = 'Twagger'");
  }
  // END

  /**
  * Disable Extension
  */
  function disable_extension() {
    global $DB;
    $sql[] = 'DROP TABLE IF EXISTS `exp_twagger_settings`';		
    $sql[] = 'DROP TABLE IF EXISTS `exp_twagger_tags`';		
    $sql[] = "DELETE FROM exp_extensions WHERE class = 'Twagger'";		

    foreach($sql as $query) {
      $DB->query($query);
    }

  }
  // END

}
// END CLASS