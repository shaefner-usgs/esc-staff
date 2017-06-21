<?php

/**
 * Collection of Employees
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class EmployeeCollection {
  public $employees, $sortBy;

  public function __construct () {
    $this->employees = array();
    $this->sortBy = 'name'; // default value
  }

  /**
   * Add an Employee instance to the Collection
   *
   * @param $Employee {Object: Employee instance}
   */
  public function add ($Employee) {
    $this->employees[] = $Employee;
  }

  /**
   * Sort employees
   *
   * @param $sortBy {String <name | location | status>}
   */
  public function sort ($sortBy) {
    if ($sortBy !== 'name' && $sortBy !== 'location' && $sortBy !== 'status') {
      return;
    }

    // array_multisort() requires an array of columns; put sort fields in columns
    foreach ($this->employees as $index => $Employee) {
      // Set sort fields to all lowercase to force case-insensitive sort
      $firstname[$index] = strtolower($Employee->firstname);
      $lastname[$index] = strtolower($Employee->lastname);
      $location[$index] = strtolower($Employee->location);
      $status[$index] = strtolower($Employee->getStatusNow()->status);
    }

    if ($sortBy === 'name') {
      array_multisort($lastname, SORT_ASC, $firstname, SORT_ASC, $this->employees);
    }
    else if ($sortBy === 'location') {
      array_multisort($location, SORT_ASC, $lastname, SORT_ASC, $this->employees);
    }
    else if ($sortBy === 'status') {
      array_multisort($status, SORT_ASC, $lastname, SORT_ASC, $firstname, SORT_ASC, $this->employees);
    }

    $this->sortBy = $sortBy;
  }
}
