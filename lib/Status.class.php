<?php

/**
 * Model for Employee Status Entry
 *
 * @params $params {Array}
 *     key-value pairs to assign as properties to a new instance (also uses magic
 *     set method to assign properties from a db query)
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class Status {
  // Initialize outside constructor so it's available to populate immediately
  private $_data = array();

  public function __construct ($params=NULL) {
    // Params passed to constructor will override corresponding key-value pairs
    // from database when using PDO::FETCH_CLASS to instantiate
    if (is_array($params)) {
      foreach ($params as $key => $value) {
        $this->_data[$key] = $value;
      }
    }

    $this->_setRequiredProps();
  }

  public function __get ($name) {
    return $this->_data[$name];
  }

  public function __set ($name, $value) {
    $this->_data[$name] = $value;
  }

  /**
   * Only keep fields which user is allowed to manipulate (via web form)
   *   also sets date values to format expected by db schema
   *
   * @return $keep {Array}
   */
  private function _filterSqlData () {
    $allowedFields = array(
      'backup', 'begin', 'changed', 'comments', 'contact', 'end', 'ip',
      'friday', 'monday', 'recurring', 'shortname', 'status', 'thursday',
      'tuesday', 'wednesday'
    );

    // Set begin / end dates to correct format (NULL or yyyy-mm-dd)
    $indefinite = isset($this->_data['indefinite']) && $this->_data['indefinite'] === '1';
    if ($indefinite) {
      $this->_data['end'] = NULL;
    } else {
      if ($this->_data['begin'] && !$this->_data['end']) {
        // Default to 1 day if no end value set
        $this->_data['end'] = $this->_data['begin'];
      }
    }
    if ($this->_data['begin']) {
      $this->_data['begin'] = date('Y-m-d', strtotime($this->_data['begin']));
    } else {
      $this->_data['begin'] = NULL;
    }
    if ($this->_data['end']) {
      $this->_data['end'] = date('Y-m-d', strtotime($this->_data['end']));
    } else {
      $this->_data['end'] = NULL;
    }

    // Only keep 'allowed' fields
    foreach ($this->_data as $key => $value) {
      if (in_array($key, $allowedFields)) {
        $keep[$key] = $value;
      }
    }

    return $keep;
  }

  /**
   * Be certain all req'd props exist (if not, create and set value to NULL or 0)
   *   required properties correspond to form fields on add / edit forms
   */
  private function _setRequiredProps () {
    $reqProps = array('backup', 'begin', 'comments', 'contact', 'end',
      'friday', 'monday', 'recurring', 'status', 'thursday', 'tuesday',
      'wednesday'
    );
    $setToZero = array('friday', 'monday', 'recurring', 'thursday', 'tuesday',
      'wednesday'
    );

    foreach ($reqProps as $prop) {
      if (!array_key_exists($prop, $this->_data)) {
        if (in_array($prop, $setToZero)) {
          $this->_data[$prop] = 0;
        } else {
          $this->_data[$prop] = NULL;
        }
      }
    }
  }

  /**
   * Add status entry to the db
   *
   * @param $Db {Object: Db instance}
   */
  public function add ($Db) {
    $data = $this->_filterSqlData();

    $Db->addStatusEntry($data);
  }

  /**
   * Delete status entry from the db
   *
   * @param $Db {Object: Db instance}
   */
  public function delete ($Db) {
    $id = $this->_data['id'];

    $Db->deleteStatusEntry($id);
  }

  /**
   * Edit status entry in the db
   *
   * @param $Db {Object: Db instance}
   */
  public function edit ($Db) {
    $data = $this->_filterSqlData();
    $id = $this->_data['id'];

    $Db->editStatusEntry($id, $data);
  }

  /**
   * Format a date for text field in form (mm/dd/yyyy)
   *
   * @param $date {String}
   *
   * @return $formatted {String}
   */
  public function formatDate ($date) {
    if ($date) {
      return date('m/d/Y', strtotime($date));
    }
  }

  /**
   * Create HTML for status entry
   *
   * @param $showButtons {Boolean}
   *     Controls whether or not edit / delete buttons are created
   * @param $active {Boolean}
   *     Controls whether or not a checkmark is displayed to indicate active state
   *
   * @return $html {String}
   */
  public function getHtml ($showButtons=false, $active=false) {
    $actionButtons = '';
    $checkmark = '';
    $cssClass = '';
    $trs = '';

    // Create 'Edit' and 'Delete' buttons
    if ($showButtons && strtolower($this->_data['status']) !== 'in the office') {
      $actionButtons = sprintf('<ul class="actions">
          <li>
            <a href="/contact/staff/%s/status/%d/" class="radius button tiny secondary">Edit</a>
          </li>
          <li>
            <a href="/contact/staff/%s/status/%d/delete/" class="delete radius button tiny secondary">Delete</a>
          </li>
        </ul>',
        $this->_data['shortname'],
        $this->_data['id'],
        $this->_data['shortname'],
        $this->_data['id']
      );
    }

    if ($active) {
      $checkmark = '<i class="fa fa-check" aria-hidden="true"></i>';
    }

    if ($this->_data['type'] === 'future') {
      $cssClass = 'secondary future';
    }
    else if ($this->_data['type'] === 'past') {
      $cssClass = 'secondary past';
    }

    $html = sprintf('<div class="alert-box %s">%s %s <em>%s</em>%s</div>',
      $cssClass,
      ucfirst($this->_data['status']),
      $checkmark,
      $this->getTimeSpan(),
      $actionButtons
    );

    if ($this->_data['contact'] && $this->_data['status'] !== 'annual leave') {
      $trs .= sprintf('<tr><th>Contact info</th><td>%s</td></tr>',
        $this->_data['contact']
       );
    }
    if ($this->_data['backup'] && $this->_data['status'] !== 'working at home') {
      $trs .= sprintf('<tr><th>Backup person</th><td>%s</td></tr>',
        $this->_data['backup']
      );
    }
    if ($this->_data['comments']) {
      $trs .= sprintf('<tr><th>Comments</th><td>%s</td></tr>',
        $this->_data['comments']
       );
    }
    if ($trs) {
      $html .= sprintf('<table class="status">%s</table>', $trs);
    }

    return $html;
  }

  /**
   * Get timespan of status entry in a 'friendly' format
   *
   * @return $timespan {String}
   */
  public function getTimeSpan () {
    $timespan = ''; // default

    // If a value was set (during instantiation), return it
    if (isset($this->_data['timespan'])) {
      return $this->_data['timespan'];
    }

    // For recurring entries, get the list of days that apply
    if ($this->_data['recurring'] === '1') {
      $days = array(
        'monday', 'tuesday', 'wednesday', 'thursday', 'friday'
      );

      foreach ($days as $day) {
        if ($this->_data[$day] === '1') {
          $recDays[] = ucwords($day) . 's';
        }
      }
      if (is_array($recDays)) {
        $timespan = implode(', ', $recDays);
      }
    }

    // Calculate timespan if entry has at least a begin date
    if ($this->_data['begin']) {
      $begin = $this->_data['begin'];
      $end = $this->_data['end'];

      $beginTimestamp = strtotime($begin);
      $endTimestamp =strtotime($end);

      $currentYear = date('Y');
      $beginYear = date('Y', strtotime($begin));
      $endYear = date('Y', strtotime($end));
      $multYears = false;
      if ($beginYear !== $endYear) {
        $multYears = true;
      }

      $dateFormat = 'F j';
      if ($multYears || !$end) { // add year if status spans mult. years or indefinite
        $dateFormat .= ', Y';
      }

      if ($end) { // end date specified
        $beginMonth = date('Y-m', $beginTimestamp); // month with year prepended
        $endMonth = date('Y-m', $endTimestamp); // month with year prepended

        // Start with begin date
        $timespan = date($dateFormat, $beginTimestamp);

        // Add end date (if same month and year, shorten end date to just the day)
        if ($begin !== $end) {
          if ($beginMonth === $endMonth && !$multYears) {
            $dateFormat = 'j';
          }
          $timespan .= '&ndash;' . date($dateFormat, $endTimestamp);
        }

        // include year for past events that don't already have it (already included if date range spans mult. years)
        if ($currentYear !== $endYear && !$multYears) {
          $timespan .= ', ' . $endYear;
        }
      }
      else { // no end date specified (indefinite)
        $timespan = 'Indefinite, beginning ' . date($dateFormat, $beginTimestamp);
      }
    }

    return $timespan;
  }
}
