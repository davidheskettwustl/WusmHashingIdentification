<?php
/// A trait adding shortcut REDCap standard logging.
/**
 *  LoggingHandler 
 *  - TRAIT for  - .
 *    + key functions
 *       * debugLog()

 *  - The project (say, ), has 
 *
 *  - WUSM - Washington University School of Medicine. 
 * @author David L. Heskett
 * @version 1.0
 * @date 20180117
 * @copyright &copy; 2018 Washington University, School of Medicine, Institute for Infomatics <a href="https://redcap.wustl.edu">redcap.wustl.edu</a>
 * @todo Further documentation done to all the methods and so on should be done sometime.
 * @todo need to handle the PROJECT_ID define somehow.
 */

namespace WashingtonUniversity\HashingIdentificationExternalModule;

use Logging;

/**
 * LoggingHandler - a trait to handle authorization key.
 */
trait LoggingHandler 
{

	/**
	 * debugLog - (debug version) Simplified Logger messaging.
	 */
	public function debugLog($msg = '', $logDisplayMsg = 'HashingTesting')
	{
		// $sql, $table, $event, $record, $display, $descrip="", $change_reason="",
		//									$userid_override="", $project_id_override="", $useNOW=true, $event_id_override=null, $instance=null
		$logSql         = '';
		$logTable       = '';
		$logEvent       = 'OTHER';  // what events can we have? ENUM('UPDATE', 'INSERT', 'DELETE', 'SELECT', 'ERROR', 'LOGIN', 'LOGOUT', 'OTHER', 'DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE', 'MANAGE', 'LOCK_RECORD', 'ESIGNATURE')
		$logRecord      = '';
		$logDisplay     = $logDisplayMsg; // data_values  text
		$logDescription = $msg;
		
		Logging::logEvent($logSql, $logTable, $logEvent, $logRecord, $logDisplay, $logDescription);
	}

} // *** end trait

?>
