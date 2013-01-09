<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Alexander Kellner <alexander.kellner@in2code.de>
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

require_once(PATH_tslib . 'class.tslib_pibase.php');

/**
 * Plugin 'Powermail Optin' for the 'powermaill_optin' extension.
 *
 * @author	Alex Kellner <alexander.kellner@in2code.de>
 * @package	TYPO3
 * @subpackage	tx_feloginwithcode_dynamicmarkers
 */
class tx_powermail_optin_dynamicmarkers extends tslib_pibase {

    public $extKey = 'powermail_optin';
    public $scriptRelPath = 'lib/class.tx_powermail_optin_submit.php';    // Path to get locallang.xml from folder
    
	private $locallangmarker_prefix = array( // prefix for automatic locallangmarker
		'POWERMAILOPTIN_LL_', // prefix for HTML template part
		'powermailoptin_ll_' // prefix for typoscript part
	);
    private $typoscriptmarker_prefix = array( // prefix for automatic typoscriptmarker
		'POWERMAILOPTIN_TS_', // prefix for HTML template part
		'powermailoptin_ts' // prefix for typoscript part
	);
	
	/**
	 * Function main() to replace typoscript- and locallang markers
	 *
	 * @param	string		$content: The content from html template
	 * @param	object		$pObj: Parent Object
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $pObj) {
		// config
		$this->conf = $pObj->conf['tx_powermailoptin.'];
		$this->cObj = $pObj->cObj;
		$this->content = $content;
		$this->pi_loadLL();
		
		// let's go
		// 1. replace locallang markers
		$this->content = preg_replace_callback ( // Automaticly fill locallangmarkers with fitting value of locallang.xml
			'#\#\#\#' . $this->locallangmarker_prefix[0] . '(.*)\#\#\##Uis', // regulare expression
			array($this, 'dynamicLocalLangMarker'), // open function
			$this->content // current content
		);
		
		// 2. replace typoscript markers
		$this->content = preg_replace_callback ( // Automaticly fill locallangmarkers with fitting value of locallang.xml
			'#\#\#\#' . $this->typoscriptmarker_prefix[0] . '(.*)\#\#\##Uis', // regulare expression
			array($this, 'dynamicTyposcriptMarker'), // open function
			$this->content // current content
		);
		
		if (!empty($this->content)) {
			return $this->content;
		}
	}
	
	/**
	 * Callback: Get automaticly a marker from locallang.xml (###LOCALLANG_BLABLA### from locallang.xml: locallangmarker_blabla)
	 *
	 * @param	array		$array: Marker values
	 * @return	string to replace
	 */
    private function dynamicLocalLangMarker($array) {
		if (!empty($array[1])) {
			$string = $this->pi_getLL(strtolower($this->locallangmarker_prefix[1] . $array[1]), '<em>' . strtolower($array[1]) . '</em>'); // search for a fitting entry in locallang.xml or typoscript
		}
        
		if (!empty($string)) {
			return $string;
		}
    }
	
	/**
	 * Callback: Get automaticly a marker from typoscript
	 *
	 * @param	array		$array: Marker values
	 * @return	string to replace
	 */
    private function dynamicTyposcriptMarker($array) {
		if ($this->conf[$this->typoscriptmarker_prefix[1] . '.'][strtolower($array[1])]) { // If there is a fitting entry in typoscript
			$string = $this->cObj->cObjGetSingle($this->conf[$this->typoscriptmarker_prefix[1] . '.'][strtolower($array[1])], $this->conf[$this->typoscriptmarker_prefix[1] . '.'][strtolower($array[1]) . '.']); // fill string with typoscript value
		}
        
		if (!empty($string)) {
			return $string;
		}
    }
	
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_dynamicmarkers.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_dynamicmarkers.php']);
}

?>