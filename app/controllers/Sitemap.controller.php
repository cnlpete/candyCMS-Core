<?php

/**
 * Print out sitemap as HTML or XML.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 2.0
 */

namespace CandyCMS\Controller;

require_once 'app/models/Blog.model.php';
require_once 'app/models/Content.model.php';
require_once 'app/models/Gallery.model.php';
require_once 'app/models/User.model.php';

class Sitemap extends \CandyCMS\Controller\Main {

	/**
	 * Show the sitemap as XML.
	 *
	 * @access public
	 * @return string XML content
	 *
	 */
	public function showXML() {
		Header('Content-Type: text/xml');

		$this->_oSmarty->assign('_website_landing_page_', WEBSITE_URL . '/' . WEBSITE_LANDING_PAGE);
		$this->_oSmarty->assign('_website_url_', WEBSITE_URL);

		$this->_getSitemap();

		$this->_oSmarty->template_dir = \CandyCMS\Helper\Helper::getTemplateDir('sitemaps', 'xml');
		return $this->_oSmarty->fetch('xml.tpl');
	}

	/**
	 * Show the sitemap as HTML.
	 *
	 * @access public
	 * @return string HTML content
	 *
	 */
	public function show() {
		$this->_getSitemap();

		$this->_oSmarty->template_dir = \CandyCMS\Helper\Helper::getTemplateDir('sitemaps', 'show');
		return $this->_oSmarty->fetch('show.tpl');
	}

	/**
	 * Generate the sitemap. Query tables and build structure.
	 *
	 * @access private
	 *
	 */
	private function _getSitemap() {
		$oBlog = new \CandyCMS\Model\Blog();
		$aBlog = $oBlog->getData('', false, 1000);

		$oContent = new \CandyCMS\Model\Content();
		$aContent = $oContent->getData('', false, 1000);

		$oGallery = new \CandyCMS\Model\Gallery();
		$aGallery = $oGallery->getData('', false, false, 1000);

		$this->_oSmarty->assign('blog', $aBlog);
		$this->_oSmarty->assign('content', $aContent);
		$this->_oSmarty->assign('gallery', $aGallery);
	}
}