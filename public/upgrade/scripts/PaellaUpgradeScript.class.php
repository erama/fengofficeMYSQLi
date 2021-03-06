<?php

/**
 * Paella upgrade script will upgrade FengOffice 3.4.4.64 to FengOffice 3.5.0.9
 *
 * @package ScriptUpgrader.scripts
 * @version 1.0
 */
class PaellaUpgradeScript extends ScriptUpgraderScript {

	/**
	 * Array of files and folders that need to be writable
	 *
	 * @var array
	 */
	private $check_is_writable = array(
		'/config/config.php',
		'/config',
		'/cache',
		'/tmp',
		'/upload'
	 ); // array

	 /**
	 * Array of extensions taht need to be loaded
	 *
	 * @var array
	 */
	private $check_extensions = array(
		'mysqli', 'gd', 'simplexml'
	); // array

	 /**
	 * Construct the PaellaUpgradeScript
	 *
	 * @param Output $output
	 * @return PaellaUpgradeScript
	 */
	function __construct(Output $output) {
		parent::__construct($output);
		$this->setVersionFrom('3.4.4.52');
		$this->setVersionTo('3.5.0.9');
	} // __construct

	function getCheckIsWritable() {
		return $this->check_is_writable;
	}

	function getCheckExtensions() {
		return $this->check_extensions;
	}
	
	/**
	 * Execute the script
	 *
	 * @param void
	 * @return boolean
	 */
	function execute() {
		if (!@mysqli_ping($this->database_connection)) {
			if ($dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASS)) {
				if (mysqli_select_db(DB_NAME, $dbc)) {
					$this->printMessage('Upgrade script has connected to the database.');
				} else {
					$this->printMessage('Failed to select database ' . DB_NAME);
					return false;
				}
				$this->setDatabaseConnection($dbc);
			} else {
				$this->printMessage('Failed to connect to database');
				return false;
			}
		}
		
		// ---------------------------------------------------
		//  Check MySQL version
		// ---------------------------------------------------

		$mysqli_version = mysqli_get_server_info($this->database_connection);
		if($mysqli_version && version_compare($mysqli_version, '4.1', '>=')) {
			$constants['DB_CHARSET'] = 'utf8';
			@mysqli_query("SET NAMES 'utf8'", $this->database_connection);
			tpl_assign('default_collation', $default_collation = 'collate utf8_unicode_ci');
			tpl_assign('default_charset', $default_charset = 'DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci');
		} else {
			tpl_assign('default_collation', $default_collation = '');
			tpl_assign('default_charset', $default_charset = '');
		} // if

		$installed_version = installed_version();
		$t_prefix = TABLE_PREFIX;
		$additional_upgrade_steps = array();
						
		// RUN QUERIES
		$total_queries = 0;
		$executed_queries = 0;

		$upgrade_script = "";
		
		$v_from = array_var($_POST, 'form_data');
		$original_version_from = array_var($v_from, 'upgrade_from', $installed_version);
		
		
		
		
		// Set upgrade queries	
		if (version_compare($installed_version, '3.5-alpha') < 0) {
			
			$upgrade_script .= "
				INSERT INTO `".$t_prefix."file_types` (`extension`, `icon`, `is_searchable`, `is_image`) VALUES ('ics', 'ics.png', '0', '0')
				ON DUPLICATE KEY UPDATE `extension`=`extension`;
			";
			
			$upgrade_script .= "
				ALTER TABLE `".$t_prefix."tab_panels` ADD COLUMN `url_params` varchar(255) COLLATE 'utf8_unicode_ci' NOT NULL DEFAULT '';
			";
		}
		
		if (version_compare($installed_version, '3.5-beta') < 0) {
			$upgrade_script .= "
				insert into ".$t_prefix."object_members
					select t.object_id, om.member_id, om.is_optimization from ".$t_prefix."timeslots t
					inner join ".$t_prefix."object_members om on om.object_id=t.rel_object_id
					where t.rel_object_id>0
				on duplicate key update ".$t_prefix."object_members.object_id=".$t_prefix."object_members.object_id;
			";
			
			$upgrade_script .= "
				ALTER TABLE `".$t_prefix."config_options` ADD COLUMN `options` varchar(255) COLLATE 'utf8_unicode_ci' DEFAULT '';
			";
		}
		
		if (version_compare($installed_version, '3.5-beta2') < 0) {
			$upgrade_script .= "
				ALTER TABLE `".$t_prefix."timeslots` ADD COLUMN `worked_time` int(10) unsigned NOT NULL DEFAULT 0;
			";
			
			$upgrade_script .= "
				update ".$t_prefix."timeslots set worked_time=GREATEST(TIMESTAMPDIFF(MINUTE,start_time,end_time),0) - (subtract/60);
			";
		}
		
		
		if (version_compare($installed_version, '3.5.0.3') < 0) {
			$upgrade_script .= "
				INSERT INTO `".$t_prefix."contact_config_options` (`category_name`, `name`, `default_value`, `config_handler_class`, `is_system`, `option_order`, `dev_comment`) VALUES
				('task panel', 'tasksShowAssignedToName', '0', 'BoolConfigHandler', 0, 0, '');
			";
		}
		
		
		if (version_compare($installed_version, '3.5.0.7') < 0) {
			$upgrade_script .= "
				INSERT INTO `".$t_prefix."contact_config_categories` (`name`, `is_system`, `type`, `category_order`) VALUES 
					('contact panel', 0, 0, 8)
				ON DUPLICATE KEY UPDATE name=name;
			";
			$upgrade_script .= "
				INSERT INTO `".$t_prefix."contact_config_options` (`category_name`, `name`, `default_value`, `config_handler_class`, `is_system`, `option_order`, `dev_comment`) VALUES 
				 ('contact panel', 'show_inactive_users_in_list', '1', 'BoolConfigHandler', '0', '0', NULL)
				ON DUPLICATE KEY UPDATE name=name;
			";
		}

		

		// Execute all queries
		if(!$this->executeMultipleQueries($upgrade_script, $total_queries, $executed_queries, $this->database_connection)) {
			$this->printMessage('Failed to execute DB schema transformations. MySQL said: ' . mysqli_error(), true);
			return false;
		}
		$this->printMessage("Database schema transformations executed (total queries: $total_queries)");
		
		$this->printMessage('Feng Office has been upgraded. You are now running Feng Office '.$this->getVersionTo().' Enjoy!');

		tpl_assign('additional_steps', $additional_upgrade_steps);

	} // execute
	
} // PaellaUpgradeScript
