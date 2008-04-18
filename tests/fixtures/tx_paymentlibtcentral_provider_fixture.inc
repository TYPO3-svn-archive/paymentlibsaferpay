<?php
	$todayArr = getdate();

	$this->fixture_correctDetailsVisa = array (
		'payment' => array (
			'ccnumber' => '4012888888881881',
			'ccexpdatemonth' => '1',
			'ccexpdateyear' => $todayArr['year']+1, 
			'ccowner' => 'Some Name',
		),
		'transaction' => array (
			'amount' => 5,
			'currency' => 'EUR',
			'invoicetext' => 'Test transaction correct VISA',
		),
	);
	
	$this->fixture_incorrectDetailsVisa = array (
		'payment' => array (
			'ccnumber' => '123456789',
			'ccexpdatemonth' => '1',
			'ccexpdateyear' => '2000', 
			'ccowner' => 'Some incorrect Name',
		),
		'transaction' => array (
			'amount' => 8,
			'currency' => 'EUR',
			'invoicetext' => 'Test transaction incorrect VISA',
		),
	);

	$this->fixture_correctDetailsMastercard = array (
		'payment' => array (
			'ccnumber' => '5105105105105100',
			'ccexpdatemonth' => '1',
			'ccexpdateyear' => $todayArr['year']+1, 
			'ccowner' => 'Some Name',
		),
		'transaction' => array (
			'amount' => 15,
			'currency' => 'EUR',
			'invoicetext' => 'Test transaction correct Mastercard',
		),
	);

	$this->fixture_incorrectDetailsMastercard = array (
		'payment' => array (
			'ccnumber' => '123456789',
			'ccexpdatemonth' => '1',
			'ccexpdateyear' => '2000', 
			'ccowner' => 'Some Name',
		),
		'transaction' => array (
			'amount' => 29,
			'currency' => 'EUR',
			'invoicetext' => 'Test transaction incorrect Mastercard',
		),
	);

	$this->fixture_correctDetailsELV = array (
		'payment' => array (
			'bankcode' => '24050110',
			'bankaccountnumber' => '666750',
			'bankname' => 'Sparkasse Lüneburg', 
		),
		'transaction' => array (
			'amount' => 95,
			'currency' => 'EUR',
			'invoicetext' => 'Test transaction correct ELV',
		),
	);

	$this->fixture_incorrectDetailsELV = array (
		'payment' => array (
			'bankcode' => '24050110',
			'bankaccountnumber' => '666751',
			'bankname' => 'Sparkasse Lüneburg', 
		),
		'transaction' => array (
			'amount' => 85,
			'currency' => 'EUR',
			'invoicetext' => 'Test transaction incorrect ELV',
		),
	);
		
?>