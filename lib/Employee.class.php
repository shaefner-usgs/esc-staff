<?php

/**
 * Model for ESC Employee
 *
 * @param $statusEntries {Array}
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class Employee {
  // Initialize outside constructor so it's available to populate immediately
  private $_data = array();

  public function __construct ($statusEntries=NULL) {
    // Attach extra (non-db) props
    $this->_data['firstletter'] = $this->_getFirstLetter();
    $this->_data['fullname'] = $this->_getFullName();
    $this->_data['lastfirst'] = $this->_getFullName('lastfirst');
    $this->_data['shortname'] = $this->_getShortName();

    // Convenience property - set current status (String} if status entries supplied
    if (is_array($statusEntries)) {
      $this->_data['status'] = $this->_getCurrentStatus($statusEntries);
    }
  }

  public function __get ($name) {
    return $this->_data[$name];
  }

  public function __set ($name, $value) {
    $this->_data[$name] = $value;
  }

  /**
   * Get employee's shortname (text before '@' in email)
   *
   * @return {String}
   */
  private function _getShortName () {
    return strstr($this->_data['email'], '@', true);
  }

  /**
   * Get employee's current status
   *
   * @param $statusEntries {Array}
   *     Multi-dimensional array grouped by user's shortname
   *
   * return $Status {Object}
   */
  private function _getCurrentStatus ($statusEntries) {
    $default = 'in the office';
    $Status = new Status(array('status' => $default)); // instantiate w/ default value

    // Get status entries for employee
    if (array_key_exists($this->_data['shortname'], $statusEntries)) {
      $entries = $statusEntries[$this->_data['shortname']];

      // Prioritize 1) entries with explicit ending dates; 2) 'newer' entries
      //   (array is sorted by begin date, so 'newer' entries will prevail)
      foreach ($entries as $entry) {
        // Only set status to 'indefinite' entry if it's overriding default value
        if ($entry['end'] || $Status->status === $default) {
          $Status->begin = $entry['begin'];
          $Status->end = $entry['end'];
          $Status->status = $entry['status'];
        }
      }
    }

    return $Status;
  }

  /**
   * Get first letter of employee's last name
   *
   * @return {String}
   */
  private function _getFirstLetter () {
    return strtoupper(substr($this->_data['lastname'], 0, 1));
  }

  /**
   * Get full name of employee, including middlename / nickname
   *
   * @param $lastFirst {Boolean}
   *
   * @return $fullname {String}
   */
  private function _getFullName ($lastFirst=false) {
    $middle = '';
    if ($this->_data['middlename']) {
      $middle = ' ' . $this->_data['middlename'];
    }
    $nick = '';
    if ($this->_data['nickname']) {
      $nick = ' (' . $this->_data['nickname'] . ')';
    }
    $firstName = $this->_data['firstname'] . $nick . $middle;

    if ($lastFirst) {
      $fullname = $this->_data['lastname'] . ', ' . $firstName;
    } else {
      $fullname = $firstName . ' ' . $this->_data['lastname'];
    }

    return $fullname;
  }
}
