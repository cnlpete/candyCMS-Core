<?php

/**
 * Parent class for most other controllers and provides most language variables.
 *
 * @abstract
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.0
 *
 */

namespace CandyCMS\Core\Controllers;

use CandyCMS\Core\Helpers\AdvancedException;
use CandyCMS\Core\Helpers\Helper;
use CandyCMS\Core\Helpers\I18n;
use CandyCMS\Core\Helpers\SmartySingleton;
use candyCMS\plugins\Bbcode;
use candyCMS\plugins\FacebookCMS;
use MCAPI;

require_once PATH_STANDARD . '/vendor/candyCMS/core/helpers/Helper.helper.php';

abstract class Main {

  /**
   * Alias for $_REQUEST
   *
   * @var array
   * @access protected
   */
  protected $_aRequest = array();

  /**
   * Alias for $_SESSION
   *
   * @var array
   * @access protected
   */
  protected $_aSession = array();

  /**
   * Alias for $_FILE
   *
   * @var array
   * @access protected
   */
  protected $_aFile;

  /**
   * Alias for $_COOKIE
   *
   * @var array
   * @access protected
   */
  protected $_aCookie;

  /**
   * ID to process.
   *
   * @var integer
   * @access protected
   */
  protected $_iId;

  /**
   * Fetches all error messages in an array.
   *
   * @var array
   * @access protected
   */
  protected $_aError;

  /**
   * The controller claimed model.
   *
   * @var object
   * @access protected
   */
  protected $_oModel;

  /**
   * Returned data from models.
   *
   * @var array
   * @access protected
   */
  protected $_aData = array();

  /**
   * Final HTML-Output.
   *
   * @var string
   * @access private
   */
  private $_sContent;

  /**
   * Name of the current controller.
   *
   * @var string
   * @access protected
   */
  protected $_sController;

  /**
   * Meta description.
   *
   * @var string
   * @access private
   */
  private $_sDescription;

  /**
   * Meta keywords.
   *
   * @var string
   * @access private
   */
  private $_sKeywords;

  /**
   * Page title.
   *
   * @var string
   * @access private
   */
  private $_sTitle;

  /**
   * Name of the templates folder.
   *
   * @var string
   * @access protected
   *
   */
  protected $_sTemplateFolder;

  /**
   * Smarty object.
   *
   * @var object
   * @access public
   */
  public $oSmarty;

  /**
   * Initialize the controller by adding input params, set default id and start template engine.
   *
   * @access public
   * @param array $aRequest alias for the combination of $_GET and $_POST
   * @param array $aSession alias for $_SESSION
   * @param array $aFile alias for $_FILE
   * @param array $aCookie alias for $_COOKIE
   *
   */
  public function __construct(&$aRequest, &$aSession, &$aFile = '', &$aCookie = '') {
    $this->_aRequest  = & $aRequest;
    $this->_aSession  = & $aSession;
    $this->_aFile     = & $aFile;
    $this->_aCookie   = & $aCookie;

    # Load config files if not already done (important for unit testing)
    if (!defined('WEBSITE_URL'))
      require PATH_STANDARD . '/config/Candy.inc.php';

    if (!defined('WEBSITE_LOCALE'))
      define('WEBSITE_LOCALE', 'en_US');

    $this->_iId = isset($this->_aRequest['id']) ? (int) $this->_aRequest['id'] : '';
    $this->_sController = $this->_aRequest['controller'];

    $this->_setSmarty();
  }

  /**
   * Destructor.
   *
   * @access public
   *
   */
  public function __destruct() {}

  /**
   * Dynamically load classes.
   *
   * @static
   * @param string $sClass name of class to load
   * @param boolean $bModel load a model file
   * @return string class name
   *
   */
  public static function __autoload($sClass, $bModel = false) {
    $sClass = (string) ucfirst(strtolower($sClass));

    if ($bModel === true)
      return \CandyCMS\Core\Models\Main::__autoload($sClass);

    else {
      if (EXTENSION_CHECK && file_exists(PATH_STANDARD . '/app/extensions/controllers/' . $sClass . '.controller.php')) {
        require_once PATH_STANDARD . '/app/extensions/controllers/' . $sClass . '.controller.php';
        return '\CandyCMS\Controllers\\' . $sClass;
      }
      else {
        require_once PATH_STANDARD . '/vendor/candyCMS/core/controllers/' . $sClass . '.controller.php';
        return '\CandyCMS\Core\Controllers\\' . $sClass;
      }
    }
  }

  /**
   * Method to include the model files.
   *
   * @access public
   * @param string $sController optional controller to load
   * @return object $this->_oModel
   *
   */
  public function __init($sController = '') {
    $sModel = $this->__autoload($sController ? $sController : $this->_sController, true);

    if ($sModel)
      $this->_oModel = new $sModel($this->_aRequest, $this->_aSession, $this->_aFile);

    return $this->_oModel;
  }

  /**
   * Set up smarty.
   *
   * @access proteced
   * @return object $this->oSmarty
   *
   */
  protected function _setSmarty() {
    # Initialize smarty
    $this->oSmarty = SmartySingleton::getInstance();

    # Clear cache on development mode or when we force it via a request.
    if (isset($this->_aRequest['clearcache']) || WEBSITE_MODE == 'development' || WEBSITE_MODE == 'test') {
      $this->oSmarty->clearAllCache();
      $this->oSmarty->clearCompiledTemplate();
    }

    return $this->oSmarty;
  }

  /**
   * Set meta description.
   *
   * @access public
   * @param string $sDescription description to be set.
   *
   */
  public function setDescription($sDescription = '') {
    if ($sDescription && !$this->_sDescription)
      $this->_sDescription = & $sDescription;
  }

  /**
   * Give back the meta description.
   *
   * @access public
   * @return string meta description
   *
   */
  public function getDescription() {
    if(!$this->_sDescription) {
      # Show default description if this is our landing page or we got no descrption.
      if ($this->_sController == $this->_aSession['routes']['/'])
        $this->setDescription(I18n::get('website.description'));

      elseif (!$this->_sDescription)
        $this->setDescription($this->getTitle());
    }

    return $this->_sDescription;
  }

  /**
   * Set meta keywords.
   *
   * @access public
   * @param string $sKeywords keywords to be set.
   *
   */
  public function setKeywords($sKeywords = '') {
    if ($sKeywords && !$this->_sKeywords)
      $this->_sKeywords = & $sKeywords;
  }

  /**
   * Give back the meta keywords.
   *
   * @access public
   * @return string meta keywords
   *
   */
  public function getKeywords() {
    return $this->_sKeywords ? $this->_sKeywords : I18n::get('website.keywords');
  }

  /**
   * Set meta keywords.
   *
   * @access public
   * @param string $sTitle title to be set.
   *
   */
  public function setTitle($sTitle = '') {
    if ($sTitle && !$this->_sTitle)
      $this->_sTitle = & $sTitle;
  }

  /**
   * Give back the page title.
   *
   * @access public
   * @return string page title
   *
   */
  public function getTitle() {
    if(!$this->_sTitle) {
      if ($this->_sController == 'errors')
        $this->setTitle(I18n::get('error.' . $this->_aRequest['id'] . '.title'));

      # Bugfix: We need that to cache our contents view. This might be SEO critical.
      #elseif(isset($this->_aRequest['seo_title']))
      #  $this->setTitle($this->_removeHighlight(url_decode($this->_aRequest['seo_title'])));

      else
        $this->setTitle(I18n::get('global.' . strtolower(Helper::singleize($this->_sController))));
    }

    return $this->_sTitle;
  }

  /**
   * Set the page content.
   *
   * @access public
   * @param string $sContent HTML content
   * @see vendor/candyCMS/core/helpers/Dispatcher.helper.php
   *
   */
  public function setContent($sContent) {
    $this->_sContent = & $sContent;
  }

  /**
   * Give back the page content (HTML).
   *
   * @access public
   * @return string $this->_sContent
   */
  public function getContent() {
    return $this->_sContent;
  }

  /**
   * Give back ID.
   *
   * @access public
   * @return integer $this->_iId
   *
   */
  public function getId() {
    return $this->_iId;
  }

  /**
   * Quick hack for displaying title without html tags.
   *
   * @static
   * @access protected
   * @param string $sTitle title to modifiy
   * @return string modified title
   *
   */
  protected static function _removeHighlight($sTitle) {
    $sTitle = str_replace('<mark>', '', $sTitle);
    $sTitle = str_replace('</mark>', '', $sTitle);
    return $sTitle;
  }

  /**
   * Set error messages.
   *
   * @access protected
   * @param string $sField field to be checked
   * @param string $sMessage error to be displayed
   * @return object $this due to method chaining
   *
   */
  protected function _setError($sField, $sMessage = '') {
    if ($sField == 'file' || $sField == 'image') {
      if (!isset($this->_aFile[$sField]) || empty($this->_aFile[$sField]['name']))
        $this->_aError[$sField] = $sMessage ?
                $sMessage :
                I18n::get('error.form.missing.file');

//      if (!isset($this->_aFile[$this->_sController]['name'][$sField]))
//        $this->_aError[$sField] = $sMessage ?
//                $sMessage :
//                I18n::get('error.form.missing.file');
    }

    else {
      if (!isset($this->_aRequest[$this->_sController][$sField]) || empty($this->_aRequest[$this->_sController][$sField]))
          $sError = I18n::get('error.form.missing.' . strtolower($sField)) ?
                I18n::get('error.form.missing.' . strtolower($sField)) :
                I18n::get('error.form.missing.standard');

      if ('email' == $sField && !Helper::checkEmailAddress($this->_aRequest[$this->_sController]['email']))
          $sError = $sError ? $sError : I18n::get('error.mail.format');

      if ($sError)
        $this->_aError[$sField] = !$sMessage ? $sError : $sMessage;
    }

    return $this;
  }

  /**
   * Show a entry.
   *
   * @access public
   * @return string HTML
   *
   */
  public function show() {
    $this->oSmarty->setCaching(SmartySingleton::CACHING_LIFETIME_SAVED);

		if (isset($this->_aRequest['type']) && 'json' == $this->_aRequest['type'])
			return $this->_showJSON();

		elseif (isset($this->_aRequest['type']) && 'xml' == $this->_aRequest['type'])
			return $this->_showXML();

		else
			return $this->_show();
  }

	/**
	 * Just a backup method to show entry as XML.
	 *
	 * @access protected
	 * @return string XML
	 *
	 */
	protected function _showXML() {
		return Helper::redirectTo('/errors/404');
	}

	/**
	 * Just a backup method to show entry as JSON.
	 *
	 * @access protected
	 * @return string html
	 *
	 */
	protected function _showJSON() {
		return json_decode(array('error' => 'There is no JSON handling method.'));
	}

	/**
   * Create an action.
   *
   * Create entry or show form template if we have enough rights.
   *
   * @access public
   * @param integer $iUserRole required user right
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  public function create($iUserRole = 3) {
    $this->oSmarty->setCaching(false);

    if ($this->_aSession['user']['role'] < $iUserRole)
      return Helper::errorMessage(I18n::get('error.missing.permission'), '/');

    else
      return isset($this->_aRequest[$this->_sController]) ? $this->_create() : $this->_showFormTemplate();
  }

  /**
   * Update entry or show form template if we have enough rights.
   *
   * @access public
   * @param integer $iUserRole required user right
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  public function update($iUserRole = 3) {
    $this->oSmarty->setCaching(false);

    if ($this->_aSession['user']['role'] < $iUserRole)
      return Helper::errorMessage(I18n::get('error.missing.permission'), '/');

    else
      return isset($this->_aRequest[$this->_sController]) ? $this->_update() : $this->_showFormTemplate();
  }

  /**
   * Delete entry if we have enough rights.
   *
   * @access public
   * @param integer $iUserRole required user right
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  public function destroy($iUserRole = 3) {
    $this->oSmarty->setCaching(false);

    return $this->_aSession['user']['role'] < $iUserRole ?
            Helper::errorMessage(I18n::get('error.missing.permission'), '/') :
            $this->_destroy();
  }

  /**
   * Build form template to create or update an entry.
   *
   * @access protected
   * @param string $sTemplateName name of form template
   * @param string $sTitle title to show
   * @return string HTML content
   *
   */
  protected function _showFormTemplate($sTemplateName = '_form', $sTitle = '') {
    $sTemplateDir  = Helper::getTemplateDir($this->_sController, $sTemplateName);
    $sTemplateFile = Helper::getTemplateType($sTemplateDir, $sTemplateName);

    if ($this->_iId) {
      $aData = $this->_oModel->getId($this->_iId, true);

      if ($sTitle && isset($aData['title']))
        $this->setTitle(vsprintf(I18n::get($sTitle . '.update'), $aData['title']));

      elseif (isset($aData['title']))
        $this->setTitle(vsprintf(I18n::get($this->_sController . '.title.update'), $aData['title']));

      foreach ($aData as $sColumn => $sData)
        $this->oSmarty->assign($sColumn, $sData);
    }
    else {
      foreach ($this->_aRequest[$this->_sController] as $sInput => $sData)
        $this->oSmarty->assign($sInput, $sData);

      if ($sTitle)
        $this->setTitle(I18n::get($sTitle . '.create'));

      else
        $this->setTitle(I18n::get($this->_sController . '.title.create'));
    }

    if ($this->_aError)
      $this->oSmarty->assign('error', $this->_aError);

    $this->oSmarty->setTemplateDir($sTemplateDir);
    return $this->oSmarty->fetch($sTemplateFile, UNIQUE_ID);
  }

  /**
   * Clear all Caches for given Controllers
   *
   * @access protected
   * @param string|array $mAdditionalCaches specify aditional caches to clear on success
   *
   */
  protected function _clearCaches($mAdditionalCaches) {
    if (gettype($mAdditionalCaches) === 'string')
      $this->oSmarty->clearCacheForController($mAdditionalCaches);

    else
      foreach ($mAdditionalCaches as $sCache)
        $this->oSmarty->clearCacheForController($sCache);
  }

  /**
   * Create an entry.
   *
   * Check if required data is given or throw an error instead.
   * If data is given, activate the model, insert them into the database and redirect afterwards.
   *
   * @access protected
   * @param string|array $mAdditionalCaches specify aditional caches to clear on success
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  protected function _create($mAdditionalCaches = null) {
    $this->_setError('title');

    if ($this->_aError)
      return $this->_showFormTemplate();

    else {
      $bResult = $this->_oModel->create() === true;

      Logs::insert( $this->_sController,
                    $this->_aRequest['action'],
                    $this->_oModel->getLastInsertId($this->_sController),
                    $this->_aSession['user']['id'],
                    '', '', $bResult);

      if ($bResult) {
        $this->oSmarty->clearCacheForController($this->_sController);

        # clear additional caches if given
        if ($mAdditionalCaches)
          $this->_clearCaches($mAdditionalCaches);

        return Helper::successMessage(I18n::get('success.create'), '/' . $this->_sController);
      }
      else
        return Helper::errorMessage(I18n::get('error.sql.query'), '/' . $this->_sController);
    }
  }

  /**
   * Update an entry.
   *
   * Activate model, insert data into the database and redirect afterwards.
   *
   * @access protected
   * @param string|array $mAdditionalCaches specify aditional caches to clear on success
   * @param string $sRedirectURL specify the URL to redirect to after execution
   * @return string|boolean HTML content (string) or returned status of model action (boolean).
   *
   */
  protected function _update($mAdditionalCaches = null, $sRedirectURL = '') {
    $this->_setError('title');

    $sRedirectURL = empty($sRedirectURL) ?
            '/' . $this->_aRequest['controller'] . '/' . (int) $this->_aRequest['id'] :
            $sRedirectURL;

    if ($this->_aError)
      return $this->_showFormTemplate();

    else {
      $bReturn = $this->_oModel->update((int) $this->_aRequest['id']) === true;

      Logs::insert( $this->_sController,
                    $this->_aRequest['action'],
                    (int) $this->_aRequest['id'],
                    $this->_aSession['user']['id'],
                    '', '', $bReturn);

      if ($bReturn) {
        $this->oSmarty->clearCacheForController($this->_sController);

        # Clear additional caches if given
        if ($mAdditionalCaches)
          $this->_clearCaches($mAdditionalCaches);

        return Helper::successMessage(I18n::get('success.update'), $sRedirectURL);
      }
      else
        return Helper::errorMessage(I18n::get('error.sql'), $sRedirectURL);
    }
  }

  /**
   * Destroy an entry.
   *
   * Activate model, delete data from database and redirect afterwards.
   *
   * @access protected
   * @param string|array $mAdditionalCaches specify aditional caches to clear on success
   * @return boolean status of model action
   *
   */
  protected function _destroy($mAdditionalCaches = null) {
    $bReturn = $this->_oModel->destroy($this->_iId) === true;

    Logs::insert( $this->_sController,
                  $this->_aRequest['action'],
                  (int) $this->_iId,
                  $this->_aSession['user']['id'],
                  '', '', $bReturn);

    if ($bReturn) {
      $this->oSmarty->clearCacheForController($this->_sController);

      # Clear additional caches if given
      if ($mAdditionalCaches)
        $this->_clearCaches($mAdditionalCaches);

      return Helper::successMessage(I18n::get('success.destroy'), '/' . $this->_sController);
    }

    else
      return Helper::errorMessage(I18n::get('error.sql'), '/' . $this->_sController);
  }

  /**
   * Subscribe to newsletter list.
   *
   * @static
   * @access protected
   * @param array $aData user data
   * @return boolean status of subscription
   *
   */
  protected static function _subscribeToNewsletter($aData, $bDoubleOptIn = false) {
    require_once PATH_STANDARD . '/vendor/mailchimp/mcapi/MCAPI.class.php';

    $oMCAPI = new MCAPI(MAILCHIMP_API_KEY);
    return $oMCAPI->listSubscribe(MAILCHIMP_LIST_ID,
            $aData['email'],
            array('FNAME' => $aData['name'], 'LNAME' => $aData['surname']),
            '',
            $bDoubleOptIn);
  }

  /**
   * Remove from newsletter list
   *
   * @static
   * @access private
   * @param string $sEmail
   * @return boolean status of action
   *
   */
  protected static function _unsubscribeFromNewsletter($sEmail) {
    require_once PATH_STANDARD . '/vendor/mailchimp/mcapi/MCAPI.class.php';

    $oMCAPI = new MCAPI(MAILCHIMP_API_KEY);
    return $oMCAPI->listUnsubscribe(MAILCHIMP_LIST_ID, $sEmail, '', '', false, false);
  }
}