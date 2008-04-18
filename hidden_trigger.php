<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Robert Lemke <robert@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Called by the ipayment gateway in case of a successful transaction.
 * The transaction data will be stored in a transaction record in table
 * tx_paymentlib_transactions
 *
 * @author		Robert Lemke <robert@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   75: class tx_paymentlib_transcentral_hiddentrigger
 *   82:     function main()
 *
 * TOTAL FUNCTIONS: 1
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

error_reporting (E_ALL ^ E_NOTICE);

define('TYPO3_OS', stristr(PHP_OS,'win')&&!stristr(PHP_OS,'darwin')?'WIN':'');
define('TYPO3_MODE','FE');
define('PATH_thisScript',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):($_SERVER['ORIG_SCRIPT_FILENAME']?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));

define('PATH_site', dirname(PATH_thisScript).'/../../../');
define('PATH_t3lib', PATH_site.'t3lib/');
define('PATH_tslib', PATH_site.'tslib/');
define('PATH_typo3conf', PATH_site.'typo3conf/');
define('TYPO3_mainDir', 'typo3/');

require_once(PATH_t3lib.'class.t3lib_div.php');
require_once(PATH_t3lib.'class.t3lib_extmgm.php');
require_once(PATH_t3lib.'config_default.php');
if (!defined ('TYPO3_db')) 	die ('The configuration file was not included.');

require_once(PATH_t3lib.'class.t3lib_db.php');
$TYPO3_DB = t3lib_div::makeInstance('t3lib_DB');
$TYPO3_DB->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password);

/**
 * Script Class, instantiated in the bottom of this script.
 *
 * @author	Robert Lemke <robert@typo3.org>
 * @package TYPO3
 * @subpackage paymentlib_transcentral
 */
class tx_paymentlib_transcentral_hiddentrigger {

	/**
	 * Main function which creates the transaction record
	 *
	 * @return	void
	 */
	function main()	{
		global $TYPO3_DB, $TSFE, $TYPO3_CONF_VARS;

		$paymentEMConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['paymentlib']);
		$ipaymentEMConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['paymentlib_transcentral']);

		if (t3lib_div::getIndpEnv ('REMOTE_ADDR') != $ipaymentEMConf['hiddentriggerremoteip']) die();


		list ($hour, $minute, $second) = explode (':', t3lib_div::_GP('ret_transtime'));
		list ($day, $month, $year) = explode ('.', t3lib_div::_GP('ret_transdate'));
		$remoteTimestamp =  mktime ($hour, $minute, $second, $month, $day, $year);

		$remoteMessagesArr = array (
			t3lib_div::_GP ('ret_errormsg'),
			t3lib_div::_GP ('ret_additionalmsg'),
		);

		$fields = array (
			'crdate' => time(),
			'pid' => $paymentEMConf['pid'],
			'status' => (t3lib_div::_GP('ret_errorcode') == 0 ? 'booked' : 'failed'),
			'amount' => intval(t3lib_div::_GP('trx_amount')),
			'currency' => t3lib_div::_GP('trx_currency'),
			'invoicetext' => t3lib_div::_GP('invoicetext'),
			'remotebookingnr' => t3lib_div::_GP('ret_booknr'),
			'remoteauthcode' => t3lib_div::_GP('ret_authcode'),
			'remotetimestamp' => $remoteTimestamp,
			'remoteerrorcode' => t3lib_div::_GP('ret_errorcode'),
			'remotemessages' => serialize ($remoteMessagesArr),
			'extkey' => t3lib_div::_GP('tx_paymentlib_transcentral_extkey'),
			'extreference' => t3lib_div::_GP('shopper_id'),
		);

		$dbResult = $TYPO3_DB->exec_INSERTquery (
			'tx_paymentlib_transactions',
			$fields
		);

		if (t3lib_div::_GP('tx_paymentlib_transcentral_returnurl')) {
			header('Location: '.t3lib_div::locationHeaderUrl(t3lib_div::_GP('tx_paymentlib_transcentral_returnuri').'?'.$parameters));
		}
	}
}

	// Make instance and call main():
$SOBE = t3lib_div::makeInstance('tx_paymentlib_transcentral_hiddentrigger');
$SOBE->main();



?>