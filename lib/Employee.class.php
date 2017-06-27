<?php

/**
 * Model for Employee
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class Employee {
  // Initialize outside constructor so it's available to populate immediately
  private $_data = array();

  public function __construct () {
    // Attach extra (non-db) props
    $this->_data['firstletter'] = $this->_getFirstLetter();
    $this->_data['fullname'] = $this->_getFullName();
    $this->_data['lastfirst'] = $this->_getFullName('lastfirst');
    $this->_data['shortname'] = $this->_getShortName();
  }

  public function __get ($name) {
    return $this->_data[$name];
  }

  public function __set ($name, $value) {
    $this->_data[$name] = $value;
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

  /**
   * Get employee's shortname (text before '@' in email)
   *
   * @return {String}
   */
  private function _getShortName () {
    return strstr($this->_data['email'], '@', true);
  }

  /**
   * Get employee's status right now - return default status if none set
   *
   * @param $timespan {String}
   *     timespan (description) for default status (likely either 'today' or '(Default setting)')
   *
   * @return $Status {Object: Status instance}
   */
  public function getStatusNow ($timespan = 'today') {
    $defaultStatus = 'in the office';
    $statusEntries = $this->_data['status'];

    // Create default Status instance
    $Status = new Status(array(
      'status' => $defaultStatus,
      'timespan' => $timespan,
      'type' => 'default'
    ));

    // Check if employee has any 'present' status entries set
    if (property_exists($statusEntries, 'present')) {
      // Prioritize:
      //  1) non-recurring entries;
      //  2) entries with explicit ending dates;
      //  3) 'newer' entries
      //   (array is sorted by begin date, so 'newer' entries will prevail)
      foreach ($statusEntries->present->entries as $Entry) {
        // Only set status to 'indefinite' entry if it's overriding default value
        if ($Entry->end || $Status->status === $defaultStatus) {
          $Status = $Entry;
        }
      }
    }
    else if (property_exists($statusEntries, 'recurring')) {
      foreach ($statusEntries->recurring->entries as $Entry) {
        if ($Status->isActive()) {
          $Status = $Entry;
        }
      }
    }

    return $Status;
  }
}
