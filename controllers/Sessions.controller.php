<?php

/**
 * Create or destroy a session.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.0
 *
 */

namespace CandyCMS\Core\Controllers;

use CandyCMS\Core\Helpers\AdvancedException;
use CandyCMS\Core\Controllers\Main;
use CandyCMS\Core\Helpers\Helper;
use CandyCMS\Core\Helpers\I18n;
use CandyCMS\Plugins\FacebookCMS;
use CandyCMS\Plugins\Recaptcha;

class Sessions extends Main {

  /**
   * Create a session or show template instead.
   * We must override the main method due to a diffent required user right policy.
   *
   * @access public
   * @return string HTML content or redirect to landing page.
   *
   */
  public function create() {
    if ($this->_aSession['user']['role'] > 0)
      return Helper::redirectTo('/');

    else
      return isset($this->_aRequest[$this->_sController]) ? $this->_create() : $this->_showFormTemplate();
  }

  /**
   * Create a session.
   *
   * Check if required data is given or throw an error instead.
   * If data is given, create session.
   *
   * @access protected
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  protected function _create() {
    $this->_setError('email');
    $this->_setError('password');

    if (isset($this->_aError))
      return $this->_showFormTemplate();

    elseif ($this->_oModel->create() === true) {
      # Clear the cache for users, since a new session updates some users last login date.
      $this->oSmarty->clearCacheForController('users');
      return Helper::successMessage(I18n::get('success.session.create'), '/');
    }

    else
      return Helper::errorMessage(I18n::get('error.session.create'), '/' . $this->_sController . '/create');
  }

  /**
   * Build form template to create a session.
   *
   * @access public
   * @return string HTML content
   *
   */
  public function _showFormTemplate() {
    $sTemplateDir   = Helper::getTemplateDir($this->_sController, '_form');
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, '_form');
    $this->oSmarty->setTemplateDir($sTemplateDir);

    if ($this->_aError)
      $this->oSmarty->assign('error', $this->_aError);

    $this->oSmarty->assign('email', isset($this->_aRequest[$this->_sController]['email']) ?
                    (string) $this->_aRequest[$this->_sController]['email'] :
                    '');

    $this->setTitle(I18n::get('global.login'));
    return $this->oSmarty->fetch($sTemplateFile, UNIQUE_ID);
  }

  /**
   * Resend password or show form.
   *
   * @access public
   * @return string HTML
   *
   */
  public function password() {
    if ($this->_aSession['user']['role'] > 0)
      return Helper::redirectTo('/');

    else {
      $this->setTitle(I18n::get('sessions.password.title'));
      $this->setDescription(I18n::get('sessions.password.description'));

      $bShowCaptcha = class_exists('\CandyCMS\Plugins\Recaptcha') ? SHOW_CAPTCHA : false;

      return isset($this->_aRequest[$this->_sController]['email']) ?
              $this->_password($bShowCaptcha) :
              $this->_showCreateResendActionsTemplate($bShowCaptcha);
    }
  }

  /**
   * Resend password.
   *
   * Check if required data is given or throw an error instead.
   * If data is given, try to send mail.
   *
   * @access protected
   * @return string HTML
   *
   */
  protected function _password($bShowCaptcha) {
    $this->_setError('email');

    if ($bShowCaptcha === true && Recaptcha::getInstance()->checkCaptcha($this->_aRequest) === false)
        $this->_aError['captcha'] = I18n::get('error.captcha.incorrect');

    if (isset($this->_aError))
      return $this->_showCreateResendActionsTemplate($bShowCaptcha);

    $sNewPasswordClean = Helper::createRandomChar(10, true);
    $bReturn = $this->_oModel->resendPassword(md5(RANDOM_HASH . $sNewPasswordClean));
    $sRedirect = '/' . $this->_sController . '/create';

    if ($bReturn == true) {
      $sModel = $this->__autoload('Mails', true);
      $oMails = new $sModel($this->_aRequest, $this->_aSession);

      $aData['to_address']  = Helper::formatInput($this->_aRequest[$this->_sController]['email']);
      $aData['subject']     = I18n::get('sessions.password.mail.subject');
      $aData['message']     = I18n::get('sessions.password.mail.body', $sNewPasswordClean);

      return $oMails->create($aData) === true ?
              Helper::successMessage(I18n::get('success.mail.create'), $sRedirect) :
              Helper::errorMessage(I18n::get('error.mail.create'), $sRedirect);
    }
    else
      return Helper::errorMessage(I18n::get('error.session.account'), $sRedirect);
  }

  /**
   * Resend verification or show form.
   *
   * @access public
   * @return string HTML
   *
   */
  public function verification() {
    if ($this->_aSession['user']['role'] > 0)
      return Helper::redirectTo('/');

    else {
      $this->setTitle(I18n::get('sessions.verification.title'));
      $this->setDescription(I18n::get('sessions.verification.description'));

      $bShowCaptcha = class_exists('\CandyCMS\Plugins\Recaptcha') ? SHOW_CAPTCHA : false;

      return isset($this->_aRequest[$this->_sController]['email']) ?
              $this->_verification($bShowCaptcha) :
              $this->_showCreateResendActionsTemplate($bShowCaptcha);
    }
  }

  /**
   * Resend verification.
   *
   * Check if required data is given or throw an error instead.
   * If data is given, try to send mail.
   *
   * @access protected
   * @param boolean $bShowCaptcha display Captcha?
   * @return string HTML
   *
   */
  protected function _verification($bShowCaptcha) {
    $this->_setError('email');

    if ($bShowCaptcha === true && Recaptcha::getInstance()->checkCaptcha($this->_aRequest) === false)
        $this->_aError['captcha'] = I18n::get('error.captcha.incorrect');

    if (isset($this->_aError))
      return $this->_showCreateResendActionsTemplate($bShowCaptcha);

    $aData = $this->_oModel->resendVerification();
    $sRedirect = '/' . $this->_sController . '/create';

    if (is_array($aData) && !empty($aData)) {
      $sModel = $this->__autoload('Mails', true);
      $oMails = new $sModel($this->_aRequest, $this->_aSession);

      $aData['to_address']  = Helper::formatInput($this->_aRequest[$this->_sController]['email']);
      $aData['subject']     = I18n::get('sessions.verification.mail.subject');
      $aData['message']     = I18n::get('sessions.verification.mail.body',
                      $aData['name'],
                      Helper::createLinkTo('users/' . $aData['verification_code'] . '/verification'));

      return $oMails->create($aData) === true ?
              Helper::successMessage(I18n::get('success.mail.create'), $sRedirect) :
              Helper::errorMessage(I18n::get('error.mail.create'), $sRedirect);
    }
    else
      return Helper::errorMessage(I18n::get('error.session.account'), $sRedirect);
  }

  /**
   * Build form template to resend verification or resend password.
   *
   * @access protected
   * @param boolean $bShowCaptcha display Captcha?
   * @return string HTML content
   *
   */
  protected function _showCreateResendActionsTemplate($bShowCaptcha) {
    $sTemplateDir   = Helper::getTemplateDir($this->_sController, 'resend');
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, 'resend');
    $this->oSmarty->setTemplateDir($sTemplateDir);

    if ($this->_aError)
      $this->oSmarty->assign('error', $this->_aError);

    foreach ($this->_aRequest[$this->_sController] as $sInput => $sData)
      $this->oSmarty->assign($sInput, $sData);

    return $this->oSmarty->fetch($sTemplateFile, UNIQUE_ID);
  }

  /**
   * There is no update action for the sessions controller.
   *
   * @access public
   *
   */
  public function update() {
    AdvancedException::writeLog('404: Trying to access ' . ucfirst($this->_sController) . '->update()');
    return Helper::redirectTo('/errors/404');
  }

  /**
   * Destroy user session.
   *
   * @access public
   * @return boolean status of model action
   *
   */
  public function destroy() {
    # Facebook logout
    if ($this->_aSession['user']['role'] == 2) {
      $this->_aSession['facebook']->destroySession();
      unset($this->_aSession['user']);

      return Helper::successMessage(I18n::get('success.session.destroy'),
              $this->_aSession['facebook']->getLogoutUrl(array('next' => WEBSITE_URL . '/')));
    }

    # Standard member
    elseif ($this->_aSession['user']['role'] > 0 && $this->_oModel->destroy(session_id()) === true) {
      unset($this->_aSession['user']);
      return Helper::successMessage(I18n::get('success.session.destroy'), '/');
    }

    else
      return Helper::redirectTo('/');
  }
}