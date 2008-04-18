<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Franz Holzinger <kontakt@fholzinger.com>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once (t3lib_extMgM::extPath('paymentlib').'lib/class.tx_paymentlib_provider.php');

class tx_paymentlibsaferpay_provider extends tx_paymentlib_provider {

	protected $providerKey = 'saferpay';		// Identifier for this provider implementation
	protected $transactionTypes = array (		// Provider specific transaction type keys
		TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER => 'a',
	);

	protected $formActionURI = 'https://webservices.primerchants.com/billing/TransactionCentral/EnterTransaction.asp?';		// Action URI for the Transaction Central "silent mode" via POST 
	protected $paymentMethod = '';			// Contains the key of the currently selected payment method
	protected $callingExtensionKey = '';		// Extension key of the extension using the paymentlib. Used to identify the extension which triggered a transaction
	protected $action = 0;				// The currently selected action
	protected $processed = FALSE;			// TRUE if this transaction has been processed
	protected $resultArr = array();			// Result of the transaction if it has been processed. Access this via transaction_getResults();

	protected $accountDataArr;			// Account data for connecting to Transaction Central gateway, defined in the Extension Manager configuration
	protected $paymentDataArr;			// Payment data for the current transaction, set by transaction_setDetails()
	protected $transactionDataArr;			// Transaction data for the current transaction, set by transaction_setDetails()
	protected $optionsArr;				// Additional options for the current transaction, set by transaction_setDetails()
	var $libObj;					// Pay Suite Library object
	var $conf;


	public function __construct () {
		$extensionManagerConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['paymentlib_saferpay']);
		$this->accountDataArr = array (
			'accountid' => $extensionManagerConf['accountid'],
			'trxuserid' => $extensionManagerConf['trxuserid'],
			'trxpassword' => $extensionManagerConf['trxpassword'],
			'execPath' => $extensionManagerConf['execPath'],
		);
		$this->conf['scriptIsCertified'] = $extensionManagerConf['scriptiscertified']; // Unless set to TRUE via the Extension Manager, all SOAP functionaly will be turned off because it needs certification by VISA / Mastercard

		$this->conf['faillink'] = $extensionManagerConf['faillink'];
		$this->conf['successlink'] = $extensionManagerConf['successlink'];
		$this->conf['useClient'] = $extensionManagerConf['useClient'];
		$this->conf['useCurl'] = $extensionManagerConf['useCurl'];
		$this->conf['provideruri'] = $extensionManagerConf['provideruri'];

		if (t3lib_extMgm::isLoaded(PAYSUITE_EXTkey)) {
			include_once (PATH_BE_paysuite.'lib/class.tx_paysuite_lib.php');
			$this->libObj = &t3lib_div::makeInstance('tx_paysuite_lib');
			$this->libObj->init($this->providerKey);
		}
	}


	/********************************************
	 *
	 * tx_pamyentlib_provider API implementation
	 *
	 ********************************************/

	/**
	 * Returns a configuration array of available payment methods.
	 *
	 * @return	array		Supported payment methods
	 * @access	public
	 */
	 public function getAvailablePaymentMethods () {
	 	$methodXml = t3lib_div::getUrl(t3lib_extMgm::extPath ('paymentlib_saferpay').'paymentmethods.xml');
	 	$rc = t3lib_div::xml2array ($methodXml);
	 	return $rc;
	 }


	/**
	 * Returns the provider key
	 *
	 * @return	string		Provider key
	 * @access	public
	 */
	 public function getProviderKey () {
	 	return $this->providerKey;
	 }


	public function &getLibObj()	{
		return $this->libObj;
	}


	/**
	 * Returns TRUE if the payment implementation supports the given gateway mode.
	 *
	 * @param	integer		$gatewayMode: The gateway mode to check for. One of the constants TX_PAYMENTLIB_GATEWAYMODE_*
	 * @return	boolean		TRUE if the given gateway mode is supported
	 * @access	public
	 */
	public function supportsGatewayMode ($gatewayMode) {
		$rc = FALSE;
		switch ($gatewayMode) {
			case TX_PAYMENTLIB_GATEWAYMODE_FORM :
				$rc = TRUE;
				break;
			case TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE :
				$rc = ($this->conf['scriptIsCertified'] ? TRUE : FALSE);
				break;
			default:
				// nothing
				break;
		}

		return $rc;
	}


	/**
	 * Initializes a transaction.
	 *
	 * @param	integer		$action: Type of the transaction, one of the constants TX_PAYMENTLIB_TRANSACTION_ACTION_*
	 * @param	string		$paymentMethod: Payment method, one of the values of getSupportedMethods()
	 * @param	integer		$gatewayMode: One of the constants TX_PAYMENTLIB_GATEWAYMODE_*
	 * @param	string		$callingExtKey: Extension key of the calling script.
	 * @return	boolean		TRUE if initialisation was successful 
	 * @access	public
	 */
	public function transaction_init ($action, $paymentMethod, $gatewayMode, $callingExtKey) {
		$rc = FALSE;
		if ($this->supportsGatewayMode ($gatewayMode))	{
			$rc = TRUE;
			$this->action = $action;
			$this->paymentMethod = $paymentMethod;
			$this->gatewayMode = $gatewayMode;
			$this->callingExtensionKey = $callingExtKey;
	
			unset ($this->paymentDataArr);
			unset ($this->transactionDataArr);
			unset ($this->optionsArr);
		}

		return $rc;
	 }

	/**
	 * Sets the payment details. 
	 *
	 * @param	array		$detailsArr: The payment details array
	 * @return	boolean		Returns TRUE if all required details have been set
	 * @access	public
	 * @TODO	Check fields depending on $this->action! (Currently applies to AUTH) Refactor!
	 */
	public function transaction_setDetails ($detailsArr) {
	
		$this->processed = FALSE;
		$ok = FALSE;

		$this->conf['redirectURI'] = $detailsArr['transaction']['returi']; // URI used for redirect back from the Transaction Central server. Will be set in the constructor or can be modified from outside 
		if (!$this->conf['faillink'])	{
			$this->conf['faillink'] = $detailsArr['transaction']['faillink'];
		}
		if (!$this->conf['successlink'])	{
			$this->conf['successlink'] = $detailsArr['transaction']['successlink'];
		}

		if ($this->gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE) {
		} elseif ($this->gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_FORM) {

			switch ($this->paymentMethod) {
				case 'paymentlib_saferpay_all':
					$ok = (
						is_array ($detailsArr['transaction']) &&
							intval($detailsArr['transaction']['amount']) &&
							strlen($detailsArr['transaction']['currency'])
					);

					if ($ok) {
						$this->orderId = strval($detailsArr['transaction']['orderuid']);
						$this->transactionId =
							$this->libObj->createUniqueID(strval($detailsArr['transaction']['orderuid']), $this->callingExtension);

						$this->transactionDataArr = array (
							'trxAmount' => $detailsArr['transaction']['amount'] * 100,
							'trxCurrency' => $detailsArr['transaction']['currency'],
							'invoiceText' => $detailsArr['transaction']['invoicetext'],
							'trxUserComment' => $detailsArr['transaction']['comment'],
						);
						$this->optionsArr = array (
							'shopperId' => $detailsArr['options']['reference'],
						);
					}
				break;
			}
		}
		return $ok;
	}


	/**
	 * Validates the transaction data which was set by transaction_setDetails().
	 * $level determines how strong the check is, 1 only checks if the data is
	 * formally correct while level 2 checks if the credit card or bank account
	 * really exists.
	 *
	 * @return	boolean		TRUE if validation was successful
	 * @access	public
	 */
	public function transaction_validate ($level=1) {
		return FALSE;
	}


	/**
	 * processing after successful transaction
	 *
	 * @return	boolean		FALSE
	 * @access	public
	 */
	public function transaction_failed () {
		return FALSE;
	}


	/**
	 * processing after successful transaction
	 *
	 * @return	boolean		FALSE
	 * @access	public
	 */
	public function transaction_succeded () {
		return FALSE;
	}


	/**
	 * Submits the prepared transaction to the payment gateway via SOAP
	 *
	 * @return	boolean		TRUE if transaction was successul, FALSE if not. The result can be accessed via transaction_getResults()
	 * @access	public
	 */
	public function transaction_process () {
		return FALSE;
	}


	/**
	 * Returns the form action URI to be used in mode TX_PAYMENTLIB_GATEWAYMODE_FORM.
	 *
	 * @return	string		Form action URI
	 * @access	public
	 */
	public function transaction_formGetActionURI () {
		$rc = FALSE;
		if ($this->gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_FORM)	{

			if ($this->conf['useClient'] == 1)	{
				$attributes = array('-a', 'AMOUNT', $this->transactionDataArr['trxAmount'],
					'-a', 'CURRENCY', $this->transactionDataArr['trxCurrency'],
					'-a', 'DESCRIPTION', '"Purchase with TYPO3 Payment Library Extension called by extension '.$this->callingExtensionKey.'"',
					'-a', 'ALLOWCOLLECT', 'no',
					'-a', 'DELIVERY', 'no',
					'-a', 'ACCOUNTID', '\''.$this->accountDataArr['accountid'].'\'', //  "99867-94913822"
					'-a', 'BACKLINK', '\''.$this->conf['redirectURI'].'\'',
					'-a', 'FAILLINK', '\''.$this->conf['faillink'].'\'',
					'-a', 'SUCCESSLINK', '\''.$this->conf['successlink'].'\''
				);

				$strAttributes = implode(' ', $attributes);
				$confPath = "/usr/local/saferpay/customers/berncity-test/"; /* maybe another path */
				$command = $this->accountDataArr['execPath']."saferpay -payinit -p ".$confPath." ".$strAttributes;
				/* get the payinit URL */
				$fp = popen($command, "r");
				$payinit_url = fgets($fp, 4096);
				$rc = $payinit_url;
			} else {
				$attributes = array(
					'AMOUNT' => $this->transactionDataArr['trxAmount'],
					'CURRENCY' => $this->transactionDataArr['trxCurrency'],
					'DESCRIPTION' => '"Purchase with TYPO3 Payment Library Extension called by extension '.$this->callingExtensionKey.'"',
					'ALLOWCOLLECT' => 'no',
					'DELIVERY' => 'no',
					'ACCOUNTID' => $this->accountDataArr['accountid'], //  "99867-94913822"
					'BACKLINK' => $this->conf['redirectURI'],
					'FAILLINK' => $this->conf['faillink'],
					'SUCCESSLINK' => $this->conf['successlink']
				);

				$strAttributesArray = array();
				foreach ($attributes as $k => $v)	{
					$strAttributesArray[] = $k . '=' . rawurlencode($v);
				}
				$strAttributes = implode ('&', $strAttributesArray);
				$confPath = $this->conf['provideruri']; /* maybe another path */
				$strUrl = $confPath.'CreatePayInit.asp?'.$strAttributes;

				/* get the PayInit URL from the hosting server */

				if ($this->conf['useCurl'] == 1)	{
					//CURL Session initialisieren
					$ch = curl_init($strUrl);
					curl_setopt($ch, CURLOPT_PORT, 443);
					// Prüfung des SSL-Zertifikats abschalten (SSL ist dennoch sicher)
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
					//Session Optionen setzen
					// kein Header in der Ausgabe
					curl_setopt($ch, CURLOPT_HEADER, 0);
					// Rückgabe schalten
					curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
					//Ausführen der Aktionen
					$payinit_url = curl_exec($ch);
					//Session beenden
					curl_close($ch); 
				} else {
					/* get the payinit URL */
					$payinit_url = implode('', file($strUrl));
				}
				$rc = $payinit_url;
			}
		}

		if (strstr ($rc, 'ERROR') != FALSE)	{
			$rc .= '<br>called url: '.$strUrl;
		}
		return $rc;
	}


	/**
	 * Returns any extra parameter for the form tag to be used in mode TX_PAYMENTLIB_GATEWAYMODE_FORM.
	 *
	 * @return  string      Form tag extra parameters
	 * @access  public
	 */
	public function transaction_formGetFormParms ()	{
		return '';
	}


	/**
	 * Returns any extra parameter for the form submit button to be used in mode TX_PAYMENTLIB_GATEWAYMODE_FORM.
	 *
	 * @return  string      Form submit button extra parameters
	 * @access  public
	 */
	public function transaction_formGetSubmitParms () {
	}


	/**
	 * Returns an array of field names and values which must be included as hidden
	 * fields in the form you render use mode TX_PAYMENTLIB_GATEWAYMODE_FORM.
	 *
	 * @return	array	Field names and values to be rendered as hidden fields
	 * @access	public
	 */
	public function transaction_formGetHiddenFields () {
		global $TSFE;
		
		if ($this->gatewayMode != TX_PAYMENTLIB_GATEWAYMODE_FORM) return FALSE;

			// Set key for payment type (credit card or ELV):
		switch ($this->paymentMethod) {
			case 'paymentlib_saferpay_all':
				$paymentType = 'all';
			break;
		}

			// Build security hash:
		$securityHash = md5 ($this->accountDataArr['trxuserid'] . $this->transactionDataArr['trxAmount'] . $this->transactionDataArr['trxCurrency'] . $this->accountDataArr['trxpassword'] );

			// Create array of hidden fields:
		$hiddenFieldsArr = array (
			'tx_paymentlib_saferpay_extkey' => $this->callingExtensionKey,
			'trxuser_id' => $this->accountDataArr['trxuserid'],
			'trxpassword' => $this->accountDataArr['trxpassword'],
			'trx_amount' => $this->transactionDataArr['trxAmount'],
			'trx_currency' => $this->transactionDataArr['trxCurrency'],
			'trx_typ' => $this->transactionTypes[$this->action],
			'trx_paymenttyp' => $paymentType,
			'trx_securityhash' => $securityHash,
			'shopper_id' =>  $this->optionsArr['shopperId'],
			'invoicetext' => $this->transactionDataArr['invoicetext'],
			'silent' => 1,
			'redirect_url' => $this->conf['redirectURI'],
			'noparams_on_redirect_url' => 1,
			'noparams_on_error_url' => 0,
		);

		return $hiddenFieldsArr;
	}


	/**
	 * Returns an array of field names and their configuration which must be rendered
	 * for submitting credit card numbers etc.
	 *
	 * The configuration has the format of the TCA fields section and can be used for
	 * rendering the labels and fields with by the extension frontendformslib
	 *
	 * @return	array		Field names and configuration to be rendered as visible fields
	 * @access	public
	 */
	public function transaction_formGetVisibleFields () {
		if ($this->gatewayMode != TX_PAYMENTLIB_GATEWAYMODE_FORM) return FALSE;

		$paymentMethodsArr = t3lib_div::xml2array (t3lib_div::getUrl(t3lib_extMgm::extPath ('paymentlib_saferpay').'paymentmethods.xml'));
		return $paymentMethodsArr[$this->paymentMethod]['fields'];
	}


	/**
	 * Returns the results of a processed transaction
	 *
	 * @param	string		$reference
	 * @return	array		Results of a processed transaction
	 * @access	public
	 */
	public function transaction_getResults ($reference)	{
		global $LANG;

			// In FORM mode, the result will be created on demand, the transaction data
			// should be set on beforehand.
		if ($this->gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_FORM) {
		} else if ($this->gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_FORM) {
			
		}
		return $this->resultsArr;
	}


	/**
	 * Returns the message of a processed transaction
	 *
	 * @return	string		Results
	 * @access	public
	 */
	public function transaction_message () {
		global $TSFE;
		global $LANG;
		//rc mit einbinden	
		$message = $TSFE->sL ('LLL:EXT:paymentlib_saferpay/locallang.php:'.$this->resultArr['posherr']);
		if (!strlen ($message) && $this->resultArr['rmsg']=='') {
			$message = $TSFE->sL ('LLL:EXT:paymentlib_saferpay/locallang.php:errormessage_general');
		}
		elseif($this->resultArr['posherr']=="100" && $this->resultArr['rc']!=''){
			$message = $TSFE->sL ('LLL:EXT:paymentlib_saferpay/locallang.php:R'.$this->resultArr['rc']);
		}
		if($message==''){
			$message= $this->resultArr['rmsg'];
		}

		return $message;
	}


	/****************************************
	 *
	 * Additional methods
	 *
	 ******************************************/

	/**
	 * Sets the account data for connecting to the Transaction Central gateway
	 *
	 * @param	array		accountDataArr: The account data. Keys: 'accountId', 'trxuserid', 'trxpassword'
	 * @return	void
	 * @access	public
	 */
	 public function setAccountData ($accountDataArr) {
	 	$this->accountDataArr = $accountDataArr;
	 }


	/**
	 * Generates the items list for rendering the expiry date year selector box
	 *
	 * @return	array		Supported payment methods
	 * @access	public
	 */
	public function itemProcFunc_ccexpdateyear_items (&$paramsArr, &$pObj) {
		$paramsArr['items'] = array();
		$todayArr = getdate();
		for ($year=$todayArr['year']; $year < ($todayArr['year']+10); $year++) {
			$paramsArr['items'][] = array ($year, $year);
		}
	}
}

?>