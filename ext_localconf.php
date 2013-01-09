<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

// Use hook in submit.php before db => saving and sending emails
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_SubmitBeforeMarkerHook'][] = 'EXT:powermail_optin/lib/class.tx_powermail_optin_submit.php:tx_powermail_optin_submit';

// Use hook in submit.php last one => changing thx message
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_SubmitLastOne'][] = 'EXT:powermail_optin/lib/class.tx_powermail_optin_submit.php:tx_powermail_optin_submit';

// Use hook in pi1.php for confirmation class
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_MainContentHookAfter'][] = 'EXT:powermail_optin/lib/class.tx_powermail_optin_confirm.php:tx_powermail_optin_confirm';

// Use hook in pi1.php for set session values
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_MainContentHookBefore'][] = 'EXT:powermail_optin/lib/class.tx_powermail_optin_session.php:tx_powermail_optin_session';

// Use hook to disable extern db log (enable only in last loop)
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['powermail']['PM_SubmitLastOne'][] = 'EXT:powermail_optin/lib/class.tx_powermail_optin_externdbentry.php:tx_powermail_optin_externdbentry';
?>