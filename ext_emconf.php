<?php

########################################################################
# Extension Manager/Repository config file for ext: "paymentlib_saferpay"
#
# Auto generated 18-04-2008 16:16
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Payment method "Saferpay"',
	'description' => 'This extension contains the payment method for Saferpay',
	'category' => 'misc',
	'shy' => 0,
	'dependencies' => 'paymentlib,paysuite,static_info_tables',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Franz Holzinger',
	'author_email' => 'contact@fholzinger.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.0.2',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.2-0.0.0',
			'paymentlib' => '0.0.0-0.2.0',
			'paysuite' => '0.0.1-',
			'static_info_tables' => '2.0.5-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:12:{s:9:"ChangeLog";s:4:"63d5";s:40:"class.tx_paymentlibsaferpay_provider.php";s:4:"94ef";s:21:"ext_conf_template.txt";s:4:"812b";s:12:"ext_icon.gif";s:4:"b82e";s:17:"ext_localconf.php";s:4:"b556";s:18:"hidden_trigger.php";s:4:"96a5";s:13:"locallang.php";s:4:"f06b";s:18:"paymentmethods.xml";s:4:"d48b";s:14:"doc/manual.sxw";s:4:"3ead";s:31:"res/logo_saferpay_color_low.png";s:4:"c258";s:46:"tests/tx_paymentsaferpay_provider_testcase.php";s:4:"42d3";s:57:"tests/fixtures/tx_paymentlibtcentral_provider_fixture.inc";s:4:"620c";}',
);

?>