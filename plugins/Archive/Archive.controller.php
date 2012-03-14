<?php

/**
 * The archive plugin lists all blog entries by month and date.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.5
 *
 */

namespace CandyCMS\Plugin\Controller;

use CandyCMS\Helper\Helper as Helper;
use CandyCMS\Helper\I18n as I18n;
use Smarty;

final class Archive {

  /**
   * Identifier for Template Replacements
   */
  const identifier = 'archive';

	/**
	 * Show the (cached) archive.
	 *
	 * We use a new Smarty instance to avoid parsing the Main.controller due to performance reasons.
	 *
	 * @static
	 * @access public
	 * @param array $aRequest
	 * @param array $aSession
	 * @return string HTML
	 *
	 */
  public final function show($aRequest, $aSession) {
    $sTemplateDir		= Helper::getPluginTemplateDir('archive', 'show');
    $sTemplateFile	= Helper::getTemplateType($sTemplateDir, 'show');

		$oSmarty = new Smarty();
		$oSmarty->setCacheDir(PATH_STANDARD . '/' . CACHE_DIR);
		$oSmarty->setCompileDir(PATH_STANDARD . '/' . COMPILE_DIR);
		$oSmarty->setCaching(Smarty::CACHING_LIFETIME_SAVED);
		$oSmarty->setTemplateDir($sTemplateDir);

		$oSmarty->merge_compiled_includes = true;
		$oSmarty->use_sub_dirs = true;

		if (!$oSmarty->isCached($sTemplateFile, 'blog|' . WEBSITE_LOCALE . '|archive')) {
			require_once PATH_STANDARD . '/app/models/Blog.model.php';
			$oModel = new \CandyCMS\Model\Blog($aRequest, $aSession);
			$aData = $oModel->getData('', false, PLUGIN_ARCHIVE_LIMIT);

			$aMonth = array();
			foreach ($aData as $aRow) {
				# Date format the month
				$sMonth = strftime('%m', $aRow['date_raw']);
				$sMonth = substr($sMonth, 0, 1) == 0 ? substr($sMonth, 1, 2) : $sMonth;
				$sMonth = I18n::get('global.months.' . $sMonth) . ' ' . strftime('%Y', $aRow['date_raw']);

				# Prepare array
				$iId = $aRow['id'];
				$aMonth[$sMonth][$iId] = $aRow;
			}

			$oSmarty->assign('data', $aMonth);
		}

		return $oSmarty->fetch($sTemplateFile, 'blog|' . WEBSITE_LOCALE . '|archive');
	}
}