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

namespace candyCMS\Core\Controllers;

use candyCMS\Core\Helpers\AdvancedException;
use candyCMS\Core\Controllers\Main;
use candyCMS\Core\Helpers\Helper;
use candyCMS\Core\Helpers\PluginManager;
use candyCMS\Core\Helpers\I18n;

class Sessions extends Main {

  /**
   * Create a session or show template instead.
   * We must override the main method due to a diffent required user right policy.
   *
   * @access public
   * @param integer $iUserRole required user right
   * @return string HTML content or redirect to landing page.
   *
   */
  public function create($iUserRole = 0) {
    if ($this->_aSession['user']['role'] > $iUserRole)
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
   * @param string|array $mAdditionalCaches specify aditional caches to clear on success - actually not required.
   * Just a bug fix for PHP strict mode
   * @param string $sRedirectURL specify the URL to redirect to after execution - actually not required.
   * Just a bug fix for PHP strict mode
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  protected function _create($mAdditionalCaches = null, $sRedirectURL = '') {
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
   * @param string $sTemplateName name of form template
   * @param string $sTitle title to show
   * @return string HTML content
   *
   */
  public function _showFormTemplate($sTemplateName = '_form', $sTitle = 'global') {
    $sTemplateDir   = Helper::getTemplateDir($this->_sController, $sTemplateName);
    $sTemplateFile  = Helper::getTemplateType($sTemplateDir, $sTemplateName);
    $this->oSmarty->setTemplateDir($sTemplateDir);

    if ($this->_aError)
      $this->oSmarty->assign('error', $this->_aError);

    $this->oSmarty->assign('email', isset($this->_aRequest[$this->_sController]['email']) ?
                    (string) $this->_aRequest[$this->_sController]['email'] :
                    '');

    $this->setTitle(I18n::get($sTitle . '.login'));
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

      return isset($this->_aRequest[$this->_sController]['email']) ?
              $this->_password() :
              $this->_showCreateResendActionsTemplate();
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
  protected function _password() {
    $this->_setError('email');

    # do the captchaCheck for for not logged in users
    if ($this->_aSession['user']['role'] == 0) {
      $oPluginManager = PluginManager::getInstance();
      $oPluginManager->checkCaptcha($this->_aError);
    }

    if (isset($this->_aError))
      return $this->_showCreateResendActionsTemplate($bShowCaptcha);

    $sNewPasswordClean = Helper::createRandomChar(16, true);
    $bReturn = $this->_oModel->password(md5(RANDOM_HASH . $sNewPasswordClean));
    $sRedirect = '/' . $this->_sController . '/create';

    if ($bReturn == true) {
      $sModel = $this->__autoload('Mails', true);
      $oMails = new $sModel($this->_aRequest, $this->_aSession);

      $aMail['to_address']  = Helper::formatInput($this->_aRequest[$this->_sController]['email']);
      $aMail['subject']     = I18n::get('sessions.password.mail.subject');
      $aMail['message']     = I18n::get('sessions.password.mail.body', $sNewPasswordClean);

      return $oMails->create($aMail) === true ?
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

      return isset($this->_aRequest[$this->_sController]['email']) ?
              $this->_verification() :
              $this->_showCreateResendActionsTemplate();
    }
  }

  /**
   * Resend verification.
   *
   * Check if required data is given or throw an error instead.
   * If data is given, try to send mail.
   *
   * @access protected
   * @return string HTML
   *
   */
  protected function _verification($bShowCaptcha) {
    $this->_setError('email');

    # do the captchaCheck for for not logged in users
    if ($this->_aSession['user']['role'] == 0) {
      $oPluginManager = PluginManager::getInstance();
      $oPluginManager->checkCaptcha($this->_aError);
    }

    if (isset($this->_aError))
      return $this->_showCreateResendActionsTemplate($bShowCaptcha);

    $mData = $this->_oModel->verification();
    $sRedirect = '/' . $this->_sController . '/create';

    if (is_array($mData) && !empty($mData)) {
      $sModel = $this->__autoload('Mails', true);
      $oMails = new $sModel($this->_aRequest, $this->_aSession);

      $aMail['to_address']  = Helper::formatInput($this->_aRequest[$this->_sController]['email']);
      $aMail['subject']     = I18n::get('sessions.verification.mail.subject');
      $aMail['message']     = I18n::get('sessions.verification.mail.body',
                      $mData['name'],
                      Helper::createLinkTo('users/' . $mData['verification_code'] . '/verification'));

      return $oMails->create($aMail) === true ?
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
   * @return string HTML content
   *
   */
  protected function _showCreateResendActionsTemplate() {
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
   * Destroy user session.
   *
   * @access public
   * @param integer $iUserRole required user right - actually not required. Just a bug fix for PHP strict mode
   * @return boolean status of model action
   *
   */
  public function destroy($iUserRole = 0) {

    if ($this->_aSession['user']['role'] > 0) {
      $oPluginManager = PluginManager::getInstance();
      $sRedirectUrl = '/';
      if ($oPluginManager->hasSessionPlugin() && $this->_aSession['user']['role'] == 2)
        $sRedirectUrl = $oPluginManager->getSessionPlugin()->logoutUrl(WEBSITE_URL);
      else
        $this->_oModel->destroy(session_id());

      unset($this->_aSession);
      return Helper::successMessage(I18n::get('success.session.destroy'), $sRedirectUrl);
    }
    else
      return Helper::redirectTo('/');
  }
}
