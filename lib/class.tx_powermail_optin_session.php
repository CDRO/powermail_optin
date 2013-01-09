<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Alex Kellner <alexander.kellner@in2code.de>
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

require_once(t3lib_extMgm::extPath('powermail') . 'lib/class.tx_powermail_sessions.php'); // file for powermail session functions

class tx_powermail_optin_session extends tslib_pibase {

	
	/**
	 * Function PM_MainContentBeforeHook() to write in session
	 *
	 * @param	array		$sessionfields: Values in session
	 * @param	array		$piVars: piVars from powermail
	 * @param	object		$obj: Parent object
	 * @return	void
	 */
	function PM_MainContentBeforeHook(&$sessionfields, $piVars, $obj) {
		if ($piVars['mailID'] > 0 && $piVars['sendNow'] > 0 && $piVars['optinuid'] > 0 && $piVars['optinhash'] > 0) { // only in this case
			
			// Give me all needed fieldsets
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
				'uid, piVars',
				'tx_powermail_mails',
				$where_clause = 'tx_powermailoptin_hash = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($piVars['optinhash'], 'tx_powermail_mails') . tslib_cObj::enableFields('tx_powermail_mails',	1),
				$groupBy = '',
				$orderBy = '',
				$limit = ''
			);
			if ($res) $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); // array of database selection
			
			// Check if hash is ok
			if ($row['uid'] > 0 && $row['uid'] == $piVars['optinuid']) { // hash is ok
				$sessionfields = t3lib_div::xml2array($row['piVars'], 'piVars'); // values from database
				if (!is_array($sessionfields)) $sessionfields = t3lib_div::xml2array(utf8_encode($row['piVars']), 'piVars'); // values from database
				
				// write values to session
				$this->session = t3lib_div::makeInstance('tx_powermail_sessions'); // Create new instance for powermail session class
				
				$GLOBALS['TSFE']->fe_user->setKey('ses', 'powermail_' . $piVars['mailID'], $sessionfields); // Generate Session with piVars array
				$GLOBALS['TSFE']->storeSessionData(); // Save session
			}
			
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.class.tx_powermail_optin_session.php.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.class.tx_powermail_optin_session.php.php']);
}
?>