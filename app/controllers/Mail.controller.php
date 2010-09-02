<?php

/*
 * This software is licensed under GPL <http://www.gnu.org/licenses/gpl.html>.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
*/

require_once 'app/models/Blog.model.php';
require_once 'lib/recaptcha/recaptchalib.php';
require_once 'app/controllers/User.controller.php';

class Mail extends Main {
  private $_sRecaptchaPublicKey = RECAPTCHA_PUBLIC;
  private $_sRecaptchaPrivateKey = RECAPTCHA_PRIVATE;
  private $_oRecaptchaResponse = '';
  private $_sRecaptchaError = '';

  # Empty, but required from section helper
  public function __init() {}

  public final function create() {
    if( isset($this->_aRequest['send_mail']) ) {
      if( USER_RIGHT == 0 )
        return $this->_checkCaptcha();
      else
        return $this->_standardMail(false);
    }
    else {
      $bShowCaptcha = ( USER_RIGHT == 0 ) ? true : false;
      return $this->_showCreateMailTemplate($bShowCaptcha);
    }
  }

  private function _showCreateMailTemplate($bShowCaptcha = true) {
    # Look for existing E-Mail address
    if( isset($this->_aRequest['email']))
      $sEmail = (string)$this->_aRequest['email'];

    elseif( isset($this->_aSession['userdata']['email']) )
      $sEmail = $this->_aSession['userdata']['email'];

    else
      $sEmail = '';

    $sSubject = isset($this->_aRequest['subject']) ?
            (string)$this->_aRequest['subject']:
            '';

    $sContent = isset($this->_aRequest['content']) ?
            (string)$this->_aRequest['content']:
            '';

    $oSmarty = new Smarty();
    $oSmarty->assign('id', $this->_iId);
    $oSmarty->assign('contact', Model_User::getUserNamesAndEmail($this->_iId));
    $oSmarty->assign('content', $sContent);
    $oSmarty->assign('email', $sEmail);
    $oSmarty->assign('subject', $sSubject);

    if( $bShowCaptcha == true )
      $oSmarty->assign('_captcha_', recaptcha_get_html(	$this->_sRecaptchaPublicKey, $this->_sRecaptchaError) );
    else
      $oSmarty->assign('_captcha_', '');

    if (!empty($this->_aError)) {
      foreach ($this->_aError as $sField => $sMessage)
        $oSmarty->assign('error_' . $sField, $sMessage);
    }

    # Language
    $oSmarty->assign('lang_content', LANG_GLOBAL_CONTENT);
    $oSmarty->assign('lang_email', LANG_MAIL_GLOBAL_LABEL_OWN_EMAIL);
    $oSmarty->assign('lang_headline', LANG_GLOBAL_CONTACT);
    $oSmarty->assign('lang_optional', LANG_GLOBAL_OPTIONAL);
    $oSmarty->assign('lang_subject', LANG_GLOBAL_SUBJECT);

    if( isset( $this->_aRequest['subject'] ) && 'Bugreport' == $this->_aRequest['subject'] )
      $oSmarty->assign('lang_submit', LANG_GLOBAL_REPORT_ERROR);
    else
      $oSmarty->assign('lang_submit', LANG_GLOBAL_MAIL_SEND);

    $oSmarty->template_dir = Helper::getTemplateDir('mail/create');
    return $oSmarty->fetch('mail/create.tpl');
  }

  private function _checkCaptcha() {
    if( isset($this->_aRequest['recaptcha_response_field']) ) {
      $this->_oRecaptchaResponse = recaptcha_check_answer (
              $this->_sRecaptchaPrivateKey,
              $_SERVER['REMOTE_ADDR'],
              $this->_aRequest['recaptcha_challenge_field'],
              $this->_aRequest['recaptcha_response_field']);

      if ($this->_oRecaptchaResponse->is_valid)
        return $this->_standardMail(true);

      else {
        #$this->_sRecaptchaError   = $this->_oRecaptchaResponse->error;
        $this->_aError['captcha'] = LANG_ERROR_MAIL_CAPTCHA_NOT_CORRECT;
        return $this->_showCreateMailTemplate();
      }
    }
    else
      return Helper::errorMessage(LANG_ERROR_MAIL_CAPTCHA_NOT_LOADED);
  }

  private function _standardMail($bShowCaptcha = true) {
    if (!isset($this->_aRequest['email']) || empty($this->_aRequest['email']))
       $this->_aError['email'] = LANG_ERROR_FORM_MISSING_EMAIL;

    if (!isset($this->_aRequest['content']) || empty($this->_aRequest['content']))
       $this->_aError['content'] = LANG_ERROR_FORM_MISSING_CONTENT;

    if (isset($this->_aError))
      return $this->_showCreateMailTemplate($bShowCaptcha);

    else {
      # Select user name and surname
      require_once 'app/models/User.model.php';
      $aRow = Model_User::getUserNamesAndEmail($this->_iId);

      $sMailTo = $aRow['email'];

      if(empty($sMailTo)) {
        $sReplyTo = isset($this->_aRequest['email']) && !empty($this->_aRequest['email']) ?
                Helper::formatInput($this->_aRequest['email']):
                WEBSITE_MAIL_NOREPLY;
      }
      else
        $sReplyTo = $sMailTo;

      $sSendersName = isset($this->_aSession['userdata']['name']) ?
              $this->_aSession['userdata']['name'] :
              LANG_GLOBAL_SYSTEMBOT;

      $sSubject = isset($this->_aRequest['subject']) && !empty($this->_aRequest['subject']) ?
              Helper::formatInput($this->_aRequest['subject']) :
              str_replace('%u', $sSendersName, LANG_MAIL_GLOBAL_SUBJECT_BY);

      $sMessage = Helper::formatInput($this->_aRequest['content']);

      # Mail to, Subject, Message, Reply to
      $bStatus = Mail::send(	$sMailTo,
              $sSubject,
              $sMessage,
              $sReplyTo);

      if ($bStatus == true)
				return Helper::successMessage(LANG_SUCCESS_MAIL_SENT, '/Start');
			else
				return Helper::errorMessage($bStatus, LANG_ERROR_MAIL_ERROR);
    }
  }

  public static function send($sTo, $sSubject, $sMessage, $sReplyTo = WEBSITE_MAIL) {
    require_once 'lib/phpmailer/class.phpmailer.php';

		# Parse message and replace with (footer) variables
		$sMessage = str_replace('%NOREPLY', LANG_MAIL_GLOBAL_NO_REPLY, $sMessage);
		$sMessage = str_replace('%SIGNATURE', LANG_MAIL_GLOBAL_SIGNATURE, $sMessage);
		$sMessage = str_replace('%WEBSITE_NAME', WEBSITE_NAME, $sMessage);
		$sMessage = str_replace('%WEBSITE_URL', WEBSITE_URL, $sMessage);

		$sSubject = str_replace('%WEBSITE_NAME', WEBSITE_NAME, $sSubject);
		$sSubject = str_replace('%WEBSITE_URL', WEBSITE_URL, $sSubject);

    $oMail = new PHPMailer(true);

    if (SMTP_ON == true)
      $oMail->IsSMTP();
    else
      $oMail->IsSendmail();

    try {
      if (SMTP_ON == true) {
        if (WEBSITE_DEV == true) {
          $oMail->SMTPDebug = 1;
          $oMail->SMTPAuth = false;
        }
        else {
          # enables SMTP debug information (for testing)
          $oMail->SMTPDebug = 0;
          $oMail->SMTPAuth = true;
        }

        $oMail->Host = SMTP_HOST;
        $oMail->Port = SMTP_PORT;
        $oMail->Username = SMTP_USER;
        $oMail->Password = SMTP_PASSWORD;
      }

      $oMail->CharSet = 'utf-8';
      $oMail->AddReplyTo($sReplyTo);
      $oMail->SetFrom(WEBSITE_MAIL, WEBSITE_NAME);
      $oMail->AddAddress($sTo);
      $oMail->Subject = $sSubject;
      $oMail->MsgHTML(nl2br($sMessage));
      $oMail->Send();

      return true;
    }
    catch (phpmailerException $e) {
      return $e->errorMessage();
    }
    catch (Exception $e) {
      return $e->getMessage();
    }
  }
}