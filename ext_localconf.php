<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (!defined ('PAYSUITE_EXTkey')) {
	define('PAYSUITE_EXTkey','paysuite');
}

if (t3lib_extMgm::isLoaded(PAYSUITE_EXTkey)) {
	if (!defined ('PATH_BE_paysuite')) {
		define('PATH_BE_paysuite', t3lib_extMgm::extPath(PAYSUITE_EXTkey));
	}
}

require_once (t3lib_extMgm::extPath ('paymentlib').'lib/class.tx_paymentlib_providerfactory.php');
require_once (t3lib_extMgm::extPath ('paymentlib_saferpay').'class.tx_paymentlibsaferpay_provider.php');

$providerFactoryObj = tx_paymentlib_providerfactory::getInstance();
$providerFactoryObj->registerProviderClass ('tx_paymentlibsaferpay_provider');

?>