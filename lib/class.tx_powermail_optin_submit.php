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

require_once(t3lib_extMgm::extPath('powermail_optin') . 'lib/class.tx_powermail_optin_div.php'); // load div class
require_once(t3lib_extMgm::extPath('powermail_optin') . 'lib/class.tx_powermail_optin_dynamicmarkers.php'); // load dynamicmarkers class
require_once(t3lib_extMgm::extPath('powermail') . 'lib/class.tx_powermail_functions_div.php'); // load div class of powermail
if (t3lib_extMgm::isLoaded('wt_spamshield', 0)) {
	if (file_exists(t3lib_extMgm::extPath('wt_spamshield') . 'lib/class.tx_powermail_optin_div.php')) { // if file exists
		include_once(t3lib_extMgm::extPath('wt_spamshield') . 'lib/class.tx_powermail_optin_div.php'); // include div class
	}
}

class tx_powermail_optin_submit extends tslib_pibase {
	
	var $prefixId      = 'tx_powermail_optin_pi1';		// Same as class name
	var $scriptRelPath = 'lib/class.tx_powermail_optin_submit.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'powermail_optin';	// The extension key.
	var $pi_checkCHash = true;
	var $dbInsert = 1; // disable for testing only (db entry)
	var $sendMail = 1; // disable for testing only (emails)
	var $tsSetupPostfix = 'tx_powermailoptin.'; // Typoscript name for variables

	
	/**
	 * Function PM_SubmitBeforeMarkerHook() to stop normal powermail process after submit
	 *
	 * @param	object		$obj: Parent object
	 * @param	array		$markerArray: markerArray for tmpl manipulation
	 * @param	array		$sessiondata: session values from powermail
	 * @return	boolean
	 */
	function PM_SubmitBeforeMarkerHook(&$obj, $markerArray, $sessiondata) {
		// config
		global $TSFE;
    	$this->cObj = $TSFE->cObj; // cObject
		$this->conf = $obj->conf;
		$this->conf[$this->tsSetupPostfix] = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->tsSetupPostfix];
		$this->confArr = $obj->confArr;
		$this->obj = $obj;
		$this->pi_loadLL();
		$this->sessiondata = $sessiondata;
		$this->div = t3lib_div::makeInstance('tx_powermail_optin_div'); // Create new instance for div class
		$this->receiver = $this->sessiondata[$this->obj->cObj->data['tx_powermail_sender']]; // sender email address
		$this->hash = $this->div->simpleRandString(); // Get random hash code
		$this->piVars = t3lib_div::_GP('tx_powermail_pi1'); // get piVars
		
		$go = true; // all ok at the beginning
		if (isset($obj->PM_SubmitBeforeMarkerHook_return) && !empty($obj->PM_SubmitBeforeMarkerHook_return)) { // if there is already an entry from the Hook (maybe spam recognized in wt_spamshield)
			$go = false; // stop proceeding
		}
		
		// lets start
		if ( // only if optin is enabled in tt_content AND senderemail is set and valid email AND no spam recognized (e.g. with wt_spamshield)
			$obj->cObj->data['tx_powermailoptin_optin'] == 1 && 
			t3lib_div::validEmail($this->receiver) &&
			$go
		) {
			
			if (empty($this->piVars['optinuid'])) { // if optinuid is not set
				
				// disable emails and db entry from powermail
				$obj->conf['allow.']['email2receiver'] = 0; // disable email to receiver
				$obj->conf['allow.']['email2sender'] = 0; // disable email to sender
				$obj->conf['allow.']['dblog'] = 0; // disable database storing
				
				// write values to db with hidden = 1
				$this->saveMail();
				
				// send email to sender with confirmation link
				$this->sendMail();
			
			} else { // optinuid is set - so go on with normal powermail => redirect
			
				$obj->conf['allow.']['dblog'] = 0; // disable database storing, because it was already stored
				
			}
				
		}
		
		return false; // no error return
	}

	
	/**
	 * Function PM_SubmitLastOneHook() to change thx message to "confirmation needed" message
	 *
	 * @param	string		$content: Content from powermail
	 * @param	array		$conf: TypoScript configuration
	 * @param	string		$sessiondata: All user variables from powermail
	 * @param	boolean		$ok: If no spam (e.g.)
	 * @param	object		$obj: Parent object
	 * @return	void
	 */
	function PM_SubmitLastOneHook(&$content, $conf, $sessiondata, $ok, $obj) {
		// config
		global $TSFE;
    	$this->cObj = $TSFE->cObj; // cObject
    	$this->obj = $obj;
		$this->conf = $conf;
		$this->sessiondata = $sessiondata;
		$this->pi_loadLL();
		$this->conf[$this->tsSetupPostfix] = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->tsSetupPostfix];
		$this->div = t3lib_div::makeInstance('tx_powermail_optin_div'); // Create new instance for div class
		$this->div_pm = t3lib_div::makeInstance('tx_powermail_functions_div'); // Create new instance for div class of powermail
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermail_optin_dynamicmarkers'); // Create new instance for dynamicmarkers class
		$this->receiver = $this->sessiondata[$this->obj->cObj->data['tx_powermail_sender']]; // sender email address
		$this->piVars = t3lib_div::_GP('tx_powermail_pi1'); // get piVars
		
		// let's start
		if ( // only if optin is enabled in tt_content AND senderemail is set and valid email AND should proceed
			$obj->cObj->data['tx_powermailoptin_optin'] == 1 && 
			t3lib_div::validEmail($this->receiver) &&
			$obj->ok
		) {
			if (empty($this->piVars['optinuid'])) { // if optinuid is not set
				
				$markerArray = array(); $tmpl = array(); // init
				$tmpl['confirmationmessage']['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['tx_powermailoptin.']['template.']['confirmationmessage']), '###POWERMAILOPTIN_CONFIRMATIONMESSAGE###'); // Content for HTML Template
				$markerArray['###POWERMAILOPTIN_MESSAGE###'] = $this->pi_getLL('confirmation_message', 'Look into your mails - confirmation needed'); // mail subject;
				$content = $this->cObj->substituteMarkerArrayCached($tmpl['confirmationmessage']['all'], $markerArray); // substitute markerArray for HTML content
				$content = $this->div_pm->marker2value($content, $this->sessiondata); // ###UID34### to its value
				$content = $this->dynamicMarkers->main($content, $this); // Fill dynamic locallang or typoscript markers
				$content = preg_replace('|###.*?###|i', '', $content); // Finally clear not filled markers
			
			} else { // optinuid is set
				
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
					$this->div->updateMailEntry($this->piVars['optinuid']); // finally set mail entry from hidden=1 to hidden=0
				}
				
			}
		}
		
	}
	
	
	/**
	 * Function sendMail() to send confirmation link to sender
	 *
	 * @return	void
	 */
	function sendMail() {
	
		// Prepare mail content
		$this->markerArray = $this->tmpl = array(); // init
		$this->div_pm = t3lib_div::makeInstance('tx_powermail_functions_div'); // Create new instance for div class of powermail
		$this->dynamicMarkers = t3lib_div::makeInstance('tx_powermail_optin_dynamicmarkers'); // Create new instance for dynamicmarkers class
		$this->tmpl['confirmationemail']['all'] = $this->cObj->getSubpart($this->cObj->fileResource($this->conf['tx_powermailoptin.']['template.']['confirmationemail']), '###POWERMAILOPTIN_CONFIRMATIONEMAIL###'); // Content for HTML Template
		
		if (t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/' != t3lib_div::getIndpEnv('TYPO3_SITE_URL')) { // if request_host is different to site_url (TYPO3 runs in a subfolder)
			$subfolder = str_replace(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') . '/', '', t3lib_div::getIndpEnv('TYPO3_SITE_URL')); // get the folder (like "subfolder/")
		} else {
			$subfolder = '';
		}
		$this->markerArray['###POWERMAILOPTIN_LINK###'] = $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'] ? $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'] : 'http://' . $_SERVER['HTTP_HOST'] . '/' . $subfolder;
		$this->markerArray['###POWERMAILOPTIN_LINK###'] .= $this->cObj->typolink(
			'x',
			array(
				'returnLast' => 'url',
				'parameter' => $GLOBALS['TSFE']->id,
				'additionalParams' => '&tx_powermail_pi1[optinhash]=' . $this->hash . '&tx_powermail_pi1[optinuid]=' . $this->saveUid,
				'useCacheHash' => 1
			)
		);
		$this->markerArray['###POWERMAILOPTIN_HASH###'] = $this->hash; // Hash marker
		$this->markerArray['###POWERMAILOPTIN_MAILUID###'] = $this->saveUid; // uid of last saved mail
		$this->markerArray['###POWERMAILOPTIN_PID###'] = $GLOBALS['TSFE']->id; // pid of current page
		$this->markerArray['###POWERMAILOPTIN_LINKLABEL###'] = $this->pi_getLL('email_linklabel', 'Confirmationlink'); // label from locallang
		$this->markerArray['###POWERMAILOPTIN_TEXT1###'] = $this->pi_getLL('email_text1', 'Confirmationlink'); // label from locallang
		$this->markerArray['###POWERMAILOPTIN_TEXT2###'] = $this->pi_getLL('email_text2', 'Confirmationlink'); // label from locallang
		
		$this->mailcontent = $this->cObj->substituteMarkerArrayCached($this->tmpl['confirmationemail']['all'], $this->markerArray); // substitute markerArray for HTML content
		$this->mailcontent = $this->div_pm->marker2value($this->mailcontent, $this->sessiondata); // ###UID34### to its value
		$this->mailcontent = $this->dynamicMarkers->main($this->mailcontent, $this); // Fill dynamic locallang or typoscript markers
		$this->mailcontent = preg_replace('|###.*?###|i', '', $this->mailcontent); // Finally clear not filled markers
		
		// start main mail function
		$this->htmlMail = t3lib_div::makeInstance('t3lib_htmlmail'); // New object: TYPO3 mail class
		$this->htmlMail->start(); // start htmlmail
		$this->htmlMail->recipient = (t3lib_div::validEmail($this->conf['tx_powermailoptin.']['email.']['receiverOverwrite']) ? $this->conf['tx_powermailoptin.']['email.']['receiverOverwrite'] : $this->receiver); // main receiver email address
		$this->htmlMail->recipient_copy = (t3lib_div::validEmail($this->conf['tx_powermailoptin.']['email.']['cc']) ? $this->conf['tx_powermailoptin.']['email.']['cc'] : ''); // cc field (other email addresses from ts)
		$this->htmlMail->subject = ($this->conf['tx_powermailoptin.']['email.']['subjectoverwrite'] ? $this->conf['tx_powermailoptin.']['email.']['subjectoverwrite'] : $this->pi_getLL('email_subject', 'Confirmation needed') ); // mail subject
		$this->htmlMail->from_email = t3lib_div::validEmail($this->conf['tx_powermailoptin.']['email.']['senderEmailOverwrite']) ? $this->conf['tx_powermailoptin.']['email.']['senderEmailOverwrite'] : $this->obj->MainReceiver; // sender email address
		$this->htmlMail->from_name = !empty($this->conf['tx_powermailoptin.']['email.']['senderOverwrite']) ? $this->conf['tx_powermailoptin.']['email.']['senderOverwrite'] : $this->obj->sendername; // sender email name
		$this->htmlMail->returnPath = t3lib_div::validEmail($this->conf['tx_powermailoptin.']['email.']['senderEmailOverwrite']) ? $this->conf['tx_powermailoptin.']['email.']['senderEmailOverwrite'] : $this->obj->MainReceiver; // return path
		$this->htmlMail->replyto_email = ''; // clear replyto email
		$this->htmlMail->replyto_name = ''; // clear replyto name
		$this->htmlMail->charset = $GLOBALS['TSFE']->metaCharset; // set current charset
		$this->htmlMail->defaultCharset = $GLOBALS['TSFE']->metaCharset; // set current charset
		$this->htmlMail->addPlain($this->mailcontent);
		$this->htmlMail->setHTML($this->htmlMail->encodeMsg($this->mailcontent));
		if ($this->sendMail) {
			$this->htmlMail->send(t3lib_div::validEmail($this->conf['tx_powermailoptin.']['email.']['receiverOverwrite']) ? $this->conf['tx_powermailoptin.']['email.']['receiverOverwrite'] : $this->receiver);
		}
					
		if ($this->conf['tx_powermailoptin.']['debug'] == 1) { // if debug output enabled
			$d_array = array(
				'receiver' => (t3lib_div::validEmail($this->conf['tx_powermailoptin.']['email.']['receiverOverwrite']) ? $this->conf['tx_powermailoptin.']['email.']['receiverOverwrite'] : $this->receiver),
				'cc receiver' => (t3lib_div::validEmail($this->conf['tx_powermailoptin.']['email.']['cc']) ? $this->conf['tx_powermailoptin.']['email.']['cc'] : ''),
				'sender' => $this->obj->sender,
				'sender name' => $this->obj->sendername,
				'subject' => ($this->conf['tx_powermailoptin.']['email.']['subjectoverwrite'] ? $this->conf['tx_powermailoptin.']['email.']['subjectoverwrite'] : $this->pi_getLL('email_subject', 'Confirmation needed') ),
				'body' => $this->mailcontent
			);
			t3lib_div::debug($d_array, 'powermail_optin: Values in confirmation email');
		}
	}
	
	
	/**
	 * Function saveMail() to save piVars and some more infos to DB (tx_powermail_mails) with hidden = 1
	 *
	 * @return	void
	 */
	function saveMail() {
		
		$pid = $GLOBALS['TSFE']->id; // current page
		if ($this->conf['PID.']['dblog'] > 0) {
			$pid = $this->conf['PID.']['dblog']; // take pid from ts
		}
		if ($this->obj->cObj->data['tx_powermail_pages'] > 0) {
			$pid = $this->obj->cObj->data['tx_powermail_pages'];
		}
		
		// DB entry for table Tabelle: tx_powermail_mails
		$db_values = array (
			'pid' => intval($pid), // PID
			'tstamp' => time(), // save current time
			'crdate' => time(), // save current time
			'hidden' => 1, // save as hidden
			'formid' => $this->obj->cObj->data['uid'],
			'recipient' => $this->obj->MainReceiver,
			'subject_r' => $this->obj->subject_r,
			'sender' => $this->obj->sender,
			'content' => $this->pi_getLL('database_content', 'No mailcontent: Double opt-in mail was send'), // message for "email-content" field
			'piVars' => t3lib_div::array2xml($this->sessiondata, '', 0, 'piVars'),
			'senderIP' => ($this->confArr['disableIPlog'] == 1 ? $this->pi_getLL('database_noip') : t3lib_div::getIndpEnv('REMOTE_ADDR')), // IP address if enabled
			'UserAgent' => t3lib_div::getIndpEnv('HTTP_USER_AGENT'),
			'Referer' => t3lib_div::getIndpEnv('HTTP_REFERER'),
			'SP_TZ' => $_SERVER['SP_TZ'],
			'tx_powermailoptin_hash' => $this->hash
		);
		
		if ($this->dbInsert) {
			if ($this->conf['tx_powermailoptin.']['debug'] == 1) { // if debug output enabled
				t3lib_div::debug($db_values, 'powermail_optin: Save this values to db');
			}
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_powermail_mails', $db_values); // DB entry
			$this->saveUid = $GLOBALS['TYPO3_DB']->sql_insert_id(); // Give me the uid if the last saved mail

		}
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_submit.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_submit.php']);
}
?>