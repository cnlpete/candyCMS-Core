<?php

/**
 * Piwik analytics code.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Hauke Schade <http://hauke-schade.de>
 * @license MIT
 * @since 2.0
 *
 */

namespace CandyCMS\Plugin\Controller;

use CandyCMS\Helper\Helper as Helper;
use CandyCMS\Helper\SmartySingleton as SmartySingleton;

final class Piwik {

  /**
   * Identifier for Template Replacements
   *
   * @var constant
   *
   */
  const IDENTIFIER = 'piwik';

	/**
	 * Get The HTML-Code for Piwik.
	 *
	 * @static
	 * @access public
	 * @return string HTML
	 *
	 */
  public final static function show() {
    $sTemplateDir   = Helper::getPluginTemplateDir('piwik', 'show');
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, 'show');

    $oSmarty = SmartySingleton::getInstance();
    $oSmarty->setTemplateDir($sTemplateDir);
    $oSmarty->setCaching(SmartySingleton::CACHING_LIFETIME_SAVED);

    $sCacheId = 'plugins|' . WEBSITE_LOCALE . '|piwik';
    if (!$oSmarty->isCached($sTemplateFile, $sCacheId)) {
      $oSmarty->assign('WEBSITE_MODE', WEBSITE_MODE);
      $oSmarty->assign('PLUGIN_PIWIK_ID', PLUGIN_PIWIK_ID);
      $oSmarty->assign('PLUGIN_PIWIK_URL', PLUGIN_PIWIK_URL);
    }

    return $oSmarty->fetch($sTemplateFile, $sCacheId);
  }
}