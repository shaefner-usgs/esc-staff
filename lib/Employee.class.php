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

  public function __get ($key) {
    if (isset($this->_data[$key])) {
      return $this->_data[$key];
    }
  }

  public function __set ($key, $value) {
    $this->_data[$key] = $value;
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
   * Get employee's status right now (or return default status if none set)
   *
   * Returns a single, 'winning' status entry by prioritizing, in this order:
   *  1) non-recurring entries;
   *  2) entries with explicit ending dates;
   *  3) 'newer' entries (based on begin date)
   *  4) entries changed by user more recently
   *
   * arrays are sorted by begin date / changed date, so 'newer' entries will
   * prevail as they are looped over and overwritten

   * @param $options {Array}
   *     Optional: key-value pairs for default status if none set by user
   *
   * @return $Status {Object: Status instance}
   */
  public function getStatusNow ($options=array()) {
    $statusEntries = $this->_data['status'];

    // Create a default Status instance
    $defaultOptions = array(
      'status' => 'in the office',
      'timespan' => 'today',
      'type' => 'default'
    );
    $options = array_merge($defaultOptions, $options);

    $Status = new Status($options);

    // 1st, check if employee has any 'present' status entries set
    if (property_exists($statusEntries, 'present')) {
      foreach ($statusEntries->present->entries as $Entry) {
        // Only set status to 'indefinite' entry if it's overriding default value
        if ($Entry->end || $Status->status === $options['status']) {
          $Status = $Entry;
        }
      }
    }
    // If no 'present' status entries, check for recurring
    else if (property_exists($statusEntries, 'recurring')) {
      foreach ($statusEntries->recurring->entries as $Entry) {
        if ($Entry->isActive()) {
          $Status = $Entry;
        }
      }
    }

    return $Status;
  }
}
