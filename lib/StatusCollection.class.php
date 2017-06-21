<?php

/**
 * Collection of Status entries
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class StatusCollection {
  public $entries, $sortOrder;

  public function __construct () {
    $this->entries = array();
    $this->sortOrder = 'ASC'; // default value
  }

  /**
   * Compare function
   *
   * @param $a {Object: Status instance}
   * @param $b {Object: Status instance}
   *
   * @return {Int}
   */
  private function _compare ($a, $b) {
    if ($a->begin === $b->begin) {
      return 0;
    }

    if ($this->sortOrder === 'ASC') {
      return ($a->begin < $b->begin) ? -1 : 1;
    }
    else { // DESC
      return ($a->begin < $b->begin) ? 1 : -1;
    }
  }

  /**
   * Add a Status instance to the Collection
   *
   * @param $Status {Object}
   */
  public function add ($Status) {
    $this->entries[] = $Status;
  }

  /**
   * Sort by begin date
   *
   * @param $order {String <ASC | DESC>}
   */
  public function sort ($order) {
    if ($order === 'ASC' || $order === 'DESC') {
      $this->sortOrder = $order;
      usort($this->entries, 'self::_compare');
    }
  }
}
