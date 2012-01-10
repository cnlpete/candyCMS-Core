<?php

/**
 * Send newsletter to receipients or users.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.0
 */

namespace CandyCMS\Controller;

use CandyCMS\Helper\Helper as Helper;

require_once 'lib/mailchimp/MCAPI.class.php';

class Newsletter extends Main {

  /**
   * Create a newsletter subscription. Send email information to mailchimp servers.
   *
   * @access public
   * @return string HTML content
   *
   */
  public function createSubscription() {
    $this->_setError('email');

    if (isset($this->_aError))
      return $this->_showCreateSubscriptionTemplate();

    else {
      if ($this->_subscribeToNewsletter($this->_aRequest, true) == true)
        return Helper::successMessage($this->oI18n->get('success.newsletter.create'), '/');

      else
        return Helper::errorMessage($this->oI18n->get('error.standard'), '/newsletter');
    }
  }

  /**
   * Show a form for email subscription.
   *
   * @access private
   * @return string HTML content
   *
   */
  private function _showCreateSubscriptionTemplate() {
    if (!empty($this->_aError))
      $this->oSmarty->assign('error', $this->_aError);

    $sTemplateDir = Helper::getTemplateDir('newsletters', 'subscribe');
    $this->oSmarty->template_dir = $sTemplateDir;
    return $this->oSmarty->fetch(Helper::getTemplateType($sTemplateDir, 'subscribe'));
  }
}