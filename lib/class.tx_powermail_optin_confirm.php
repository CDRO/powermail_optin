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

if (t3lib_extMgm::isLoaded('wt_spamshield', 0)) {
	include_once(t3lib_extMgm::extPath('wt_spamshield') . 'ext/class.tx_wtspamshield_powermail.php'); // include div class
}


class tx_powermail_optin_confirm extends tslib_pibase {

	var $prefixId = 'tx_powermail_pi1'; // Prefix
	var $scriptRelPath = 'lib/class.tx_powermail_optin_confirm.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'powermail_optin';	// The extension key.

	
	/**
	 * Function PM_MainContentAfterHook() to manipulate content from powermail
	 *
	 * @param	string		$content: Content from powermail
	 * @param	string		$piVars: All user variables from powermail
	 * @param	object		$obj: Parent object
	 * @return	void
	 */
	function PM_MainContentAfterHook(&$content, $piVars, $obj) {
		
		// config
		$this->pi_loadLL();
		global $TSFE;
    	$this->cObj = $TSFE->cObj; // cObject
		$this->obj = $obj;
		$this->piVars = $piVars;
		
		// let's go
		if ($this->piVars['optinhash'] > 0 && $this->piVars['optinuid'] > 0 && !$this->piVars['sendNow'] && !$this->piVars['mailID']) { // only if GET param optinhash and optenuid is set
			
			// Give me all needed mails
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery (
				'uid',
				'tx_powermail_mails',
				$where_clause = 'tx_powermailoptin_hash = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->piVars['optinhash'], 'tx_powermail_mails') . tslib_cObj::enableFields('tx_powermail_mails', 1) . ' AND hidden = 1',
				$groupBy = '',
				$orderBy = '',
				$limit = ''
			);
			if ($res) $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res); // array of database selection
			
			// Check if hash is ok
			if ($row['uid'] > 0 && $row['uid'] == $this->piVars['optinuid']) { // hash is ok
				
				$content = $this->redirect(); // send real mail to receiver (mail is still with hidden=1)
			
			} else { // hash is not ok
				
				$content = '<b>' . $this->pi_getLL('confirm_alreadyfilled', 'You have alredy finished the confirmation.') . '</b>';
			
			}
			
		}
		
		// no return
	}
	
	
	/**
	 * Function redirect() redirects to powermail // sends main email to powermail receiver
	 *
	 * @return	Link for redirect (normally not needed because of the header redirect)
	 */
	function redirect() {
		if (class_exists('tx_wtspamshield_div')) { // if spamshield div class exists
			$wtspamshield_div = t3lib_div::makeInstance('tx_wtspamshield_div'); // Generate Instance for div method
			$wtspamshield_div->disableSpamshieldForCurrentPage(); // turn off spamshield now
		}
		
		$typolink_conf = array (
		  'returnLast' => 'url', // Give me only the string
		  'parameter' => $GLOBALS['TSFE']->id, // target pid
		  'useCacheHash' => 0, // Don't use cache
		  'section' => '', // clear section value if any
		  'additionalParams' => '&' . $this->prefixId . '[mailID]=' . $this->obj->cObj->data['uid'] . '&' . $this->prefixId . '[sendNow]=1&' . $this->prefixId . '[optinuid]=' . $this->piVars['optinuid'] . '&' . $this->prefixId . '[optinhash]=' . $this->piVars['optinhash']
		);
		$link = t3lib_div::locationHeaderUrl($this->cObj->typolink('x', $typolink_conf)); // Create target url
						
		// Header for redirect
		header('Location: ' . $link); 
		header('Connection: close');
		
		return '<a href="' . $link . '">' . $this->pi_getLL('confirm_redirect', 'If you can see this, please use this link') . '</a>';
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_submit.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_submit.php']);
}
?>