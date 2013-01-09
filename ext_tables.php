<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

/* Dont't enable hash field in backend
$tempColumns = Array (
	"tx_powermailoptin_hash" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:powermail_optin/locallang_db.xml:tx_powermail_mails.tx_powermailoptin_hash",		
		"config" => Array (
			"type" => "input",	
			"size" => "30",
		)
	),
);


t3lib_div::loadTCA("tx_powermail_mails");
t3lib_extMgm::addTCAcolumns("tx_powermail_mails",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("tx_powermail_mails","tx_powermailoptin_hash;;;;1-1-1");
*/


$tempColumns = Array (
	"tx_powermailoptin_optin" => Array (		
		"exclude" => 1,		
		"label" => "LLL:EXT:powermail_optin/locallang_db.xml:tt_content.tx_powermailoptin_optin",		
		"config" => Array (
			"type" => "check",
		)
	),
);


t3lib_div::loadTCA("tt_content");
t3lib_extMgm::addTCAcolumns("tt_content",$tempColumns,1);
$TCA['tt_content']['types']['powermail_pi1']['showitem'] = str_replace('tx_powermail_multiple,', 'tx_powermail_multiple, tx_powermailoptin_optin;;;;1-1-1,', $TCA['tt_content']['types']['powermail_pi1']['showitem']); // change multiple field with multiple + optin field
?>