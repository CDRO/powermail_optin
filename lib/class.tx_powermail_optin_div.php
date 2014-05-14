<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Alexander Kellner <alexander.kellner@einpraegsam.net>
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


class tx_powermail_optin_div extends tslib_pibase {

	
	/**
	 * Generates random string
	 *
	 * @param	int		$len: String length
	 * @param	string	$list: Allowed signs
	 * @return	random string
	 */
	function simpleRandString($len = 8, $list = '23456789ABCDEFGHJKMNPQRSTUVWXYZ') {
		$str = '';
		if (is_numeric ($len) && !empty ($list)) {
			mt_srand ((double) microtime () * 1000000);
			while (strlen ($str) < $len) {
				$str .= $list[mt_rand (0, strlen ($list)-1)];
			}
		}
		return t3lib_div::md5int($str);
	}

	
	/**
	 * Function updateMailEntry() set mail entry of powermail from hidden=1 to hidden=0
	 *
	 * @param	int		$uid: mail uid to manipulate
	 * @return	void
	 */
	function updateMailEntry($uid) {
		
		if ($uid > 0) {
			// Update tx_powermail_mails SET hidden = 0
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery (
				'tx_powermail_mails',
				'uid = ' . intval($uid),
				array (
					'tstamp' => time(),
					'hidden' => 0
				)
			);
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_div.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/powermail_optin/lib/class.tx_powermail_optin_div.php']);
}
?>