<?php
namespace WashingtonUniversity\HashingIdentificationExternalModule;

require_once APP_PATH_DOCROOT . '/Config/init_global.php';

include_once 'HashingIdentificationExternalModule.php';

$testHashingIdentificationExternalModule = new HashingIdentificationExternalModule();

$projectId = '';
if (isset($_GET['pid'])) {
	$projectId = $_GET['pid'];
}

$dagcode = '';
if (isset($_GET['dagcode'])) {
	$dagcode = $_GET['dagcode'];
}

$flag = $testHashingIdentificationExternalModule->isDagInternational($dagcode);

if ($testHashingIdentificationExternalModule->debugLogFlag) {
	$status = ($flag ? 'International' : 'Domestic');

	$msg = 'Dag code ' . $dagcode . ' is ' .  $status;
	$testHashingIdentificationExternalModule->debugLog($msg, $msg);
}

echo $flag;  // true = 'International', false = 'Domestic'

?>
