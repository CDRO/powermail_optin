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

require_once(t3lib_extMgm::extPath('powermail_optin').'lib/class.tx_powermail_optin_div.php'); // load div class
require_once(t3lib_extMgm::extPath('powermail').'lib/class.tx_powermail_functions_div.php'); // load div class of powermail

class tx_powermail_optin_submit extends tslib_pibase {
	
	var $prefixId      = 'tx_powermail_optin_pi1';		// Same as class name
	var $scriptRelPath = 'lib/class.tx_powermail_optin_submit.php';	// Path to this script relative to the extension dir.
	var $extKey        = 'powermail_optin';	// The extension key.
	var $pi_checkCHash = true;
	var $dbInsert = 1; // disable for testing only (db entry)
	var $sendMail = 1; // disable for testing only (emails)
	var $tsSetupPostfix = 'tx_powermailoptin.'; // Typoscript name for variables

	// Function PM_SubmitBeforeMarkerHook() to manipulate db entry
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
		$this->receiver = $this->sessiondata[$this->obj->pibase->cObj->data['tx_powermail_sender']]; // sender email address
		$this->hash = $this->div->simpleRandString(); // Get random hash code
		
		// lets start
		if ( $obj->pibase->cObj->data['tx_powermailoptin_optin'] == 1 && t3lib_div::validEmail($this->receiver) ) { // only if optin is enabled in tt_content AND senderemail is set and valid email
			
			if (!isset($obj->pibase->piVars['optinuid'])) { // if optinuid is not set
				// disable emails and db entry from powermail
				$obj->conf['allow.']['email2receiver'] = 0; // disable email to receiver
				$obj->conf['allow.']['email2sender'] = 0; // disable email to sender
				$obj->conf['allow.']['dblog'] = 0; // disable database storing
				
				// write values to db with hidden = 1
				$this->saveMail();
				
				// send email to sender with confirmation link
				$this->sendMail();
			}	
			
			else { // optinuid is set - so go on with normal powermail => redirect
			
				$obj->conf['allow.']['dblog'] = 0; // disable database storing, because it was already stored
				
			}
				
		}
		
		return false; // no error return
	}
	
	
	// Function PM_SubmitLastOneHook() to change thx message to "confirmation needed" message
	function PM_SubmitLastOneHook(&$content, $conf, $sessiondata, $ok, $obj) {
		// config
		global $TSFE;
    	$this->cObj = $TSFE->cObj; // cObject
    	$this->obj = $obj;
		$this->conf = $conf;
		$this->sessiondata = $sessiondata;
		$this->pi_loadLL();
		$this->conf[$this->tsSetupPostfix] = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->tsSetupPostfix];
		$this->div_pm = t3lib_div::makeInstance('tx_powermail_functions_div'); // Create new instance for div class of powermail
		$this->receiver = $this->sessiondata[$this->obj->pibase->cObj->data['tx_powermail_sender']]; // sender email address
		
		// let's start
		if ( $obj->pibase->cObj->data['tx_powermailoptin_optin'] == 1 && t3lib_div::validEmail($this->receiver) ) { // only if optin is enabled in tt_content AND senderemail is set and valid email
			if (!isset($obj->pibase->piVars['optinuid'])) { // if optinuid is not set
				$markerArray = array(); $tmpl = array(); // init
				$tmpl['confirmationmessage']['all'] = $this->cObj->getSubpart(tslib_cObj::fileResource($this->conf['tx_powermailoptin.']['template.']['confirmationmessage']), '###POWERMAILOPTIN_CONFIRMATIONMESSAGE###'); // Content for HTML Template
				$markerArray['###POWERMAILOPTIN_MESSAGE###'] = $this->pi_getLL('confirmation_message', 'Look into your mails - confirmation needed'); // mail subject;
				$content = $this->cObj->substituteMarkerArrayCached($tmpl['confirmationmessage']['all'], $markerArray); // substitute markerArray for HTML content
				$content = $this->div_pm->marker2value($content, $this->sessiondata); // ###UID34### to its value
				$content = preg_replace("|###.*?###|i","", $content); // Finally clear not filled markers
			}
		}
		
	}
	
	
	// Function sendMail() to send confirmation link to sender
	function sendMail() {
	
		// Prepare mail content
		$this->markerArray = array(); $this->tmpl = array(); // init
		$this->div_pm = t3lib_div::makeInstance('tx_powermail_functions_div'); // Create new instance for div class of powermail
		$this->tmpl['confirmationemail']['all'] = $this->cObj->getSubpart(tslib_cObj::fileResource($this->conf['tx_powermailoptin.']['template.']['confirmationemail']), '###POWERMAILOPTIN_CONFIRMATIONEMAIL###'); // Content for HTML Template
		$this->markerArray['###POWERMAILOPTIN_LINK###'] = ($GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'] ? $GLOBALS['TSFE']->tmpl->setup['config.']['baseURL'] : 'http://'.$_SERVER['HTTP_HOST'].'/') . $this->cObj->typolink('x',array("returnLast"=>"url","parameter"=>$GLOBALS['TSFE']->id,"additionalParams"=>'&tx_powermail_pi1[optinhash]='.$this->hash.'&tx_powermail_pi1[optinuid]='.$this->saveUid,"useCacheHash"=>1)); // Link marker
		$this->markerArray['###POWERMAILOPTIN_HASH###'] = $this->hash; // Hash marker
		$this->markerArray['###POWERMAILOPTIN_MAILUID###'] = $this->saveUid; // uid of last saved mail
		$this->markerArray['###POWERMAILOPTIN_PID###'] = $GLOBALS['TSFE']->id; // pid of current page
		$this->markerArray['###POWERMAILOPTIN_LINKLABEL###'] = $this->pi_getLL('email_linklabel', 'Confirmationlink'); // label from locallang
		$this->markerArray['###POWERMAILOPTIN_TEXT1###'] = $this->pi_getLL('email_text1', 'Confirmationlink'); // label from locallang
		$this->markerArray['###POWERMAILOPTIN_TEXT2###'] = $this->pi_getLL('email_text2', 'Confirmationlink'); // label from locallang
		$this->mailcontent = $this->cObj->substituteMarkerArrayCached($this->tmpl['confirmationemail']['all'], $this->markerArray); // substitute markerArray for HTML content
		$this->mailcontent = $this->div_pm->marker2value($this->mailcontent, $this->sessiondata); // ###UID34### to its value
		$this->mailcontent = preg_replace("|###.*?###|i","", $this->mailcontent); // Finally clear not filled markers
		
		// start main mail function
		$this->htmlMail = t3lib_div::makeInstance('t3lib_htmlmail'); // New object: TYPO3 mail class
		$this->htmlMail->start(); // start htmlmail
		$this->htmlMail->recipient = $this->receiver; // main receiver email address
		$this->htmlMail->recipient_copy = (t3lib_div::validEmail($this->conf['tx_powermailoptin.']['email.']['cc']) ? $this->conf['tx_powermailoptin.']['email.']['cc'] : ''); // cc field (other email addresses from ts)
		$this->htmlMail->subject = ($this->conf['tx_powermailoptin.']['email.']['subjectoverwrite'] ? $this->conf['tx_powermailoptin.']['email.']['subjectoverwrite'] : $this->pi_getLL('email_subject', 'Confirmation needed') ); // mail subject
		$this->htmlMail->from_email = $this->obj->pibase->submit->sender; // sender email address
		$this->htmlMail->from_name = $this->obj->pibase->submit->sendername; // sender email name
		$this->htmlMail->returnPath = $this->obj->pibase->submit->sender; // return path
		$this->htmlMail->replyto_email = ''; // clear replyto email
		$this->htmlMail->replyto_name = ''; // clear replyto name
		$this->htmlMail->charset = $GLOBALS['TSFE']->metaCharset; // set current charset
		$this->htmlMail->defaultCharset = $GLOBALS['TSFE']->metaCharset; // set current charset
		$this->htmlMail->addPlain($this->mailcontent);
		$this->htmlMail->setHTML($this->htmlMail->encodeMsg($this->mailcontent));
		if ($this->sendMail) $this->htmlMail->send($this->receiver);
	}
	
	
	// Function saveMail() to save piVars and some more infos to DB (tx_powermail_mails) with hidden = 1
	function saveMail() {
		
		// DB entry for table Tabelle: tx_powermail_mails
		$db_values = array (
			'pid' => ($this->conf['PID.']['dblog'] > 0 ? $this->save_PID = $this->conf['PID.']['dblog'] : $this->save_PID = $GLOBALS['TSFE']->id), // PID
			'tstamp' => time(), // save current time
			'crdate' => time(), // save current time
			'hidden' => 1, // save as hidden
			'formid' => $this->obj->pibase->cObj->data['uid'],
			'recipient' => $this->obj->pibase->submit->MainReceiver,
			'subject_r' => $this->obj->pibase->submit->subject_r,
			'sender' => $this->obj->pibase->submit->sender,
			'content' => $this->pi_getLL('database_content', 'No mailcontent: Double opt-in mail was send'), // message for "email-content" field
			'piVars' => t3lib_div::array2xml($this->sessiondata,'',0,'piVars'),
			'senderIP' => ($this->confArr['disableIPlog'] == 1 ? $this->pi_getLL('database_noip') : $_SERVER['REMOTE_ADDR']), // IP address if enabled
			'UserAgent' => $_SERVER['HTTP_USER_AGENT'],
			'Referer' => $_SERVER['HTTP_REFERER'],
			'SP_TZ' => $_SERVER['SP_TZ'],
			'tx_powermailoptin_hash' => $this->hash
		);
		
		if($this->dbInsert) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_powermail_mails',$db_values); // DB entry
			$this->saveUid = mysql_insert_id(); // Give me the uid if the last saved mail
		}
	}

}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_submit.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_submit.php']);
}
?>