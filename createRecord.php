<?php
namespace WashingtonUniversity\HashingIdentificationExternalModule;

require_once APP_PATH_DOCROOT . '/Config/init_global.php';

include_once 'HashingIdentificationExternalModule.php';

$pid = 0;
if (isset($_GET['pid'])) {
	$pid = 0 + $_GET['pid'];  // the pid as a number (makes a difference to the json data conversion)
}
$projectId   = ($pid ? $pid : 0);
$_GET['pid'] = $projectId;

if ($pid) {
	
	$recordId    = '';
	$firstName   = '';
	$lastName    = '';
	$dateOfBirth = '';
	$eventId     = '';
	$instrument  = '';
	$hashCode    = '';
	$isInternational = '';
	$dagId = '';

	if (isset($_GET['firstName'])) {
		$firstName = $_GET['firstName'];  // 
	}
	if (isset($_GET['lastName'])) {
		$lastName = $_GET['lastName'];  // 
	}
	if (isset($_GET['dateOfBirth'])) {
		$dateOfBirth = $_GET['dateOfBirth'];  // 
	}
	if (isset($_GET['isInternational'])) {
		$isInternational = $_GET['isInternational'];  // 
	}
	if (isset($_GET['eventId'])) {
		$eventId = $_GET['eventId'];  // 
	}
	if (isset($_GET['instrument'])) {
		$instrument = $_GET['instrument'];  // 
	}
	if (isset($_GET['hashCode'])) {
		$hashCode = $_GET['hashCode'];  // 
	}
	if (isset($_GET['dagId'])) {
		$dagId = $_GET['dagId'];  // 
	}

	$testHashingIdentificationExternalModule = new HashingIdentificationExternalModule($pid);

	$newRecordId = $testHashingIdentificationExternalModule->makeNewRecord($projectId, $recordId, $eventId, $instrument, $firstName, $lastName, $dateOfBirth, $hashCode, $dagId, $isInternational);

	if ($testHashingIdentificationExternalModule->debugLogFlag) {
		$newRecordIdStatus = ($recordId ? 'Exists' : 'Not Found');

		$msg = 'Record ID: ' . $recordId;
		$msg .= ', ';
		$msg = 'status ' .  $newRecordIdStatus;
		$msg .= ', ';
		$msg .= 'projectId ' . $projectId;
		$msg .= ', ';
		$msg .= 'eventId ' . $eventId;
		$msg .= ', ';
		$msg .= 'instrument ' . $instrument;
		$msg .= ', ';
		$msg .= 'firstName ' . $firstName;
		$msg .= ', ';
		$msg .= 'lastName ' . $lastName;
		$msg .= ', ';
		$msg .= 'dateOfBirth ' . $dateOfBirth;
		$msg .= ', ';
		$msg .= 'dagId ' . $dagId;
		$msg .= ', ';
		$msg .= 'isInternational ' . $isInternational;
		$msg .= ', ';
		$msg .= 'hashCode ' . $hashCode;
		$msg .= ' ';

		$testHashingIdentificationExternalModule->debugLog($msg, $msg);
	}	

	echo $newRecordId;
}

?>
