<?php

/**
 * Parent class for most other models. Handles also DB insertations.
 *
 * @abstract
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.5
 *
 */

namespace CandyCMS\Core\Models;

use CandyCMS\Core\Helpers\Helper;
use CandyCMS\Core\Helpers\AdvancedException;
use PDO;

abstract class Main {

  /**
   * Alias for $_REQUEST
   *
   * @var array
   * @access protected
   *
   */
  protected $_aRequest = array();

  /**
   * Alias for $_SESSION
   *
   * @var array
   * @access protected
   *
   */
  protected $_aSession = array();

  /**
   * Returned data from models.
   *
   * @var array
   * @access protected
   *
   */
  protected $_aData = array();

  /**
   * ID to process.
   *
   * @var integer
   * @access protected
   *
   */
  protected $_iId;

  /**
   * Name of the current controller.
   *
   * @var string
   * @access protected
   */
  protected $_sController;

  /**
   * PDO object.
   *
   * @var object
   * @access protected
   *
   */
  protected $_oDb;

  /**
   * Page object.
   *
   * @var object
   * @access public
   *
   */
  public $oPagination;

  /**
   * Database connection.
   *
   * @var object
   * @access protected
   *
   */
  static $_oDbStatic;

  /**
   * Return ID of last inserted data.
   *
   * @var integer
   * @access public
   *
   */
  static $iLastInsertId;

  /**
   * Initialize the model by adding input params, set default id connect to database.
   *
   * @access public
   * @param array $aRequest alias for the combination of $_GET and $_POST
   * @param array $aSession alias for $_SESSION
   * @param array $aFile alias for $_FILE
   *
   */
  public function __construct(&$aRequest = '', &$aSession = '', &$aFile = '') {
    $this->_aRequest  = & $aRequest;
    $this->_aSession  = & $aSession;
    $this->_aFile     = & $aFile;

    $this->_iId = isset($this->_aRequest['id']) && !isset($this->_iId) ? (int) $this->_aRequest['id'] : '';
    $this->_oDb = $this->connectToDatabase();
    $this->_sController = $this->_aRequest['controller'];
  }

  /**
   * Close DB connection.
   *
   * @access public
   * @return null
   *
   */
  public function __destruct() {
    # Not unsetting the database, because it is unset by Index.controller.php.
  }

  /**
   * Get a Singleton database PDO Object.
   *
   * @static
   * @access public
   * @return object PDO
   *
   */
  public static function connectToDatabase() {
    if (empty(self::$_oDbStatic)) {
      try {
				$sDatabase = defined('SQL_SINGLE_DB_MODE') && SQL_SINGLE_DB_MODE === true ?
								SQL_DB :
								SQL_DB . '_' . WEBSITE_MODE;

        self::$_oDbStatic = new PDO('mysql:host=' . SQL_HOST . ';port=' . SQL_PORT . ';dbname=' . $sDatabase,
                        SQL_USER,
                        SQL_PASSWORD,
                        array(PDO::ATTR_PERSISTENT => true));

        self::$_oDbStatic->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      }
      catch (PDOException $p) {
        AdvancedException::reportBoth('0102 - ' . $p->getMessage());
        exit('SQL error.');
      }
    }

    return self::$_oDbStatic;
  }

  /**
   * Disconnect from database.
   *
   * @static
   * @access public
   * @return boolean
   *
   */
  public static function disconnectFromDatabase() {
    return self::$_oDbStatic = null;
  }

  /**
   * Remove slashes from content for update purposes.
   *
   * @static
   * @access protected
   * @param array $aRow array with data to update
   * @return array $aData data witout slashes
   *
   */
  protected static function _formatForUpdate($aRow) {
    $aData = array();

    foreach ($aRow as $sColumn => $sData) {

			# Bugfix: Avoid TinyMCE problems.
			$sData = str_replace('\"', '', $sData);
			$sData = str_replace('\&quot;', '', $sData);
      $aData[$sColumn] = $sData;
		}

    return $aData;
  }

  /**
   * Format necessary datetime stamps and add them to $aData
   *
   * @static
   * @access protected
   * @param array $aData array with the timestamp stored in '$sKey'
   * @param string $sKey the key, where the date is stored in $aData
   * @return array reference to $aData
   *
   */
  protected static function _formatDates(&$aData, $sKey = 'date') {
    if (isset($aData[$sKey])) {
      $iTimeStamp = (int) $aData[$sKey];
      $aDateData = Array(
        'raw'       => $iTimeStamp,
        'rss'       => date('D, d M Y H:i:s O', $iTimeStamp),
        'w3c'       => date('Y-m-d\TH:i:sP', $iTimeStamp),
        'w3c_date'  => date('Y-m-d', $iTimeStamp),
      );

      $aData[$sKey] = $aDateData;
    }

    return $aData;
  }

  /**
   * Format data correctly.
   *
   * @access protected
   * @param array $aData array with data to format
   * @param array $aInts identifiers, which should be cast to int
   * @param array $aBools identifiers, which should be cast to bool
   * @param string $sController name of the controller we are working in
   * @return array $aData rebuild data
   *
   */
  protected function _formatForOutput(&$aData, $aInts = array('id'), $aBools = null, $sController = '') {
    $sController = !$sController ? $this->_sController : $sController;

    foreach (array('content', 'teaser', 'title') as $sColumn)
      $aData[$sColumn] = isset($aData[$sColumn]) ? Helper::formatOutput($aData[$sColumn]) : '';

    # Bugfix: Set types
    if ($aInts)
      foreach ($aInts as $sIdent)
        if (isset($aData[$sIdent]))
          $aData[$sIdent] = (int) $aData[$sIdent];

    if ($aBools)
      foreach ($aBools as $sIdent)
        if (isset($aData[$sIdent]))
          $aData[$sIdent] = (bool) $aData[$sIdent];

    # Format data
    self::_formatDates($aData);

    # Set sitemaps.xml data
    if (isset($aData['date']['raw'])) {
      $iTimestampNow = time();

      # Entry is less than a day old
      if($iTimestampNow - $aData['date']['raw'] < 86400) {
        $aData['changefreq']  = 'hourly';
        $aData['priority']    = '1.0';
      }
      # Entry is younger than a week
      elseif($iTimestampNow - $aData['date']['raw'] < 86400 * 7) {
        $aData['changefreq']  = 'daily';
        $aData['priority']    = '0.9';
      }
      # Entry is younger than a month
      elseif($iTimestampNow - $aData['date']['raw'] < 86400 * 31) {
        $aData['changefreq']  = 'weekly';
        $aData['priority']    = '0.75';
      }
      # Entry is younger than three month
      elseif($iTimestampNow - $aData['date']['raw'] < 86400 * 90) {
        $aData['changefreq']  = 'monthly';
        $aData['priority']    = '0.6';
      }
      # Entry is younger than half a year
      elseif($iTimestampNow - $aData['date']['raw'] < 86400 * 180) {
        $aData['changefreq']  = 'monthly';
        $aData['priority']    = '0.4';
      }
      # Entry is younger than a year
      elseif($iTimestampNow - $aData['date']['raw'] < 86400 * 360) {
        $aData['changefreq']  = 'monthly';
        $aData['priority']    = '0.25';
      }
      # Entry older than half year
      else {
        $aData['changefreq']  = 'yearly';
        $aData['priority']    = '0.1';
      }
    }

    # Normal user
    if ($aData['user_id'] != 0) {
      $aUserData = array(
          'email'         => $aData['user_email'],
          'id'            => $aData['user_id'],
          'use_gravatar'  => isset($aData['use_gravatar']) ? (bool) $aData['use_gravatar'] : false,
          'name'          => $aData['user_name'],
          'surname'       => $aData['user_surname'],
          'facebook_id'   => isset($aData['author_facebook_id']) ? $aData['author_facebook_id'] : '',
          'ip'            => isset($aData['author_ip']) ? $aData['author_ip'] : '',
      );
    }

    # We don't have a user (comments) and format the user given data instead.
    else {
      $aUserData = array(
          'email'         => isset($aData['author_email']) ? $aData['author_email'] : WEBSITE_MAIL,
          'id'            => isset($aData['author_id']) ? $aData['author_id'] : 0,
          'use_gravatar'  => isset($aData['use_gravatar']) ? (bool) $aData['use_gravatar'] : true,
          'name'          => isset($aData['author_name']) ? $aData['author_name'] : '',
          'surname'       => '',
          'facebook_id'   => isset($aData['author_facebook_id']) ? $aData['author_facebook_id'] : '',
          'ip'            => isset($aData['author_ip']) ? $aData['author_ip'] : '',
      );
    }

    $aData['author'] = self::_formatForUserOutput($aUserData);

    # Encode data for SEO
    $aData['title_encoded'] = isset($aData['title']) ? urlencode($aData['title']) : $aData['author']['encoded_full_name'];

    # URL to entry
    $aData['url_clean']   = WEBSITE_URL . '/' . $sController . '/' . $aData['id'];
    $aData['url']         = $aData['url_clean'] . '/' . $aData['title_encoded'];
    $aData['url_encoded'] = urlencode($aData['url']); #SEO
    $aData['url_destroy'] = $aData['url_clean'] . '/destroy';
    $aData['url_update']  = $aData['url_clean'] . '/update';

    # Do we need to highlight text?
    $sHighlight = isset($this->_aRequest['highlight']) ? $this->_aRequest['highlight'] : '';

    # Highlight text for search results
    if(!empty($sHighlight)) {
      $aData['title']   = isset($aData['title']) ? Helper::formatOutput($aData['title'], $sHighlight) : '';
      $aData['teaser']  = isset($aData['teaser']) ? Helper::formatOutput($aData['teaser'], $sHighlight) : '';
      $aData['content'] = Helper::formatOutput($aData['content'], $sHighlight);
    }

    return $aData;
  }

  /**
   * Formats / adds all relevant Information for displaying a user.
   *
   * @static
   * @access protected
   * @param array $aData array of given userdata, required fields are 'email', 'id', 'name', 'surname' and 'use_gravatar'
   * @return array $aData returns reference of $aData
   *
   */
  protected static function _formatForUserOutput(&$aData) {
    # Set up ints first
    $aData['id']    = (int) $aData['id'];
    $aData['role']  = (int) isset($aData['role']) ? $aData['role'] : 0;

    self::_formatDates($aData);

    # Create avatars
    Helper::createAvatarURLs($aData,
            $aData['id'],
            isset($aData['email']) ? $aData['email'] : WEBSITE_MAIL,
            isset($aData['use_gravatar']) ? (bool) $aData['use_gravatar'] : false);

    # Build full user name
    $aData['name']      = isset($aData['name']) ? (string) $aData['name'] : '';
    $aData['surname']   = isset($aData['surname']) ? (string) $aData['surname'] : '';
    $aData['full_name'] = trim($aData['name'] . ' ' . $aData['surname']);

    # Encode data for SEO
    $aData['encoded_full_name'] = urlencode($aData['full_name']);

    # URL to entry
    $aData['url_clean']   = WEBSITE_URL . '/users/' . $aData['id'];
    $aData['url']         = $aData['url_clean'] . '/' . $aData['encoded_full_name'];
    $aData['url_encoded'] = urlencode($aData['url']);

    $aData['url_destroy'] = $aData['url_clean'] . '/destroy';
    $aData['url_update']  = $aData['url_clean'] . '/update';

    return $aData;
  }

  /**
   * Return last inserted ID.
   *
   * @static
   * @access public
   * @return integer self::$iLastInsertId last inserted ID.
   *
   */
  public static function getLastInsertId() {
    return self::$iLastInsertId;
  }

  /**
   * Return data for autocompletion.
   *
   * @static
   * @access public
   * @param string $sTable table to get data from
   * @param string $sColumn column to get data from
   * @param boolean $bSplit split data by comma
   * @return string formatted data
   *
   */
  public static function getTypeaheadData($sTable, $sColumn, $bSplit = false) {
    try {
      $oQuery = self::$_oDbStatic->query("SELECT
                                            " . $sColumn . "
                                          FROM
                                            " . SQL_PREFIX . $sTable . "
                                          GROUP BY
                                            " . $sColumn);

      $aResult = $oQuery->fetchAll(PDO::FETCH_ASSOC);

      $aEntries = array();
      foreach ($aResult as $aRow) {
        if ($bSplit === true) {
          $aItems = array_filter(array_map('trim', explode(',', $aRow[$sColumn])));

          foreach ($aItems as $sItem)
            $aEntries[] = $sItem;
        }

        else
          $aEntries[] = $aRow[$sColumn];
      }

      return json_encode($aEntries);
    }
    catch (\PDOException $p) {
      try {
        parent::$_oDbStatic->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0099 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0100 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }

  /**
   * Dynamically load models.
   *
   * @static
   * @access public
   * @param string $sClass name of model to load
   * @return string model name
   *
   */
  public static function __autoload($sClass) {
    $sClass = (string) ucfirst(strtolower($sClass));

    if (EXTENSION_CHECK && file_exists(PATH_STANDARD . '/app/extensions/models/' . $sClass . '.model.php')) {
      require_once PATH_STANDARD . '/app/extensions/models/' . $sClass . '.model.php';
      return '\CandyCMS\Models\\' . $sClass;
    }
    elseif (file_exists(PATH_STANDARD . '/vendor/candyCMS/core/models/' . $sClass . '.model.php')) {
      require_once PATH_STANDARD . '/vendor/candyCMS/core/models/' . $sClass . '.model.php';
      return '\CandyCMS\Core\Models\\' . $sClass;
    }
  }

  /**
   * Destroy an entry.
   *
   * @access public
   * @param integer $iId ID to destroy
   * @param string $sController controller to use
   * @return boolean status of query
   *
   */
  public function destroy($iId, $sController = '') {
    $sController = $sController ? (string) $sController : (string) $this->_sController;

    try {
      $oQuery = $this->_oDb->prepare("DELETE FROM
                                        " . SQL_PREFIX . $sController . "
                                      WHERE
                                        id = :id
                                      LIMIT
                                        1");

      $oQuery->bindParam('id', $iId, PDO::PARAM_INT);
      return $oQuery->execute();
    }
    catch (\PDOException $p) {
      try {
        $this->_oDb->rollBack();
      }
      catch (\Exception $e) {
        AdvancedException::reportBoth('0112 - ' . $e->getMessage());
      }

      AdvancedException::reportBoth('0113 - ' . $p->getMessage());
      exit('SQL error.');
    }
  }
}