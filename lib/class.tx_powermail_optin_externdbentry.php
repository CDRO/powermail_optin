<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Alexander Kellner <alexander.kellner@einpraegsam.net>
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

class tx_powermail_optin_externdbentry extends tslib_pibase {

	var $prefixId = 'tx_powermail_pi1'; // Prefix

	// Function PM_MainContentAfterHook() to manipulate content from powermail
	function PM_SubmitLastOneHook($content, $conf, $session, &$ok, $obj) {
		
		// config
		global $TSFE;
    	$this->cObj = $TSFE->cObj; // cObject
		$this->obj = $obj;
		
		// let's go
		if ($obj->pibase->cObj->data['tx_powermailoptin_optin'] == 1) { // only if opt-in enabled in backend
			if ($this->piVars['optinhash'] > 0 && $this->piVars['optinuid'] > 0 && $this->piVars['sendNow'] && $this->piVars['mailID']) { // only if GET param optinhash and optenuid is set
				
				// Give me all needed fieldsets
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
					'uid',
					'tx_powermail_mails',
					$where_clause = 'tx_powermailoptin_hash = '.strip_tags(addslashes($this->piVars['optinhash'])).tslib_cObj::enableFields('tx_powermail_mails', 1),
					$groupBy = '',
					$orderBy = '',
					$limit = ''
				);
				if ($res) $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); // array of database selection
				
				// Check if hash is ok
				if ($row['uid'] > 0 && $row['uid'] == $this->piVars['optinuid']) { // hash is ok
					
					$ok = 1; // enable extern db entries
				
				} else { // hash is not ok
				
					$ok = 0; // disable extern db entries
				
				}
				
			} else {
				$ok = 0; // disable extern db entries
			}
		}
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_externdbentry.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_externdbentry.php']);
}
?>