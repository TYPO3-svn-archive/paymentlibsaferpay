<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005 Robert Lemke (robert@typo3.org)
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * @author	Robert Lemke <robert@typo3.org>
 */

require_once (t3lib_extMgm::extPath ('paymentlib').'/lib/class.tx_paymentlib_providerfactory.php');
require_once 'PHPUnit2/Framework/TestCase.php';

class tx_paymentlibipayment_provider_testcase extends PHPUnit2_Framework_TestCase {

	protected $providerClass = 'tx_paymentlibipayment_provider';
	protected $providerKey = 'ipayment';
	
	public function __construct ($name) {
		parent::__construct ($name);
	}

	public function setUp () {
		$this->getProviderObj()->setAccountData (
			array(
				'accountId' => 99999,
				'trxuserId' => 99999,
				'trxpassword' => 0,
			)
		);

		include ('fixtures/tx_paymentlibtcentral_provider_fixture.inc');
	}
	
	public function test_getProviderObjects() {		
		self::assertTrue($this->getProviderObj()->getProviderKey() == $this->providerKey, "tx_paymentlib_providerfactory->getProviderObjects() did not return an object for my class $this->providerClass. Maybe it's not correctly registered in ext_tables.php?");
	}

	public function test_checkConnection() {
		self::assertTrue ($this->getProviderObj()->checkConnection() !== FALSE, "test connection to SOAP service failed");
	}
	
	public function test_getProviderObjectByPaymentMethod() {
		$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();

		$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod('paymentlib_transcentral_cc_visa');
		self::assertTrue (is_object($providerObject), "getProviderObjectByPaymentMethod ('paymentlib_transcentral_cc_visa') did not return ANY object!");
		self::assertEquals ($providerObject->getProviderKey(), $this->providerKey, "getProviderObjectByPaymentMethod ('paymentlib_transcentral_cc_visa') did not return the correct object!");
			
		$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod('paymentlib_transcentral_cc_mastercard');
		self::assertTrue (is_object($providerObject), "getProviderObjectByPaymentMethod ('paymentlib_transcentral_cc_mastercard') did not return ANY object!");
		self::assertEquals ($providerObject->getProviderKey(), $this->providerKey, "getProviderObjectByPaymentMethod ('paymentlib_transcentral_cc_masetercard') did not return the correct object!");

		$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod('paymentlib_transcentral_elv');
		self::assertTrue (is_object($providerObject), "getProviderObjectByPaymentMethod ('paymentlib_transcentral_elv') did not return ANY object!");
		self::assertEquals ($providerObject->getProviderKey(), $this->providerKey, "getProviderObjectByPaymentMethod ('paymentlib_transcentral_elv') did not return the correct object!");
	}
	
	public function test_transaction_validate() {
		$providerObject = $this->getProviderObj();

					// CHECKS FOR VISA:
		$providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,'paymentlib_transcentral_cc_visa');

		$result = $providerObject->transaction_setDetails ($this->fixture_correctDetailsVisa);
		self::assertTrue(($result === TRUE), "setting details of correct VISA transaction failed");
		$result = $providerObject->transaction_validate (1);
		self::assertTrue(($result === TRUE), "validation of VISA transaction should be positive but transaction_validate() did not return TRUE");
		
		$result = $providerObject->transaction_setDetails ($this->fixture_incorrectDetailsVisa);
		self::assertTrue(($result === TRUE), "setting details of incorrect VISA transaction failed");		
		$result = $providerObject->transaction_validate (1);
		self::assertTrue(($result === FALSE), "basic validation of VISA transaction should be negative but transaction_validate() did not return FALSE");
		$result = $providerObject->transaction_validate (2);
		self::assertTrue(($result === FALSE), "advanced validation of VISA transaction should be negative but transaction_validate() did not return FALSE");

			// CHECKS FOR MASTERCARD:
		$providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,'paymentlib_transcentral_cc_mastercard');

		$result = $providerObject->transaction_setDetails ($this->fixture_correctDetailsMastercard);
		self::assertTrue(($result === TRUE), "setting details of correct Mastercard transaction failed");
		$result = $providerObject->transaction_validate (1);
		self::assertTrue(($result === TRUE), "basic validation of Mastercard transaction should be positive but transaction_validate() did not return TRUE");
		$result = $providerObject->transaction_validate (2);
		self::assertTrue(($result === TRUE), "advanced validation of Mastercard transaction should be positive but transaction_validate() did not return TRUE");

		$result = $providerObject->transaction_setDetails ($this->fixture_incorrectDetailsMastercard);
		self::assertTrue(($result === TRUE), "setting details of incorrect Mastercard transaction failed");		
		$result = $providerObject->transaction_validate (1);
		self::assertTrue(($result === FALSE), "basic validation of Mastercard transaction should be negative but transaction_validate() did not return FALSE");
		$result = $providerObject->transaction_validate (2);
		self::assertTrue(($result === FALSE), "advanced validation of Mastercard transaction should be negative but transaction_validate() did not return FALSE");

			// CHECKS FOR ELV:
		$providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,'paymentlib_transcentral_elv');

		$result = $providerObject->transaction_setDetails ($this->fixture_correctDetailsELV);
		self::assertTrue(($result === TRUE), "setting details of correct ELV transaction failed");
		$result = $providerObject->transaction_validate (1);
		self::assertTrue(($result === TRUE), "basic validation of ELV transaction should be positive but transaction_validate() did not return TRUE");
		$result = $providerObject->transaction_validate (2);
		self::assertTrue(($result === TRUE), "advanced validation of ELV transaction should be positive but transaction_validate() did not return TRUE");
		
		$result = $providerObject->transaction_setDetails ($this->fixture_incorrectDetailsELV);
		self::assertTrue(($result === TRUE), "setting details of incorrect ELV transaction failed");		
		$result = $providerObject->transaction_validate (1);
		self::assertTrue(($result === FALSE), "basic validation of ELV transaction should be negative but transaction_validate() did not return FALSE");
		$result = $providerObject->transaction_validate (2);
		self::assertTrue(($result === FALSE), "advanced validation of ELV transaction should be negative but transaction_validate() did not return FALSE");
	}
	
	public function test_transaction_process() {
		$providerObject = $this->getProviderObj();

			// CHECKS FOR VISA:
		$providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,'paymentlib_transcentral_cc_visa');

		$this->fixture_correctDetailsVisa['transaction']['amount'] = rand (1,100000);
		$result = $providerObject->transaction_setDetails ($this->fixture_correctDetailsVisa);
		$result = $providerObject->transaction_process ();
		self::assertTrue(($result === TRUE), "AUTHORIZE AND TRANSFER of VISA trans action should be positive but transaction_process() did not return TRUE");

		$this->fixture_incorrectDetailsVisa['transaction']['amount'] = rand (1,100000);
		$result = $providerObject->transaction_setDetails ($this->fixture_incorrectDetailsVisa);
		$result = $providerObject->transaction_process ();
		self::assertTrue(($result === FALSE), "AUTHORIZE AND TRANSFER of VISA transaction should be NEGATIVE but transaction_process() did return TRUE");

			// CHECKS FOR MASTERCARD:
		$providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,'paymentlib_transcentral_cc_mastercard');

		$this->fixture_correctDetailsMastercard['transaction']['amount'] = rand (1,100000);
		$result = $providerObject->transaction_setDetails ($this->fixture_correctDetailsMastercard);
		$result = $providerObject->transaction_process ();
		self::assertTrue(($result === TRUE), "AUTHORIZE AND TRANSFER of MASTERCARD transaction should be positive but transaction_process() did not return TRUE");

		$this->fixture_incorrectDetailsMastercard['transaction']['amount'] = rand (1,100000);
		$result = $providerObject->transaction_setDetails ($this->fixture_incorrectDetailsMastercard);
		$result = $providerObject->transaction_process ();
		self::assertTrue(($result === FALSE), "AUTHORIZE AND TRANSFER of MASTERCARD transaction should be NEGATIVE but transaction_process() did return TRUE");

			// CHECKS FOR ELV:
		$providerObject->transaction_init (TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER,'paymentlib_transcentral_elv');

		$this->fixture_correctDetailsELV['transaction']['amount'] = rand (1,100000);
		$result = $providerObject->transaction_setDetails ($this->fixture_correctDetailsELV);
		$result = $providerObject->transaction_process ();
		self::assertTrue(($result === TRUE), "AUTHORIZE AND TRANSFER of ELV transaction should be positive but transaction_process() did not return TRUE");

		$this->fixture_incorrectDetailsELV['transaction']['amount'] = rand (1,100000);
		$result = $providerObject->transaction_setDetails ($this->fixture_incorrectDetailsELV);
		$result = $providerObject->transaction_process ();
		self::assertTrue(($result === FALSE), "AUTHORIZE AND TRANSFER of ELV transaction should be NEGATIVE but transaction_process() did return TRUE");
	}
	
	public function test_transaction_getResultsFromDB() {
		$providerObject = $this->getProviderObj();
		$transactionType = TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER;

			// Create a full transaction by using the correct VISA fixture:
		$providerObject->transaction_init ($transactionType,'paymentlib_transcentral_cc_visa');

		$this->fixture_correctDetailsVisa['transaction']['amount'] = rand (1,100000);
		$result = $providerObject->transaction_setDetails ($this->fixture_correctDetailsVisa);
		$result = $providerObject->transaction_process ();
		self::assertTrue(($result === TRUE), "AUTHORIZE AND TRANSFER of VISA trans action should be positive but transaction_process() did not return TRUE");

			// Get the results, fetch the results record from the database and compare it with the data we submitted:		
		$directResultsArr = $providerObject->transaction_getResults();		
		$dbResultsArr = $providerObject->transaction_getResultsFromDB($directResultsArr['internaltransactionuid']);

		self::assertTrue(
			(
				$dbResultsArr['remotetimestamp'] == $directResultsArr['remotetimestamp'] &&
				$dbResultsArr['remotebookingnr'] == $directResultsArr['remotebookingnr'] &&
				$dbResultsArr['remoteauthcode'] == $directResultsArr['remoteauthcode'] &&
				$dbResultsArr['type'] == TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER &&		
				$dbResultsArr['amount'] == $this->fixture_correctDetailsVisa['transaction']['amount'] &&
				$dbResultsArr['currency'] == $this->fixture_correctDetailsVisa['transaction']['currency'] &&
				$dbResultsArr['invoicetext'] == $this->fixture_correctDetailsVisa['transaction']['invoicetext']
			),			
			"getResultsFromDB() did not return the same transaction data which was submitted via transaction_process!"
		);		
	}
		
	protected function getProviderObj() {
		$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
		$providerObjectsArr = $providerFactoryObj->getProviderObjects();
		return $providerObjectsArr[$this->providerClass];		
	}


	public function tearDown () {
	}
		
}

?>