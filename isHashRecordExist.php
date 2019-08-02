<?php
namespace WashingtonUniversity\HashingIdentificationExternalModule;

require_once APP_PATH_DOCROOT . '/Config/init_global.php';
include_once 'HashingIdentificationExternalModule.php';

$testHashingIdentificationExternalModule = new HashingIdentificationExternalModule();

$projectId = '';
if (isset($_GET['pid'])) {
	$projectId = $_GET['pid'];
}

$hashcode = '';
if (isset($_GET['hashcode'])) {
	$hashcode = $_GET['hashcode'];
}

$hashData = $testHashingIdentificationExternalModule->checkExistingHash($projectId, $hashcode);

if ($testHashingIdentificationExternalModule->debugLogFlag) {
	$msg = 'hash code ' . $hashcode . ' status ' .  ($hashData ? 'Exists' : 'Not Found');
	$testHashingIdentificationExternalModule->debugLog($msg, $msg);
}

echo $hashData;

?>
