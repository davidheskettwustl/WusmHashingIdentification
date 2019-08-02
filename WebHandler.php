<?php
/// A trait adding shortcut REDCap standard logging.
/**
 *  WebHandler 
 *  - TRAIT for  - .
 *    + key functions
 *       * showJson()

 *  - The project (say, ), has 
 *
 *  - WUSM - Washington University School of Medicine. 
 * @author David L. Heskett
 * @version 1.0
 * @date 20180117
 * @copyright &copy; 2018 Washington University, School of Medicine, Institute for Infomatics <a href="https://redcap.wustl.edu">redcap.wustl.edu</a>
 * @todo Further documentation done to all the methods and so on should be done sometime.
 * @todo .
 */

namespace WashingtonUniversity\HashingIdentificationExternalModule;

/**
 * WebHandler - a trait to some simple json and web details.
 */
trait WebHandler 
{

	/**
	 * showJson - show a json parsable page.
	 */
	public function showJson($rsp) 
	{
		$jsonheader = 'Content-Type: application/json; charset=utf8';
		header($jsonheader);
		echo $rsp;
	}

} // *** end trait

?>
