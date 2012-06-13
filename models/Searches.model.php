<?php

/**
 * Create search.
 *
 * @link http://github.com/marcoraddatz/candyCMS
 * @author Marco Raddatz <http://marcoraddatz.com>
 * @license MIT
 * @since 1.5
 *
 */

namespace CandyCMS\Core\Models;

use CandyCMS\Core\Helpers\AdvancedException;
use CandyCMS\Core\Helpers\Helper;
use CandyCMS\Core\Helpers\I18n;
use CandyCMS\Core\Helpers\Pagination;
use PDO;

class Searches extends Main {

  /**
   * Get search information from tables.
   *
   * @access public
   * @param string $sSearch query string to search
   * @param array $aTables tables to search in
   * @param string $sOrderBy how to order search
   * @return array $this->_aData search data
   *
   */
  public function getData($sSearch, $aTables = '', $sOrderBy = 't.date DESC') {
    if (empty($aTables))
      $aTables = array('blogs', 'contents');

    foreach ($aTables as $sTable) {
      try {
        $this->oQuery = $this->_oDb->prepare("SELECT
                                                t.*,
                                                UNIX_TIMESTAMP(t.date) as date,
                                                u.id as user_id,
                                                u.name as user_name,
                                                u.surname as user_surname,
                                                u.email as user_email
                                              FROM
                                                " . SQL_PREFIX . $sTable . " t
                                              JOIN
                                                " . SQL_PREFIX . "users u
                                              ON
                                                u.id = t.author_id
                                              WHERE
                                                t.title LIKE :searchString
                                              OR
                                                t.content LIKE :searchString
                                              ORDER BY
                                                " . (string) $sOrderBy);

        $this->oQuery->bindValue('searchString', '%' . $sSearch . '%', PDO::PARAM_STR);
        $this->oQuery->execute();
        $aResult = $this->oQuery->fetchAll(PDO::FETCH_ASSOC);

        # Build table names and order them
        if ($sTable == 'gallery_albums') {
          $this->_aData[$sTable]['controller'] = 'galleries';
          $this->_aData[$sTable]['title'] = I18n::get('global.albums');
        }
        else {
          $this->_aData[$sTable]['controller'] = $sTable;
          $this->_aData[$sTable]['title'] = I18n::get('global.' . strtolower($sTable));
        }

        $iEntries = 0;
        foreach ($aResult as $aRow) {
          if (isset($aRow['published']) && $aRow['published'] == 0)
            continue;

          $iDate = $aRow['date'];
          $this->_aData[$sTable][$iDate] = $this->_formatForOutput(
                  $aRow,
                  array('id', 'author_id'),
                  null,
                  $this->_aData[$sTable]['controller']);

          ++$iEntries;
        }

        $this->_aData[$sTable]['entries'] = $iEntries;
      }
      catch (\PDOException $p) {
        AdvancedException::reportBoth('0071 - ' . $p->getMessage());
        exit('SQL error.');
      }
    }

    return $this->_aData;
  }
}