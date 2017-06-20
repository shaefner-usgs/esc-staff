<?php

date_default_timezone_set('America/Los_Angeles');

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
    $reqFields = array('backup', 'begin', 'comments', 'contact', 'end', 'status');

    // Params passed to constructor will override corresponding key-value pairs
    // from database when using PDO::FETCH_CLASS to instantiate
    if (is_array($params)) {
      foreach ($params as $key=>$value) {

        // Set begin / end dates to format expected by db schema
        if ($key === 'begin' && $value !== '') {
          $value = date('Y-m-d', strtotime($params['begin']));
        }
        if ($key === 'end') {
          // Indefinite - prob. not nec. to check b/c 'end' should be disabled and therefore not included in $_POST
          if (isset($params['indefinite']) && $params['indefinite'] === 'true') {
            $value = NULL;
          }
          // No end date set; default to begin date
          else if ($params['end'] === '') {
        	  $value = date('Y-m-d', strtotime($params['begin']));
          }
          else {
            $value = date('Y-m-d', strtotime($params['end']));
          }
        }

        $this->_data[$key] = $value;
      }
    }
    // Be certain all req'd fields exist (if not, create and set values to NULL)
    foreach ($reqFields as $field) {
      if (!array_key_exists($field, $this->_data)) {
        // Must be set to NULL so that 'end' is NULL in db when not set
        $this->_data[$field] = NULL;
      }
    }
  }

  public function __get ($name) {
    return $this->_data[$name];
  }

  public function __set ($name, $value) {
    $this->_data[$name] = $value;
  }

  /**
   * Only keep fields which user is allowed to manipulate (via web form)
   *
   * @return $keep {Array}
   */
  private function _filterSqlData () {
    $allowedFields = array(
      'backup', 'begin', 'changed', 'comments', 'contact', 'end', 'ip', 'shortname', 'status'
    );

    foreach ($this->_data as $key => $value) {
      if (in_array($key, $allowedFields)) {
        $keep[$key] = $value;
      }
    }

    return $keep;
  }

  /**
   * Add a status entry to the db
   *
   * @param $Db {Db Class instance}
   */
  public function add ($Db) {
    $data = $this->_filterSqlData();

    $Db->addStatusEntry($data);
  }

  /**
   * Delete a status entry from the db
   *
   * @param $Db {Db Class instance}
   */
  public function delete ($Db) {
    $id = $this->_data['id'];

    $Db->deleteStatusEntry($id);
  }

  /**
   * Edit a status entry in the db
   *
   * @param $Db {Db Class instance}
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
    $formatted = '';

    if ($date) {
      $formatted = date('m/d/Y', strtotime($date));
    }

    return $formatted;
  }

  /**
   * Create HTML for status entry
   *
   * @param $type {String <past | current | future>
   * @param $showButtons {Boolean}
   *     Controls whether or not edit / delete buttons are created
   *
   * @return $html {String}
   */
  public function getHtml ($type, $showButtons=false) {
    $trs = '';
    $cssClass = '';

    if ($type === 'future') {
      $cssClass = 'secondary future';
    }
    else if ($type === 'past') {
      $cssClass = 'secondary past';
    }

    // Create 'Edit' and 'Delete' buttons
    $actionButtons = '';
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

		$html = sprintf('<div class="alert-box %s">%s <em>%s</em>%s</div>',
		  $cssClass,
		  ucfirst($this->_data['status']),
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
