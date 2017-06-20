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
   * Add a Status instance to the Collection
   *
   * @param $Status {Object}
   */
  public function add ($Status) {
    $this->entries[] = $Status;
  }
}
