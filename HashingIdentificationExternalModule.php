<?php
/// A support module to help create hashing identification values.
/**
 *  HashingIdentificationExternalModule 
 *  - CLASS for Hashing Identification - creates hash value for either International or United States based upon persons name and birthday information.
 *    + key functions
 *  - The project (say, ), has 
 *  
 *  
 *  - WUSM - Washington University School of Medicine. 
 * @author David L. Heskett
 * @version 1.0
 * @date 20180102
 * @copyright &copy; 2018 Washington University, School of Medicine, Institute for Informatics <a href="https://redcap.wustl.edu">redcap.wustl.edu</a>
 * @todo Further documentation done to all the methods and so on should be done sometime.
 */

namespace WashingtonUniversity\HashingIdentificationExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

use Project;
use REDCap;
use HtmlPage;

include_once 'LoggingHandler.php';
include_once 'WebHandler.php';

class HashingIdentificationExternalModule extends AbstractExternalModule
{
	use WebHandler;
	use LoggingHandler;

	private $version;    /**< version of this module */
	private $projectName;
	private $projectId;  /**< holds a project ID */
	private $Project;  /**< holds a Project */
	
	private $isHashType;
	private $projectUsingHashing;
	private $custom_prefix;
	private $firstNameField;
	private $lastNameField;
	private $dateOfBirthField;
	private $yearField;
	private $hashCodeField;
	private $hashCodeData;
	private $locationFlagField;
	private $instrument_to_use;
	private $debug_mode_log;

	public $debugLogFlag;

	CONST MODULE_VERSION = '1.0';

	CONST HASH_TYPE_UNITED_STATES = 'USA';
	CONST HASH_TYPE_INTERNATIONAL = 'INT';
	
	CONST PROJECT_NAME = 'Hashing EM';

	CONST MSG_EXISTING_RECORD = "Participant (recordid dagname) already exists. <br><br>Please contact the Project Administrator.";
	CONST MSG_PROCESS_HASH_REQUEST = 'First name, Last name and Birth date are REQUIRED.';  // processHashRequest
	
	CONST BTN_SAVE_PROCESS_HASH_REQUEST = 'Add Participant';
	CONST BTN_CREATE_PROCESS_HASH_REQUEST = 'Create New Record';
	
	// **********************************************************************	
	// **********************************************************************	
	// **********************************************************************	

	/**
	 * - set up our defaults.
	 */
	function __construct($pid = null)
	{
		parent::__construct();
		
		$this->version = HashingIdentificationExternalModule::MODULE_VERSION;
		$this->projectId = null;

		$this->projectName = self::PROJECT_NAME;
		
		$this->isHashType = null; // HashingIdentificationExternalModule::HASH_TYPE_UNITED_STATES;
		$this->hashCodeField = null;
		$this->hashCodeData = null;
		$this->custom_prefix = '';

		$this->instrument_to_use = null;
		
		// project ID of project 
		if ($pid) {
			$projectId = $pid;
		} else {
			$projectId = (isset($_GET['pid']) ? $_GET['pid'] : 0);
		}
		
		if ($projectId > 0) {
			$this->projectId = $projectId;
			$this->loadProjectSettings($projectId);
		} else {
			$this->projectUsingHashing = 0;
		}
		
		$this->debugLogFlag = ($this->debug_mode_log ? true : false);
	}
	
	/**
	 * view - the front end part, display what we have put together.
	 */
	public function viewHtml($msg = 'view', $flag = '')
	{
		$HtmlPage = new HtmlPage(); 

		// html header
		if ($flag == 'project') {
			$HtmlPage->ProjectHeader();
		} else {   // system
			$HtmlPage->setPageTitle($this->projectName);
			$HtmlPage->PrintHeaderExt();
		}
		
	  echo $msg;
		
		// html footer
		if ($flag == 'project') {
			$HtmlPage->ProjectFooter();
		} else {   // system
			$HtmlPage->PrintFooterExt();
		}
	}
	
	/**
	 * init - initializations here.
	 */
	public function loadProjectSettings($projectId = 0) 
	{
		if ($projectId > 0) {
			$projectUsingHashing = $this->getProjectSetting('project_using_hashing');
			$this->projectUsingHashing = (is_numeric($projectUsingHashing) ? $projectUsingHashing : 0);
			
			$this->custom_prefix           = $this->getProjectSetting('custom_prefix');
			$this->firstNameField           = $this->getProjectSetting('field_first_name');
			$this->lastNameField            = $this->getProjectSetting('field_last_name');
			$this->dateOfBirthField         = $this->getProjectSetting('field_date_of_birth');
			$this->yearField                = $this->getProjectSetting('field_year');
			$this->hashCodeField            = $this->getProjectSetting('hash_code_field');
			$this->locationFlagField        = $this->getProjectSetting('location_flag_field');
			$this->debug_mode_log           = $this->getProjectSetting('debug_mode_log');
			$this->instrument_to_use        = strtolower($this->getProjectSetting('instrument_to_use'));
		}
	}

	/**
	 * getDagId - get DAG ID
	 */
	public function getDagId($projectId, $recordId) 
	{
		$dagId = '';

		// SELECT value AS dagId FROM redcap_data WHERE project_id = 159 AND record = '66' AND field_name = '__GROUPID__';
		//$sql = 'SELECT value AS dagId FROM redcap_data WHERE project_id = ' . $projectId . ' AND record = ' . "'" . $recordId . "'" .' AND field_name = ' . "'" . '__GROUPID__' . "'";
		
		//SELECT D.value AS dagId, G.group_name AS dagName FROM redcap_data AS D JOIN redcap_data_access_groups AS G ON (D.value = G.group_id) WHERE D.project_id = 159 AND D.record = '66' AND D.field_name = '__GROUPID__';
		$sql = 'SELECT D.value AS dagId, G.group_name AS dagName FROM redcap_data AS D JOIN redcap_data_access_groups AS G ON (D.value = G.group_id) WHERE D.project_id = ' . db_escape($projectId) . ' AND D.record = ' . "'" . db_escape($recordId) . "'" .' AND D.field_name = ' . "'" . '__GROUPID__' . "'";

		$result = db_query($sql);
		$details = db_fetch_assoc($result);
		
		if (isset($details['dagId'])) {
			if ($details['dagId'] > 0) {
				$dagId = $details['dagId'];
			}
		}
		if (isset($details['dagName'])) {
			if ($details['dagName'] > '') {
				$dagName = $details['dagName'];
			}
		}
		
		return $dagName;
	}

	/**
	 * setDebugFlag - debug flag on
	 */
	public function setDebugFlag() 
	{
		$this->debugFlag = true;
	}

	/**
	 * clearDebugFlag - debug flag on
	 */
	public function clearDebugFlag() 
	{
		$this->debugFlag = false;
	}

	/**
	 * getVersion - get the version.
	 * @return The version number.
	 */
	public function getVersion()
	{
		return $this->version;
	}
	
	/**
	 * test - do something we can check on.
	 * @return The version number.
	 */
	public function test()
	{
		return $this->version;
	}

	/**
	 * msg - format a message adding in <p>$msg</p>.
	 * @return The message string wrapped in html paragraph.
	 */
	public function msg($msg)
	{
		$str = '';
		$str .= '<p>';
		$str .= js_escape($msg);
		$str .= '</p>';
		
		return $str;
	}

	/**
	 * debugMsg - format a message adding in <p>$msg</p>.
	 */
	public function debugMsg($msg)
	{
		if ($this->debugFlag) {
			echo $msg;
		}
	}

	/**
	 * view - the front end part, display what we have put together.
	 */
	public function view($msg = 'view')
	{
		$this->show($msg); // stub
	}

	/**
	 * build - put together the javascript, the html, needed. 
	 */
	public function build($msg = 'build')
	{
		$this->show($msg); // stub
	}

	/**
	 * data - harvest the data we need.
	 */
	public function data($msg = 'data')
	{
		$this->show($msg); // stub
	}

	/**
	 * html - construct the html needed, given our data.
	 */
	public function html($msg = 'html')
	{
		$this->show($msg); // stub
	}

	/**
	 * js - construct the javascript needed, given our data.
	 */
	public function js($msg = 'js')
	{
		$this->show($msg); // stub
	}

	/**
	 * show - display the msg.
	 */
	public function show($msg = 'blank')
	{
		print $this->msg($msg);
	}

	/**
	 * viewJson - display the msg.
	 */
	public function viewJson($rsp = null)
	{
		if ($rsp) {
			$this->showJson($rsp);
		} else {
			$this->showJson($this->rsp);
		}
	}

	/**
	 * setup - get our foundation data resources.
	 */
	public function setup()
	{

	}
	
	/**
	 * getRecordData - construct the data.
	 */
	public function getRecordData($pid, $criteria)
	{
		$records = null;
		
		$records = REDCap::getData($pid, "array", null, null, null, null, false, false, false, $criteria);
		
		return $records;
	}

	/**
	 * getDataDictionary - construct the data.  NOT INTENDED TO BE USED.
	 */
	public function getDataDictionary($pid)
	{
		$records = null;

		if ($pid) {
			$records = REDCap::getDataDictionary($pid, 'array');
		}

		return $records;
	}

	/**
	 * getProject - get the project data.
	 */
	public function getProject($pid = null)
	{
		$project = null;
		
		if ($pid) {
			$project = new Project($pid);
			$this->project = $project;
		}
		
		return $project;
	}

	/**
	 * readRedcapRecord - read the record data.
	 */
	public function readRedcapRecord($recordId)
	{
		return REDCap::getData('array', $recordId);
	}

	/**
	 * getOurModuleDir - get the name dir of our module.
	 */
	public function getOurModuleDir()
	{
		$getModulePath = $this->getModulePath();
		$parsed = explode('modules/', $getModulePath);
		$last = $parsed[1];
		$last = str_replace('/', '', $last);

		return $last;
	}

	/**
	 * showDagsListing - show the DAGs list.
	 */
	public function showDagsListing()
	{
		$html = $this->makeDagsListing();
		
		$x = $this->grabAllDagsListing();
	
		//$html .= print_r($x, true);
		$html .= '<hr>';
	
		foreach ($x as $key => $dag) {
			$flag = $this->isDagInternational($dag);
			if ($flag) {
				$html .= js_escape($dag) . ' is INTERNATIONAL.';
			} else {
				//$html .= $flag . ' ' . $dag . ' is NOT international.';
				$html .= js_escape($dag) . ' is domestic.';
			}
			$html .= '<br>';
		}
		
		$this->viewHtml($html, 'project');
	}

	/**
	 * showModuleNamesPaths - show module dir paths.
	 */
	public function showModuleNamesPaths()
	{
		$html = '';
		
		$moduleName = $this->getModuleName();
		
		$getModulePath = $this->getModulePath();
		
		$moduleDir = $this->getOurModuleDir();
		
		$html .= '<div id="hashModule">' . js_escape($moduleName) . '</div>';
		$html .= '<div id="getModulePath">' . js_escape($getModulePath) . '</div>';
		$html .= '<div id="last">' . js_escape($moduleDir) . '</div>';
		
		$this->viewHtml($html, 'project');
	}

	/**
	 * makeDagsListing - make html data of DAGs List.
	 */
	public function makeDagsListing()
	{
		$html = '';

		$this->listDags = $this->getDagsListing();
		
		foreach ($this->listDags as $key => $dag) {
			$html .= ' ' . js_escape($dag) . '<br>';
		}
		
		return $html;
	}

	/**
	 * grabAllDagsListing - make array list of DAGs with dag id and dag name.
	 */
	public function grabAllDagsListing()
	{
		$html = '';
		
		$projectId = $this->projectId;
		
		// SELECT group_id, project_id, group_name FROM redcap_data_access_groups where project_id = 19 order by group_name;
		$sql = 'SELECT group_id, group_name FROM redcap_data_access_groups where project_id = ' . db_escape($projectId) . ' order by group_name';

		$result = db_query($sql);
		$details = db_fetch_assoc($result);
		
		$list = array();
		if ($result) {
			foreach ($result as $key => $val) {
				$list[$val['group_id']] = $val['group_name'];
			}
		}
		
		return $list;
	}

	/**
	 * isDagInternational - check given DAG for international.
	 */
	public function isDagInternational($dag)
	{
		$flag = false;
		
		$listDags = $this->getDagsListing();
		$listDags = array_flip($listDags);
		
		if (isset($listDags[$dag])) {
			$flag = true;
		}
		
		return ($flag ? '1' : '0');
	}
	
	/**
	 * makeUniqueGroupName - convert DAG name to the unique group name, lowercase and underscores no spaces.
	 * REDCap core: see Project.php getUniqueGroupNames()
	 */
	public function makeUniqueGroupName($dagName)
	{
		$dagUniqueGroupName = '';
		
		// limit the characters used, no parens for example
		$dagName = preg_replace("/[^0-9a-z_ ]/i", '', trim($dagName));
		
		// convert DAG name to the unique group name, lowercase and underscores no spaces
		$dagUniqueGroupName = str_replace(' ', '_', strtolower($dagName)); 

		// size to 18 chars
		$dagUniqueGroupName = substr($dagUniqueGroupName, 0, 18);

		// trim trailing underscore
		$dagUniqueGroupName = rtrim($dagUniqueGroupName, '_');
						
		return $dagUniqueGroupName;
	}
	
	/**
	 * makeDagsListing - make html data of DAGs List.
	 */
	public function makeDagsListingOptions()
	{
		$html = '';

		$listDags = $this->grabAllDagsListing();
		
		$html .= '<select id="selectDagsListing" onchange="checklocus('.js_escape($this->projectId).');">';

		$html .= '<option value="' . '' . '">' . '' . '</option>'; // blank line

		foreach ($listDags as $key => $dag) {
			$dagUniqueGroupName = $this->makeUniqueGroupName($dag);
			$html .= '<option value="' . js_escape($dagUniqueGroupName) . '">' . js_escape($dag). '</option>';
		}
		$html .= '</select>';
		
		return $html;
	}
	
	/**
	 * makeDagsListingOptionsInternational - make html data of DAGs List. NOT USED
	 */
	public function makeDagsListingOptionsInternational()
	{
		$html = '';

		$this->listDags = $this->getDagsListing();
		
		$html .= '<select id="selectDagsListing">';
		$dag = '';
		$html .= '<option value="' . '' . '">' . js_escape($dag) . '</option>';
		foreach ($this->listDags as $key => $dag) {
			$html .= '<option value="' . js_escape($this->makeUniqueGroupName($dag)) . '">' . js_escape($dag) . '</option>';
		}
		$html .= '</select>';
		
		return $html;
	}

	/**
	 * getDagsListing - read the config DAG list.
	 */
	public function getDagsListing()
	{
		$list = array();
		
		$list = $this->getProjectSetting('dags_listing_international');
		
		asort($list);
		
		$newlist = array();
		foreach ($list as $key => $val) {
			$newlist[] = $this->makeUniqueGroupName($val);
		}
		$this->listDags = $newlist;
		
		return $newlist;
	}
	
	/**
	 * writeRedcapRecord - write the record data.
	 */
	public function writeRedcapRecord($projectId, $recordSaveData, $dag = '')
	{
		return REDCap::saveData($projectId, 'array', $recordSaveData, 'normal', 'YMD', 'flat', $dag);
	}

	/**
	 * getYearOfBirth - extract the year from a date time string.
	 */
	public function getYearOfBirth($dateOfBirth = null)
	{
		$yearOfBirth = '';
		
		if (!$dateOfBirth) {
			$dateOfBirth = $this->dateOfBirth;
		}
		
		if ($dateOfBirth) {
			$vals = explode(' ', $dateOfBirth);  // yyyy-mm-dd hh:mm
			$datePortion = $vals[0];
	
			$monthDayYear = explode('-', $datePortion);
			$yearOfBirth = $monthDayYear[0];  // yyyy-mm-dd
		}
		
		return $yearOfBirth;
	}

	/**
	 * getYearOfBirth - get the birth year out of a date of birth data.
	 */
	public function getYearOfBirthVariantOne($dateOfBirth = null)
	{
		$yearOfBirth = null;
		
		if (!$dateOfBirth) {
			$dateOfBirth = $this->dateOfBirth;
		}
		
		if ($dateOfBirth) {
			$len = strlen($dateOfBirth) - 4;
			
			if ($len) { // mm/dd/yyyy  012345 6789
				$yearOfBirth = substr($dateOfBirth, $len, 4); // get last four digits?
			} else {
				$yearOfBirth = $dateOfBirth;
			}
		}
		
		return $yearOfBirth;
	}

	/**
	 * generateHash - short name for generate the Hash Code.
	 * @return The hash code.
	 */
	public function generateHash($firstName = null, $lastName = null, $dateOfBirth = null)
	{
		return $this->generateHashCode($firstName, $lastName, $dateOfBirth);
	}

	/**
	 * checkExistingHash - 
	 *  N = record exists for that hash code
	 *  0 = no record for that hash code, thus a new record can be made
	 * @return 0 or N (record ID).
	 */
	public function checkExistingHash($projectId, $hash = null)
	{
		$flag = false;
		$count = 0;
		$recordId = 0;
		
		// look up by hash
		$hashField = $this->hashCodeField; 

		if (!$hash) {
			return '0';
		}

		$sql = 'SELECT record AS recordId, event_id AS eventId, instance FROM redcap_data WHERE project_id = ' . db_escape($projectId) . ' and field_name = \'' . db_escape($hashField) . '\' and value = \'' . $hash . '\'';
		
		$result = db_query($sql);
		$details = db_fetch_assoc($result);
		db_free_result($result);
		
		if (isset($details['recordId'])) {
			if (strlen($details['recordId']) > 0) {
				$recordId = $details['recordId'];
				
				$dagId = $this->getDagId($projectId, $recordId);
				
				$recordId = $recordId . ',' . $dagId;  // we need to send back the DAG info, if any (the name actually), along with the record ID
				$flag = true;

				if ($this->debugLogFlag) {
					$this->debugLog('checkExistingHash: [' . $recordId  . '] [' . $dagId . ']', ' recordId [' . $recordId  . '] dagId [' . $dagId . ']');
				}
			}
		}
		
		return ($flag ? $recordId : '0');
	}

	/**
	 * countCheckExistingHash - .
	 *  1 = record exists for that hash code
	 *  0 = no record for that hash code, thus a new record can be made
	 * @return flag .
	 */
	public function countCheckExistingHash($projectId, $hash = null)
	{
		$flag = false;
		$count = 0;
		
		// look up by hash
		$hashField = $this->hashCodeField; 

		if (!$hash) {
			return '0';
		}

		$sql = 'SELECT COUNT(*) AS count FROM redcap_data WHERE project_id = ' . db_escape($projectId) . ' and field_name = \'' . db_escape($hashField) . '\' and value = \'' . $hash . '\'';
		
		$result = db_query($sql);
		$details = db_fetch_assoc($result);
		db_free_result($result);
		
		if (isset($details['count'])) {
			if ($details['count'] > 0) {
				$flag = true;
			}
		}
		
		if ($count) {
			$flag = true;
		}
		return ($flag ? '1' : '0');
	}

	/**
	 * reformatDateAsYmd - change date mm-dd-yyyy to yyyy-mm-dd.
	 */
	public function reformatDateAsYmd($dateStr) 
	{
		$newDateStr = '';
		
		$year  = '';
		$month = '';
		$day   = '';
		
		if (substr_count($dateStr, '-')) {
			$dateData = explode('-', $dateStr);
			if ($this->debugLogFlag) {
				$msg = 'Date Str: ' . $dateStr . ' uses dashes';
				$this->debugLog($msg, $msg);
			}
		} else if (substr_count($dateStr, '/')) {
			$dateData = explode('/', $dateStr);
			if ($this->debugLogFlag) {
				$msg = 'Date Str: ' . $dateStr . ' uses slashes';
				$this->debugLog($msg, $msg);
			}
		} else {
			if ($this->debugLogFlag) {
				$msg = 'Date Str: ' . $dateStr . ' uses unknown';
				$this->debugLog($msg, $msg);
			}
			return '';
		}
		
		$year  = $dateData[2];
		$month = $dateData[0];
		$day   = $dateData[1];
		
		$newDateStr = $year . '-' . $month . '-' . $day;
		
		return $newDateStr;
	}

	/**
	 * addAutoNumberedRecordPrefixed - modfified from AbstractExternalModule method addAutoNumberedRecord.
	 */
	public function addAutoNumberedRecordPrefixed($pid = null, $prefix = '') 
	{
		if ($this->debugLogFlag) {
			$this->debugLog('Data addAutoNumberedRecordPrefixed start: ', ' PID [' . $pid  . '] prefix [' . $prefix . ']');
		}

		if ($pid === null) {
			$this->debugLog('PID addAutoNumberedRecordPrefixed: ', ' PID is NULL');
			return null;
		}

		$eventId = $this->getFirstEventId($pid);
		$fieldName = \Records::getTablePK($pid);
		$recordId = $this->getNextAutoNumberedRecordIdPrefixed($pid, $prefix);

		$this->query("insert into redcap_data (project_id, event_id, record, field_name, value) values (".db_escape($pid).",".db_escape($eventId).", '". db_escape($recordId)."', '".db_escape($fieldName)."', '".db_escape($recordId)."')");
		
		$result = $this->query("select count(1) as count from redcap_data where project_id = ".db_escape($pid)." and event_id = ".db_escape($eventId)." and record = '".db_escape($recordId)."' and field_name = '".db_escape($fieldName)."' and value = '".db_escape($recordId)."'");
		$count = $result->fetch_assoc()['count'];
		
		if($count > 1) {
			$this->query("delete from redcap_data where project_id = ".db_escape($pid)." and event_id = ".db_escape($eventId)." and record = '".db_escape($recordId)."' and field_name = '".db_escape($fieldName)."' limit 1");

			return $this->addAutoNumberedRecordPrefixed($pid, $prefix);
		} else if($count == 0) {
			throw new Exception("An error occurred while adding an auto numbered record for project $pid.");
		}

		$this->updateRecordCountPrefixed($pid);

		if ($this->debugLogFlag) {
			$this->debugLog('Data addAutoNumberedRecordPrefixed end: ', ' Record ID [' . $recordId . ']');
		}

		return $recordId;
	}

	/**
	 * updateRecordCountPrefixed - modfified from AbstractExternalModule method updateRecordCount.
	 */
	public function updateRecordCountPrefixed($pid, $prefix = ''){
		//$results = $this->query("select count(1) as count from (select 1 from redcap_data where project_id = $pid group by record) a");
		
		//$count = $results->fetch_assoc()['count'];
		
		//$this->query("update redcap_record_counts set record_count = $count where project_id = $pid");
		//Fix for RDD-885 Adding new records does not display in record status dashboard or in the edit records
		//Talked with Rob and he suggested to delete instead of adding and let REDCap handle the creation of record on click of Record Status Dashboard.
		//This way REDCap will also new values which are not done currently when we use to updated redcap_record_counts table manually.
		$this->query("delete from redcap_record_counts where project_id = ".db_escape($pid));
	}

/*

			select RIGHT(record, LENGTH(record) - 3) as dataval from redcap_data 
			where project_id = 159 and record like 'Dys%'
			group by record
            order by dataval * 1 desc limit 1;
*/
	/**
	 * getNextAutoNumberedRecordIdPrefixed - modfified from AbstractExternalModule method getNextAutoNumberedRecordId.
	 */
	public function getNextAutoNumberedRecordIdPrefixed($pid, $prefix = '') 
	{
		$lenPrefix = strlen($prefix);
		$results = $this->query("select RIGHT(record, LENGTH(record) - $lenPrefix) as recordnumber from redcap_data 
			where project_id = ".db_escape($pid)." and record like '$prefix%'
			group by record
			order by recordnumber * 1 desc limit 1");

		$row = $results->fetch_assoc();
		
		if(empty($row)) {
			return $prefix . 1;
		} else {
			$number = $row['recordnumber'];
			$number++;

			return $prefix . $number;
		}
	}

	/**
	 * generateHashCode - generate the Hash Code of the First, Last, Middle, name and Date of Birth (full mm-dd-yyyy), .
	 * @return The hash code.
	 */
	public function makeNewRecord($projectId, $recordId, $eventId, $instrument, $firstName, $lastName, $dateOfBirth, $hashCodeData, $dagId, $isInternational = '0')
	{
		$birthDate = $this->reformatDateAsYmd($dateOfBirth);
		
		if ($isInternational) {
			$this->firstName    = '';
			$this->lastName     = '';
			$this->dateOfBirth  = '';
		} else {
			$this->firstName    = $firstName;
			$this->lastName     = $lastName;
			$this->dateOfBirth  = $dateOfBirth;
		}
		
		$this->hashCodeData = $hashCodeData;
		
		$newRecordId = $this->addAutoNumberedRecordPrefixed($projectId, $this->custom_prefix);
		if ($newRecordId === null) {
			$this->debugLog('Error makeNewRecord: ', 'cannot add an auto numbered record, PID is null.');
			return '0'; // fail
		}
		
		$recordId = $newRecordId;
		
		//$eventId = 204;//$this->getFirstEventId($pid);  // FIXME: hack
		$eventId = $this->getEventId($projectId, $this->instrument_to_use);
		
		if ($this->debugLogFlag) {
			$this->debugLog('makeNewRecord: ', ' eventId ID [' . $eventId . ']');
			$this->debugLog('Data makeNewRecord: ', ' New Record ID [' . $newRecordId . ']');
		}
		
		if ($isInternational) {
			$dataRecord[$recordId][$eventId][$this->firstNameField]   = '';
			$dataRecord[$recordId][$eventId][$this->lastNameField]    = '';
			$dataRecord[$recordId][$eventId][$this->dateOfBirthField] = '';
		} else {
			$dataRecord[$recordId][$eventId][$this->firstNameField]   = $this->firstName;
			$dataRecord[$recordId][$eventId][$this->lastNameField]    = $this->lastName;
			$dataRecord[$recordId][$eventId][$this->dateOfBirthField] = $birthDate;   // Y-M-D  04-09-1999   1999-04-09
		}

		$dataRecord[$recordId][$eventId][$this->hashCodeField]      = $this->hashCodeData;
		$dataRecord[$recordId][$eventId][$this->yearField]         = $this->getYearOfBirth($birthDate);

		$dataRecord[$recordId][$eventId][$this->locationFlagField] = ($isInternational ? '1' : '0');  // location_flag : 0	us, 1	international
		
		if ($this->debugLogFlag) {
			$this->debugLog('dataRecord saveData', print_r($dataRecord, true));
		}
		
		$response = $this->writeRedcapRecord($projectId, $dataRecord, $dagId);
		
		$errorCount = count($response['errors']);
		if ($errorCount) {
			$this->debugLog('Errors when saveData', print_r($response, true));
		}		
		
		return ($errorCount ? '0' : $newRecordId);  // saving:  0 = fail, N = success (new record ID)
	}

	/**
	 * generateHashCode - generate the Hash Code of the First, Last, Middle, name and Date of Birth (full mm-dd-yyyy), .
	 * @return The hash code.
	 */
	public function generateHashCode($firstName = null, $lastName = null, $dateOfBirth = null)
	{
		$checkData = false;
		
		if ($firstName && $lastName && $dateOfBirth) {
			$checkData = true;
		}
		
		if ($checkData) {

			$firstName   = ($firstName ? $firstName : $this->firstName);
			$lastName    = ($lastName ? $lastName : $this->lastName);
			$dateOfBirth = ($dateOfBirth ? $dateOfBirth : $this->dateOfBirth);
	
			$this->hashCodeData = hash('sha512', ($firstName . $lastName . $dateOfBirth));
		}
		
		return $this->hashCodeData;
	}

	/**
	 * getEventId - find an event id for the project and instance.
	 */
	public function getEventId($projectId, $instrument)
	{
		$eventId = null;

		$sql = 'SELECT m.event_id AS eventId FROM redcap_events_arms a JOIN redcap_events_metadata m ON a.arm_id = m.arm_id JOIN redcap_events_forms f ON f.event_id = m.event_id WHERE a.project_id = ' . db_escape($projectId) . ' AND f.form_name = \'' . db_escape(strtolower($instrument)) . '\' ORDER BY m.event_id';

		if ($this->debugLogFlag) {
			$this->debugLog('getEventId: [' . $instrument  . ']', 'getEventId: [' . $sql  . ']' . ' projectId: [' . $projectId  . ']' .' instrument: [' . $instrument  . ']');
		}

		$result = db_query($sql);
		$details = db_fetch_assoc($result);
		
		if (isset($details['eventId'])) {
			if ($details['eventId'] > 0) {
				$eventId = $details['eventId'];
			}
		}
		
		if ($this->debugLogFlag) {
			$this->debugLog('getEventId: eventId[' . $eventId  . ']',  'eventId: [' . $eventId  . ']' );
		}
		
		return $eventId;
	}

	/**
	 * redcap_survey_complete - hook redcap_survey_complete.
	 */
	public function redcap_survey_complete($projectId, $recordId, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
	{
	}

	/**
	 * redcap_every_page_top - hook redcap_every_page_top.
	 */
	public function redcap_every_page_top($projectId)
	{
	}
	
	/**
	 * redcap_add_edit_records_page - hook redcap_add_edit_records_page.
	 */
	public function redcap_add_edit_records_page($projectId, $instrument, $event_id)
	{
		$instrument = $this->instrument_to_use;
		
		echo $this->generateJs($projectId, $instrument, $event_id);
	}
	
	/**
	 * redcap_save_record - hook redcap_save_record.
	 */
	public function redcap_save_record($projectId, $recordId, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
	{
	}
	
	/**
	 * getDagForUser - find a users DAG ID given a project ID and a username.
	 
	 *  giveName = false, return groupId   (number  17)
	 *  giveName = true,  return groupName (string  u_rome)
	 */
	public function getDagForUser($projectId, $username, $giveName = false)
	{
		$groupInfo = null;
		
		$sql = 'SELECT R.group_id AS groupId, D.group_name AS groupName FROM redcap_user_rights AS R JOIN  redcap_data_access_groups AS D ON (D.group_id = R.group_id) where R.project_id = ' . db_escape($projectId) . ' AND R.username = ' . "'" . db_escape($username) . "'" . '';

		$result = db_query($sql);
		$details = db_fetch_assoc($result);
		
		if (isset($details['groupId'])) {
			if ($details['groupId'] > 0) {
				$groupInfo = $details['groupId'];
			}
		}
		
		if ($giveName) {
			if (isset($details['groupName'])) {
				if ($details['groupName'] > '') {
					$groupInfo = $details['groupName'];
					$groupInfo = $this->makeUniqueGroupName($groupInfo);
				}
			}
		}
		
		return $groupInfo;
	}

	/**
	 * makeHtmlBits - .
	 */
	public function makeHtmlBits()
	{
		$nl = "\n";
		$html = '';
		
		$html = '';
		
		
		return $html;
	}
	
	/**
	 * makeJsHashCalc - the hashing code algorithm in javascript.
	 */
	public function makeJsHashCalc()
	{
		/* A JavaScript implementation of the SHA family of hashes, as defined in FIPS
		 * PUB 180-2 as well as the corresponding HMAC implementation as defined in
		 * FIPS PUB 198a
		 *
		 * Version 1.3 Copyright Brian Turek 2008-2010
		 * Distributed under the BSD License
		 * See http://jssha.sourceforge.net/ for more information
		 *
		 * Several functions taken from Paul Johnson
		 */
		// this is the hash calc code
		
		$brnl = '<br>'."\n";
		$nl = "\n";
		$js = '';
		
		$js .= $nl;

		
		$xjs = <<<EOD
(function() {
	var charSize=8,b64pad="",hexCase=0,Int_64=function(a,b){this.highOrder=a;this.lowOrder=b},str2binb=function(a){var b=[],mask=(1<<charSize)-1,length=a.length*charSize,i;for(i=0;i<length;i+=charSize){b[i>>5]|=(a.charCodeAt(i/charSize)&mask)<<(32-charSize-(i%32))}return b},hex2binb=function(a){var b=[],length=a.length,i,num;for(i=0;i<length;i+=2){num=parseInt(a.substr(i,2),16);if(!isNaN(num)){b[i>>3]|=num<<(24-(4*(i%8)))}else{return"INVALID HEX STRING"}}return b},binb2hex=function(a){var b=(hexCase)?"0123456789ABCDEF":"0123456789abcdef",str="",length=a.length*4,i,srcByte;for(i=0;i<length;i+=1){srcByte=a[i>>2]>>((3-(i%4))*8);str+=b.charAt((srcByte>>4)&0xF)+b.charAt(srcByte&0xF)}return str},binb2b64=function(a){var b="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"+"0123456789+/",str="",length=a.length*4,i,j,triplet;for(i=0;i<length;i+=3){triplet=(((a[i>>2]>>8*(3-i%4))&0xFF)<<16)|(((a[i+1>>2]>>8*(3-(i+1)%4))&0xFF)<<8)|((a[i+2>>2]>>8*(3-(i+2)%4))&0xFF);for(j=0;j<4;j+=1){if(i*8+j*6<=a.length*32){str+=b.charAt((triplet>>6*(3-j))&0x3F)}else{str+=b64pad}}}return str},rotr=function(x,n){if(n<=32){return new Int_64((x.highOrder>>>n)|(x.lowOrder<<(32-n)),(x.lowOrder>>>n)|(x.highOrder<<(32-n)))}else{return new Int_64((x.lowOrder>>>n)|(x.highOrder<<(32-n)),(x.highOrder>>>n)|(x.lowOrder<<(32-n)))}},shr=function(x,n){if(n<=32){return new Int_64(x.highOrder>>>n,x.lowOrder>>>n|(x.highOrder<<(32-n)))}else{return new Int_64(0,x.highOrder<<(32-n))}},ch=function(x,y,z){return new Int_64((x.highOrder&y.highOrder)^(~x.highOrder&z.highOrder),(x.lowOrder&y.lowOrder)^(~x.lowOrder&z.lowOrder))},maj=function(x,y,z){return new Int_64((x.highOrder&y.highOrder)^(x.highOrder&z.highOrder)^(y.highOrder&z.highOrder),(x.lowOrder&y.lowOrder)^(x.lowOrder&z.lowOrder)^(y.lowOrder&z.lowOrder))},sigma0=function(x){var a=rotr(x,28),rotr34=rotr(x,34),rotr39=rotr(x,39);return new Int_64(a.highOrder^rotr34.highOrder^rotr39.highOrder,a.lowOrder^rotr34.lowOrder^rotr39.lowOrder)},sigma1=function(x){var a=rotr(x,14),rotr18=rotr(x,18),rotr41=rotr(x,41);return new Int_64(a.highOrder^rotr18.highOrder^rotr41.highOrder,a.lowOrder^rotr18.lowOrder^rotr41.lowOrder)},gamma0=function(x){var a=rotr(x,1),rotr8=rotr(x,8),shr7=shr(x,7);return new Int_64(a.highOrder^rotr8.highOrder^shr7.highOrder,a.lowOrder^rotr8.lowOrder^shr7.lowOrder)},gamma1=function(x){var a=rotr(x,19),rotr61=rotr(x,61),shr6=shr(x,6);return new Int_64(a.highOrder^rotr61.highOrder^shr6.highOrder,a.lowOrder^rotr61.lowOrder^shr6.lowOrder)},safeAdd_2=function(x,y){var a,msw,lowOrder,highOrder;a=(x.lowOrder&0xFFFF)+(y.lowOrder&0xFFFF);msw=(x.lowOrder>>>16)+(y.lowOrder>>>16)+(a>>>16);lowOrder=((msw&0xFFFF)<<16)|(a&0xFFFF);a=(x.highOrder&0xFFFF)+(y.highOrder&0xFFFF)+(msw>>>16);msw=(x.highOrder>>>16)+(y.highOrder>>>16)+(a>>>16);highOrder=((msw&0xFFFF)<<16)|(a&0xFFFF);return new Int_64(highOrder,lowOrder)},safeAdd_4=function(a,b,c,d){var e,msw,lowOrder,highOrder;e=(a.lowOrder&0xFFFF)+(b.lowOrder&0xFFFF)+(c.lowOrder&0xFFFF)+(d.lowOrder&0xFFFF);msw=(a.lowOrder>>>16)+(b.lowOrder>>>16)+(c.lowOrder>>>16)+(d.lowOrder>>>16)+(e>>>16);lowOrder=((msw&0xFFFF)<<16)|(e&0xFFFF);e=(a.highOrder&0xFFFF)+(b.highOrder&0xFFFF)+(c.highOrder&0xFFFF)+(d.highOrder&0xFFFF)+(msw>>>16);msw=(a.highOrder>>>16)+(b.highOrder>>>16)+(c.highOrder>>>16)+(d.highOrder>>>16)+(e>>>16);highOrder=((msw&0xFFFF)<<16)|(e&0xFFFF);return new Int_64(highOrder,lowOrder)},safeAdd_5=function(a,b,c,d,e){var f,msw,lowOrder,highOrder;f=(a.lowOrder&0xFFFF)+(b.lowOrder&0xFFFF)+(c.lowOrder&0xFFFF)+(d.lowOrder&0xFFFF)+(e.lowOrder&0xFFFF);msw=(a.lowOrder>>>16)+(b.lowOrder>>>16)+(c.lowOrder>>>16)+(d.lowOrder>>>16)+(e.lowOrder>>>16)+(f>>>16);lowOrder=((msw&0xFFFF)<<16)|(f&0xFFFF);f=(a.highOrder&0xFFFF)+(b.highOrder&0xFFFF)+(c.highOrder&0xFFFF)+(d.highOrder&0xFFFF)+(e.highOrder&0xFFFF)+(msw>>>16);msw=(a.highOrder>>>16)+(b.highOrder>>>16)+(c.highOrder>>>16)+(d.highOrder>>>16)+(e.highOrder>>>16)+(f>>>16);highOrder=((msw&0xFFFF)<<16)|(f&0xFFFF);return new Int_64(highOrder,lowOrder)},coreSHA2=function(j,k,l){var a,b,c,d,e,f,g,h,T1,T2,H,lengthPosition,i,t,K,W=[],appendedMessageLength;if(l==="SHA-384"||l==="SHA-512"){lengthPosition=(((k+128)>>10)<<5)+31;K=[new Int_64(0x428a2f98,0xd728ae22),new Int_64(0x71374491,0x23ef65cd),new Int_64(0xb5c0fbcf,0xec4d3b2f),new Int_64(0xe9b5dba5,0x8189dbbc),new Int_64(0x3956c25b,0xf348b538),new Int_64(0x59f111f1,0xb605d019),new Int_64(0x923f82a4,0xaf194f9b),new Int_64(0xab1c5ed5,0xda6d8118),new Int_64(0xd807aa98,0xa3030242),new Int_64(0x12835b01,0x45706fbe),new Int_64(0x243185be,0x4ee4b28c),new Int_64(0x550c7dc3,0xd5ffb4e2),new Int_64(0x72be5d74,0xf27b896f),new Int_64(0x80deb1fe,0x3b1696b1),new Int_64(0x9bdc06a7,0x25c71235),new Int_64(0xc19bf174,0xcf692694),new Int_64(0xe49b69c1,0x9ef14ad2),new Int_64(0xefbe4786,0x384f25e3),new Int_64(0x0fc19dc6,0x8b8cd5b5),new Int_64(0x240ca1cc,0x77ac9c65),new Int_64(0x2de92c6f,0x592b0275),new Int_64(0x4a7484aa,0x6ea6e483),new Int_64(0x5cb0a9dc,0xbd41fbd4),new Int_64(0x76f988da,0x831153b5),new Int_64(0x983e5152,0xee66dfab),new Int_64(0xa831c66d,0x2db43210),new Int_64(0xb00327c8,0x98fb213f),new Int_64(0xbf597fc7,0xbeef0ee4),new Int_64(0xc6e00bf3,0x3da88fc2),new Int_64(0xd5a79147,0x930aa725),new Int_64(0x06ca6351,0xe003826f),new Int_64(0x14292967,0x0a0e6e70),new Int_64(0x27b70a85,0x46d22ffc),new Int_64(0x2e1b2138,0x5c26c926),new Int_64(0x4d2c6dfc,0x5ac42aed),new Int_64(0x53380d13,0x9d95b3df),new Int_64(0x650a7354,0x8baf63de),new Int_64(0x766a0abb,0x3c77b2a8),new Int_64(0x81c2c92e,0x47edaee6),new Int_64(0x92722c85,0x1482353b),new Int_64(0xa2bfe8a1,0x4cf10364),new Int_64(0xa81a664b,0xbc423001),new Int_64(0xc24b8b70,0xd0f89791),new Int_64(0xc76c51a3,0x0654be30),new Int_64(0xd192e819,0xd6ef5218),new Int_64(0xd6990624,0x5565a910),new Int_64(0xf40e3585,0x5771202a),new Int_64(0x106aa070,0x32bbd1b8),new Int_64(0x19a4c116,0xb8d2d0c8),new Int_64(0x1e376c08,0x5141ab53),new Int_64(0x2748774c,0xdf8eeb99),new Int_64(0x34b0bcb5,0xe19b48a8),new Int_64(0x391c0cb3,0xc5c95a63),new Int_64(0x4ed8aa4a,0xe3418acb),new Int_64(0x5b9cca4f,0x7763e373),new Int_64(0x682e6ff3,0xd6b2b8a3),new Int_64(0x748f82ee,0x5defb2fc),new Int_64(0x78a5636f,0x43172f60),new Int_64(0x84c87814,0xa1f0ab72),new Int_64(0x8cc70208,0x1a6439ec),new Int_64(0x90befffa,0x23631e28),new Int_64(0xa4506ceb,0xde82bde9),new Int_64(0xbef9a3f7,0xb2c67915),new Int_64(0xc67178f2,0xe372532b),new Int_64(0xca273ece,0xea26619c),new Int_64(0xd186b8c7,0x21c0c207),new Int_64(0xeada7dd6,0xcde0eb1e),new Int_64(0xf57d4f7f,0xee6ed178),new Int_64(0x06f067aa,0x72176fba),new Int_64(0x0a637dc5,0xa2c898a6),new Int_64(0x113f9804,0xbef90dae),new Int_64(0x1b710b35,0x131c471b),new Int_64(0x28db77f5,0x23047d84),new Int_64(0x32caab7b,0x40c72493),new Int_64(0x3c9ebe0a,0x15c9bebc),new Int_64(0x431d67c4,0x9c100d4c),new Int_64(0x4cc5d4be,0xcb3e42b6),new Int_64(0x597f299c,0xfc657e2a),new Int_64(0x5fcb6fab,0x3ad6faec),new Int_64(0x6c44198c,0x4a475817)];if(l==="SHA-384"){H=[new Int_64(0xcbbb9d5d,0xc1059ed8),new Int_64(0x0629a292a,0x367cd507),new Int_64(0x9159015a,0x3070dd17),new Int_64(0x0152fecd8,0xf70e5939),new Int_64(0x67332667,0xffc00b31),new Int_64(0x98eb44a87,0x68581511),new Int_64(0xdb0c2e0d,0x64f98fa7),new Int_64(0x047b5481d,0xbefa4fa4)]}else{H=[new Int_64(0x6a09e667,0xf3bcc908),new Int_64(0xbb67ae85,0x84caa73b),new Int_64(0x3c6ef372,0xfe94f82b),new Int_64(0xa54ff53a,0x5f1d36f1),new Int_64(0x510e527f,0xade682d1),new Int_64(0x9b05688c,0x2b3e6c1f),new Int_64(0x1f83d9ab,0xfb41bd6b),new Int_64(0x5be0cd19,0x137e2179)]}}j[k>>5]|=0x80<<(24-k%32);j[lengthPosition]=k;appendedMessageLength=j.length;for(i=0;i<appendedMessageLength;i+=32){a=H[0];b=H[1];c=H[2];d=H[3];e=H[4];f=H[5];g=H[6];h=H[7];for(t=0;t<80;t+=1){if(t<16){W[t]=new Int_64(j[t*2+i],j[t*2+i+1])}else{W[t]=safeAdd_4(gamma1(W[t-2]),W[t-7],gamma0(W[t-15]),W[t-16])}T1=safeAdd_5(h,sigma1(e),ch(e,f,g),K[t],W[t]);T2=safeAdd_2(sigma0(a),maj(a,b,c));h=g;g=f;f=e;e=safeAdd_2(d,T1);d=c;c=b;b=a;a=safeAdd_2(T1,T2)}H[0]=safeAdd_2(a,H[0]);H[1]=safeAdd_2(b,H[1]);H[2]=safeAdd_2(c,H[2]);H[3]=safeAdd_2(d,H[3]);H[4]=safeAdd_2(e,H[4]);H[5]=safeAdd_2(f,H[5]);H[6]=safeAdd_2(g,H[6]);H[7]=safeAdd_2(h,H[7])}switch(l){case"SHA-384":return[H[0].highOrder,H[0].lowOrder,H[1].highOrder,H[1].lowOrder,H[2].highOrder,H[2].lowOrder,H[3].highOrder,H[3].lowOrder,H[4].highOrder,H[4].lowOrder,H[5].highOrder,H[5].lowOrder];case"SHA-512":return[H[0].highOrder,H[0].lowOrder,H[1].highOrder,H[1].lowOrder,H[2].highOrder,H[2].lowOrder,H[3].highOrder,H[3].lowOrder,H[4].highOrder,H[4].lowOrder,H[5].highOrder,H[5].lowOrder,H[6].highOrder,H[6].lowOrder,H[7].highOrder,H[7].lowOrder];default:return[]}},jsSHA=function(a,b){this.sha384=null;this.sha512=null;this.strBinLen=null;this.strToHash=null;if("HEX"===b){if(0!==(a.length%2)){return"TEXT MUST BE IN BYTE INCREMENTS"}this.strBinLen=a.length*4;this.strToHash=hex2binb(a)}else if(("ASCII"===b)||('undefined'===typeof(b))){this.strBinLen=a.length*charSize;this.strToHash=str2binb(a)}else{return"UNKNOWN TEXT INPUT TYPE"}};jsSHA.prototype={getHash:function(a,b){var c=null,message=this.strToHash.slice();switch(b){case"HEX":c=binb2hex;break;case"B64":c=binb2b64;break;default:return"FORMAT NOT RECOGNIZED"}switch(a){case"SHA-384":if(null===this.sha384){this.sha384=coreSHA2(message,this.strBinLen,a)}return c(this.sha384);case"SHA-512":if(null===this.sha512){this.sha512=coreSHA2(message,this.strBinLen,a)}return c(this.sha512);default:return"HASH NOT RECOGNIZED"}},getHMAC:function(a,b,c,d){var e,keyToUse,i,retVal,keyBinLen,hashBitSize,keyWithIPad=[],keyWithOPad=[];switch(d){case"HEX":e=binb2hex;break;case"B64":e=binb2b64;break;default:return"FORMAT NOT RECOGNIZED"}switch(c){case"SHA-384":hashBitSize=384;break;case"SHA-512":hashBitSize=512;break;default:return"HASH NOT RECOGNIZED"}if("HEX"===b){if(0!==(a.length%2)){return"KEY MUST BE IN BYTE INCREMENTS"}keyToUse=hex2binb(a);keyBinLen=a.length*4}else if("ASCII"===b){keyToUse=str2binb(a);keyBinLen=a.length*charSize}else{return"UNKNOWN KEY INPUT TYPE"}if(128<(keyBinLen/8)){keyToUse=coreSHA2(keyToUse,keyBinLen,c);keyToUse[31]&=0xFFFFFF00}else if(128>(keyBinLen/8)){keyToUse[31]&=0xFFFFFF00}for(i=0;i<=31;i+=1){keyWithIPad[i]=keyToUse[i]^0x36363636;keyWithOPad[i]=keyToUse[i]^0x5C5C5C5C}retVal=coreSHA2(keyWithIPad.concat(this.strToHash),1024+this.strBinLen,c);retVal=coreSHA2(keyWithOPad.concat(retVal),1024+hashBitSize,c);return(e(retVal))}};window.jsSHA=jsSHA
	}
()
);

EOD;

		$js .= $xjs;

		$js .= $nl;
		
		return $js;
	}
	
	/**
	 * makeJsCustomCode - the mess of custom javascript code.
	 */
	public function makeJsCustomCode($projectId, $instrument, $event_id)
	{
		global $redcap_base_url, $redcap_version;

		$brnl = '<br>'."\n";
		$nl = "\n";
		$js = '';
		
		$js .= $nl;
		
		$dagGroupFound = false;
		$dagGroupId = 0;
		$dagGroupName = '';
		$dagGroupIsInternational = false;
		// get user
		$username = USERID;
		//$user = \User::getUserInfo(USERID);
		// check for DAG group
		$dagGroupName = $this->getDagForUser($projectId, $username, true);
		$dagGroupFound = ($dagGroupName > '' ? true : false);
		// if a DAG, check if international
		if ($dagGroupFound) {
			$dagGroupIsInternational = $this->isDagInternational($dagGroupName);
		}
		// prefill see: Current DAG   html code below
		

		$hashDir = $this->getOurModuleDir();
		
		$instrumentName = $this->instrument_to_use;
		$projectInstrumentEventId = $this->getEventId($projectId, $this->instrument_to_use);
		
		$pathBaseDataEntryIndex = $redcap_base_url . 'redcap_v' . $redcap_version . '/DataEntry/index.php'; 
		
		$pathToAjaxFunction                   = $this->getUrl('isHashRecordExist.php', false, false);
		$pathToAjaxFunctionCheckInternational = $this->getUrl('isInternational.php',   false, false);
		$pathToAjaxCreateRecordFunction       = $this->getUrl('createRecord.php',      false, false);
		
		$msgExistingRecord = self::MSG_EXISTING_RECORD;
		$msgProcessHashRequest = self::MSG_PROCESS_HASH_REQUEST;

		$btnProcessHashRequestSave = self::BTN_SAVE_PROCESS_HASH_REQUEST;
		$btnProcessHashRequestCreate = self::BTN_CREATE_PROCESS_HASH_REQUEST;
		
		$strDivInputFields = '';

		$strDivInputFields .= '<div id="msgHashCode" class="alert alert-success" style="display:none;"><p>Test Text</p></div>';
		$strDivInputFields .= '<div id="hashcodemsg" class="alert alert-success" style="display:none;"><p>hash code</p></div>';
		$strDivInputFields .= '<div id="msgShowLocus" class="alert alert-success" style="display:none;"><p>where</p></div>';

		$dagsOptions = $this->makeDagsListingOptions();
		if (SUPER_USER || ($dagGroupFound == false)) {
			$strDivInputFields .= '<div id="dagsList">DAGs List: '.'<span id="dagsOptions">'.js_escape($dagsOptions).'</span>'.'</div>'.'<hr>'; 
		}
		
		$strDivInputFields .= '<div id="hashcodefields">First Name &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<input id="hash_fname" type="text" name="firstname"><br>';
		$strDivInputFields .= 'Last  Name &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;<input id="hash_lname" type="text" name="lastname"><br>';

		$strDivInputFields .= 'Birthdate (mm-dd-yyyy) <input autocomplete="off" aria-labelledby="label-hash_dob" value="" id="hash_dob" type="text" name="hash_dob" onblur="redcap_validate(this,'."\'\'".','."\'\'".','."\'".'soft_typed'."\'".','."\'".'date_mdy'."\'".',1);"  />'; 

		$calImageButton = '';
		if (substr_count(APP_PATH_DOCROOT, '/redcap/redcap_v')) { // REDCap DEV QA PROD servers
			$calImageButton = '/redcap'; // servers have this path level
		} 
		$calImageButton .= '/redcap_v'.$redcap_version.'/Resources/images/date.png';

		$strDivInputFields .= '<br><br>Current DAG <input id="hash_dag_name" readonly="readonly" type="text" name="dagname" value="' . $dagGroupName . '" />'; 
		$strDivInputFields .= '<br>Location Intl. <input id="hash_dag_location_flag" onclick="return false;" type="checkbox" name="hashdaglocation" ' . ($dagGroupIsInternational ? 'checked="checked"' : '') . ' /><br><br></div>'; 
		$strDivInputFields .= '</div>'; 
		
		$xjs .= <<<EOD_2


$(document).ready(function(){

	wipeAddRecButton();

	$( function() {
    $( "#hash_dob" ).datepicker({dateFormat: 'mm-dd-yy',
    	changeMonth: true,
    	changeYear: true,
    	showOn: "button",
    	buttonImage: "{$calImageButton}",
    	buttonImageOnly: true,
    	buttonText: "",
    	yearRange: "-100:+0"
    	});
  } );

});

function checklocus(projectId)
{
	var dagItem   = $('#selectDagsListing').val().trim();
	var showwhere = 'blank';
	var result = 0;
	var pid = projectId;
	var urlLocation = "{$pathToAjaxFunctionCheckInternational}" + '&dagcode=' + dagItem;
	
	$.ajax({
		url: urlLocation,
	 
		success: function(result) {
				if (result == 1) {
					showwhere = 'international';
					document.getElementById("hash_dag_location_flag").checked = true;
					} else {
					showwhere = 'domestic';
					document.getElementById("hash_dag_location_flag").checked = false;
				}

			$("#hash_dag_name").val(dagItem);
		}
	});	
}

function processHashRequest(projectId)
{
		var pid = projectId;
		var hashCode = 0;
		var hashCodeVal = '';
		var msg = '';
		
		var dataFirstName   =  '';
		var dataLastName    =  '';
		var dataDateOfBirth =  '';
		var dagName =  '';
		
		dataFirstName   = $('#hash_fname').val().trim();
		dataLastName    = $('#hash_lname').val().trim();
		dataDateOfBirth = $('#hash_dob').val().trim();
		
		var hasDataFlag = false;
		var hasDataCount = 0;
		if (dataFirstName.length > 0) {
			hasDataCount += 1;
		}
		if (dataLastName.length > 0) {
			hasDataCount += 1;
		}
		if (dataDateOfBirth.length > 0) {
			hasDataCount += 1;
		}
		
		if (hasDataCount >= 3) {
			hasDataFlag = true;
		}
		
		if (hasDataFlag == false) {
			msg = '{$msgProcessHashRequest}';
			$("#msgHashCode").show();
			$("#msgHashCode").html(msg);
			return;
		}
		
		if (dataDateOfBirth.indexOf("/")) {
			dataDateOfBirth.replace('/', '-');
		}
				
		hashCode = createHash(dataFirstName, dataLastName, dataDateOfBirth);
		
		if (dataFirstName.includes('fail')) {
			hashCode = 'fail';
		}
		if (dataFirstName.includes('pass')) {
			hashCode = 'pass';
		}

		if (hashCode == 0) {
			msg = '{$msgProcessHashRequest}';
			$("#msgHashCode").show();
			$("#msgHashCode").html(msg);
		} else {
			$("#msgHashCode").hide();

			$.ajax({url: "{$pathToAjaxFunction}" + '&hashcode=' + hashCode, success: function(result) {
				dataFirstName   = $('#hash_fname').val().trim();
				dataLastName    = $('#hash_lname').val().trim();
				dataDateOfBirth = $('#hash_dob').val().trim();
				
				dagName = $('#hash_dag_name').val().trim();
		
				var flagExistingHashCodeRecord = false;
				var strparts = result.split(',');
				var recordId = strparts[0];
				var dagNameResult = strparts[1];

				if ((recordId > '') && (recordId != 0)) {
					flagExistingHashCodeRecord = true;
					// Record exists
					existingRecord(recordId, dagNameResult);
				} else {
					// Make the record
					var isInternationalFlag = ($("#hash_dag_location_flag").is(":checked") ? '1' : '0');

					allowNewRecord(pid, dataFirstName, dataLastName, dataDateOfBirth, isInternationalFlag, hashCode, dagName);
				}
		}});
	}
}

function wipeAddRecButton()
{
	 var buttonTochange = findAddRecButton();

	$(buttonTochange).replaceWith('{$strDivInputFields}<div id="div_hash_button_wusmaddrec" ><button id="wusmaddrecbutton" onclick="processHashRequest({$this->projectId}); return false;">{$btnProcessHashRequestSave}</button></div><div id="div_hash_button_create" class="col-*-12" style="display:none;" ><button id="hash_button_create" class="btn btn-danger small" type="button" onclick="createNewRecord();" >{$btnProcessHashRequestCreate}</button></div>');
}

function createHash(valFirstName, valLastName, valDateOfBirth) 
{
	var flag = 0;
	var hashCode = 0;

	if (valFirstName) {
		flag += 1;
	}
	
	if (valLastName) {
		flag += 1;
	}
	
	if (valDateOfBirth) {
		flag += 1;
	}

	if (flag >= 3) {
		var tmp = valFirstName + valLastName + valDateOfBirth;
		
		var shaObj = new jsSHA(tmp.trim(), "ASCII"); 
		
		hashCode = shaObj.getHash("SHA-512","HEX");
	}

	return hashCode;
}

function findAddRecButton()
{
	var foundButton;
		
	$('.data').find('button').filter(function(){if($(this).text().trim() === "Add new record"){ foundButton = $(this); }});
	
	return foundButton;
}

function createNewRecord()
{
}

function createNewRecordAjax(pid, firstName, lastName, dateOfBirth, isInternational, hashCode, dagId)
{
	var createRecordUrl = '{$pathToAjaxCreateRecordFunction}';
	var createRecordUrl2 = createRecordUrl + '&firstName=' + firstName + '&lastName=' + lastName + '&dateOfBirth=' + dateOfBirth + '&isInternational=' + isInternational + '&hashCode=' + hashCode + '&dagId=' + dagId;

	$.ajax({
		url: createRecordUrl2,
	 
		success: function(result) {
			if (result > '') {  // success
				var pageName = '{$instrumentName}';
				var eventId = '{$projectInstrumentEventId}';
				var homepageurl = '{$pathBaseDataEntryIndex}' + '?pid='+pid+'&id='+result+'&page='+pageName+'&event_id='+eventId;
				location.replace(homepageurl);
				
			} else {  // fail
				$("#hashcodemsg").html('Failure');
				$("#hashcodemsg").show();
			}
		}
	});	
}

function allowNewRecord(pid, dataFirstName, dataLastName, dataDateOfBirth, isInternationalFlag, hashCode, dagId)
{
	updateAddRecButtonToAddRecord(pid, dataFirstName, dataLastName, dataDateOfBirth, isInternationalFlag, hashCode, dagId);
}

function existingRecord(recordId, dagName)
{
	$("#hashcodefields").hide();
	$("#div_hash_button_wusmaddrec").hide();
	$("#dagsList").hide();
	$("#msgShowLocus").hide();

	var msgExisting = '{$msgExistingRecord}';
	
	msgExisting = msgExisting.replace('recordid', recordId);
	msgExisting = msgExisting.replace('dagname', dagName);

	$("#msgHashCode").show();
	$("#msgHashCode").html(msgExisting);	
}

function findHashCode()
{
	alert('findHashCode not created yet');
	$("#div_hash_button_wusmaddrec").show();
}

function updateAddRecButtonToAddRecord(pid, dataFirstName, dataLastName, dataDateOfBirth, isInternationalFlag, hashCode, dagId)
{
	$("#div_hash_button_wusmaddrec").hide();
	
	$("#hashcodefields").hide();
	$("#dagsList").hide();
	$("#msgShowLocus").hide();
	
	createNewRecordAjax(pid, dataFirstName, dataLastName, dataDateOfBirth, isInternationalFlag, hashCode, dagId);
}
EOD_2;

		$js .= $xjs;

		$js .= $nl;
		
		return $js;
	}

	/**
	 * generateJqueryBase - .
	 */
	public function generateJqueryBase($projectId)
	{
		$js = '';
		$nl = "\n";

		$js .= '<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>';
		$js .= $nl;

		return $js;
	}

	/**
	 * generateJs - hook redcap_every_page_top.
	 */
	public function generateJs($projectId, $instrument, $event_id)
	{
		$js = '';
		$nl = "\n";

		$js .= '<script type="text/javascript">';
		$js .= $nl;
		
		$js .= $this->makeJsHashCalc();
		$js .= $nl;

		$js .= $this->makeJsCustomCode($projectId, $instrument, $event_id);
		$js .= $nl;

		$js .= '</script>';
		$js .= $nl;
		
		return $js;
	}

} // *** end class

?>
