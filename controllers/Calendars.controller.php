<?php

/**
 * CRUD action of simple calendar.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 2.0
 *
 */

namespace CandyCMS\Core\Controllers;

use CandyCMS\Core\Helpers\Helper;
use CandyCMS\Core\Helpers\I18n;

class Calendars extends Main {

  /**
   * Show calendar overview.
   *
   * @access protected
   * @return string HTML content or exit if action does ics output
   *
   */
  protected function _show() {
    # Show single .ics file
    return $this->_iId ?
            $this->ics($this->_iId) :
            $this->overview();
  }

  /**
   * Helper method to redirect archive action to show overview.
   *
   * @access public
   * @return string HTML
   *
   */
  public function archive() {
    return $this->overview();
  }

  /**
   * Show single event as ics file.
   * This needs to be specified as ajax, since there should be no surrounding templates.
   *
   * @access public
   * @param integer $iId ID to show
   * @return string ICS-File
   *
   */
  public function ics($iId) {
    $sTemplateDir   = Helper::getTemplateDir($this->_sController, 'ics');
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, 'ics');
    $this->oSmarty->setTemplateDir($sTemplateDir);

    if (!$this->oSmarty->isCached($sTemplateFile, UNIQUE_ID)) {
      $aData = $this->_oModel->getId($iId);
      $this->oSmarty->assign('calendar', $aData);

      if (!$aData['id'])
        return Helper::redirectTo('/errors/404');
    }

    header('Content-type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename=' . $aData['title_encoded'] . '.ics');

    exit($this->oSmarty->fetch($sTemplateFile, UNIQUE_ID));
  }

  /**
   * Show the overview
   *
   * @access public
   * @return string HTML content
   *
   */
  public function overview() {
    $sTemplateDir   = Helper::getTemplateDir($this->_sController, 'show');
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, 'show');
    $this->oSmarty->setTemplateDir($sTemplateDir);

    if (!$this->oSmarty->isCached($sTemplateFile, UNIQUE_ID))
      $this->oSmarty->assign('calendar', $this->_oModel->getOverview());

    return $this->oSmarty->fetch($sTemplateFile, UNIQUE_ID);
  }

  /**
   * show the overview
   *
   * @access public
   * @return string HTML content
   *
   */
  public function iCalFeed() {
    $sTemplateDir   = Helper::getTemplateDir($this->_sController, 'icalfeed');
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, 'icalfeed');
    $this->oSmarty->setTemplateDir($sTemplateDir);

    if (!$this->oSmarty->isCached($sTemplateFile, UNIQUE_ID))
      $this->oSmarty->assign('calendar', $this->_oModel->getOverview());

    header('Content-type: text/calendar; charset=utf-8');
    header('Content-Disposition: inline; filename=' . WEBSITE_NAME . '.ics');

    exit($this->oSmarty->fetch($sTemplateFile, UNIQUE_ID));
  }

  /**
   * Create a calendar entry.
   *
   * @access protected
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  protected function _create() {
    $this->_setError('start_date');

    return parent::_create();
  }

  /**
   * Update a calendar entry.
   *
   * @access protected
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  protected function _update() {
    $this->_setError('start_date');

    return parent::_update(null, '/' . $this->_sController);
  }
}