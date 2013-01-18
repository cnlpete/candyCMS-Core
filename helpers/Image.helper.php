<?php

/**
 * Resize images.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.0
 *
 */

namespace candyCMS\Core\Helpers;

use candyCMS\Core\Helpers\Helper;

class Image {

  /**
   * @var array
   * @access protected
   *
   */
  protected $_aInfo;

  /**
   * @var string
   * @access protected
   *
   */
  protected $_sFolder;

  /**
   * @var string
   * @access protected
   *
   */
  protected $_sId;

  /**
   * @var string
   * @access protected
   *
   */
  protected $_sImgType;

  /**
   * @var string
   * @access protected
   *
   */
  protected $_sOriginalPath;

  /**
   * @var string
   * @access protected
   *
   */
  protected $_sUploadDir;

  /**
   * Set up the new image.
   *
   * @access public
   * @param string $sId name of the file
   * @param string $sUploadDir folder to upload image to. Normally the controller name. For gallery use "gallery/id_of_gallery".
   * @param string $sOriginalPath path of the image to clone from incl. file name
   * @param string $sImgType type of image
   *
   */
  public function __construct($sId, $sUploadDir, $sOriginalPath, $sImgType = 'jpg') {
    $this->_sId           = & $sId;
    $this->_sUploadDir    = & $sUploadDir;
    $this->_sOriginalPath = & $sOriginalPath;
    $this->_sImgType      = & $sImgType;
    $this->_aInfo         = getimagesize($this->_sOriginalPath);

    if (!isset($this->_aInfo)) {
      $this->_aInfo[0] = 1;
      $this->_aInfo[1] = 1;
    }
  }

  /**
   * Create the new image with given params.
   *
   * @access private
   * @param integer $iSrcX x-coordinate of source point
   * @param integer $iSrcY y-coordinate of source point
   * @param integer $iDstX destination width
   * @param integer $iDstY destination height
   * @return string $sPath path of the new image
   *
   */
  private function _createImage($iSrcX, $iSrcY, $iDstX, $iDstY) {
    $sPath = Helper::removeSlash(PATH_UPLOAD . '/' . $this->_sUploadDir . '/' .
                    $this->_sFolder . '/' . $this->_sId . '.' . $this->_sImgType);

    if ($this->_sImgType == 'jpg' || $this->_sImgType == 'jpeg')
      $oOldImg = imagecreatefromjpeg($this->_sOriginalPath);

    elseif ($this->_sImgType == 'png')
      $oOldImg = imagecreatefrompng($this->_sOriginalPath);

    elseif ($this->_sImgType == 'gif')
      $oOldImg = imagecreatefromgif($this->_sOriginalPath);

    $oNewImg = imagecreatetruecolor($this->_iImageWidth, $this->_iImageHeight);
    $oBg = imagecolorallocate($oNewImg, 255, 255, 255);

    imagefill($oNewImg, 0, 0, $oBg);
    imagecopyresampled($oNewImg, $oOldImg, 0, 0, $iSrcX, $iSrcY, $iDstX, $iDstY, $this->_aInfo[0], $this->_aInfo[1]);

    if ($this->_sImgType == 'jpg' || $this->_sImgType == 'jpeg')
      imagejpeg($oNewImg, $sPath, 75);

    elseif ($this->_sImgType == 'png') {
      imagealphablending($oNewImg, false);
      imagesavealpha($oNewImg, true);
      imagepng($oNewImg, $sPath, 9);
    }
    elseif ($this->_sImgType == 'gif')
      imagegif($oNewImg, $sPath);

    imagedestroy($oNewImg);

    # Reduce image size via Smush.it
    if (defined('ALLOW_SMUSHIT') && ALLOW_SMUSHIT === true) {
      require_once PATH_STANDARD . '/vendor/smushit/smushit/class.smushit.php';

      # Send information of our created image to the server.
      $oSmushIt = new \SmushIt(WEBSITE_URL . '/' . $sPath);

      # Download new image from Smush.it
      if (empty($oSmushIt->error)) {
        unlink($sPath);
        file_put_contents($sPath, file_get_contents($oSmushIt->compressedUrl));
      }
    }

    return $sPath;
  }

  /**
   * Proportional resizing.
   *
   * @access public
   * @param integer $iDim width of the new image
   * @param integer $iMaxHeight maximum height of the new image
   * @param string $sFolder folder of the new image
   * @return string $sPath path of the new image
   *
   */
  public function resizeDefault($iDim, $iMaxHeight = '', $sFolder = '') {
    # Y bigger than X and max height
    if ($this->_aInfo[1] > $this->_aInfo[0] && $iMaxHeight) {
      $iDstX = round($this->_aInfo[0] * ($iMaxHeight / $this->_aInfo[1]));
      $iDstY = $iMaxHeight;
    }

    # X bigger than Y
    else {
      $iDstX = $iDim;
      $iDstY = round($this->_aInfo[1] * ($iDim / $this->_aInfo[0]));
    }

    $this->_iImageWidth   = $iDstX;
    $this->_iImageHeight  = $iDstY;
    $this->_sFolder = empty($sFolder) ? $iDim : $sFolder;

    return $this->_createImage(0, 0, $iDstX, $iDstY);
  }

  /**
   * Cut resizing.
   *
   * @access public
   * @param integer $iDim width and height of the new image
   * @param string $sFolder folder of the new image
   * @return string $sPath path of the new image
   *
   */
  public function resizeAndCut($iDim, $sFolder = '') {
    # Y bigger than X
    if ($this->_aInfo[1] > $this->_aInfo[0]) {
      $iSrcX = 0;
      $iSrcY = ($this->_aInfo[1] - $this->_aInfo[0]) / 2;

      $iDstX = $iDim;
      $iDstY = round($this->_aInfo[1] * ($iDim / $this->_aInfo[0]));
    }

    # X bigger than Y
    else {
      $iSrcX = ($this->_aInfo[0] - $this->_aInfo[1]) / 2;
      $iSrcY = 0;

      $iDstX = round($this->_aInfo[0] * ($iDim / $this->_aInfo[1]));
      $iDstY = $iDim;
    }

    $this->_iImageWidth   = $iDim;
    $this->_iImageHeight  = $iDim;
    $this->_sFolder = empty($sFolder) ? $iDim : $sFolder;

    return $this->_createImage($iSrcX, $iSrcY, $iDstX, $iDstY);
  }
}