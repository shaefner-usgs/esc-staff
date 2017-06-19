<?php

/**
 * Database queries for staff list and whiteboard
 *
 * App uses 2 db connectors:
 *   1 for status table (internal) and 1 for employees, locations (external)
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class Db {
  private $_dbExt, $_dbInt, $_ip;

  public function __construct() {
    // Internal db connector - status entries (sets $db)
    include_once $_SERVER['DOCUMENT_ROOT'] . '/template/db/dbConnect-escintWrite.inc.php';
    $this->_dbInt = $db;

    // External db connector - employees, locations (also sets $db)
    include_once $_SERVER['DOCUMENT_ROOT'] . '/template/db/dbConnect-escRead.inc.php';
    $this->_dbExt = $db;

    $this->_ip = $_SERVER['REMOTE_ADDR'];
  }

  /**
   * Perform db query
   *
   * @param $sql {String}
   *     SQL query
   * @param $params {Array} default is NULL
   *     key-value substitution params for SQL query
   *
   * @return $stmt {Object} - PDOStatement object
   */
  private function _execQuery ($sql, $params=NULL) {
    try {
      // Use appropriate connector depending on which table is being queried
      if (preg_match('/esc_statusEntries/', $sql)) {
        $stmt = $this->_dbInt->prepare($sql);
      } else {
        $stmt = $this->_dbExt->prepare($sql);
      }

      // Bind sql params
      if (is_array($params)) {
        foreach ($params as $key => $value) {
          $type = $this->_getType($value);
          $stmt->bindValue($key, $value, $type);
        }
      }
      // TESTING MODE - uncomment to allow SELECT only (blocks INSERT / UPDATE)
      //if (preg_match('/^SELECT/', $sql)) {
        //var_dump($stmt->queryString);
        $stmt->execute();
      //}

      return $stmt;
    }
    catch(Exception $e) {
      print '<p class="alert error">ERROR 2: ' . $e->getMessage() . '</p>';
    }
  }

  /**
   * Manually format fields/placeholders for MySQL SET clause
   *   from: https://phpdelusions.net/pdo
   *
   * @param $data {Array}
   *     key-value pairs for query
   *
   * @return $setClause {String}
   */
  private function _getSetClause ($data) {
    $setClause = 'SET ';

    foreach ($data as $field => $value) {
      $setClause .= '`' . str_replace('`', '``', $field) . '`' . "=:$field, ";
    }
    $setClause = substr($setClause, 0, -2); // strip final ','

    return $setClause;
  }

  /**
   * Take an array of SQL fields and return a comma-separated string with fields
   *   enclosed in backticks
   *
   * @param $array {Array}
   *
   * @return {String}
   */
  private function _getSqlFieldList ($array) {
    return '`' . implode('`, `', $array) . '`';
  }

  /**
   * Get data type for a sql parameter (PDO::PARAM_* constant)
   *
   * @param $var {?}
   *     variable to identify type of
   *
   * @return $type {Integer}
   */
  private function _getType ($var) {
    $pdoTypes = array(
      'boolean' => PDO::PARAM_BOOL,
      'integer' => PDO::PARAM_INT,
      'NULL' => PDO::PARAM_NULL,
      'string' => PDO::PARAM_STR
    );
    $varType = gettype($var);

    $type = $pdoTypes['string']; // default
    if (isset($pdoTypes[$varType])) {
      $type = $pdoTypes[$varType];
    }

    return $type;
  }

  /**
   * Add status entry to database
   *
   * @param $params {Array}
   *
   * @return {Function}
   */
  public function addStatusEntry ($params) {
    // need to set initial value for changed (MySQL schema updates it automatically)
    $params['changed'] = date('Y-m-d H:i:s');
    $params['ip'] = $this->_ip;

    $setClause = $this->_getSetClause($params);

    $sql = "INSERT INTO esc_statusEntries
      $setClause";

    return $this->_execQuery($sql, $params);
  }

  /**
   * Delete status entry (instead of perm. deleting, set deleted to 1 to hide it)
   *
   * @param $id {Integer}
   *
   * @return {Function}
   */
  public function deleteStatusEntry ($id) {
    $params['id'] = $id;
    $params['ip'] = $this->_ip;

    $sql = 'UPDATE esc_statusEntries
      SET `deleted` = 1, ip = :ip
      WHERE `id` = :id';

    return $this->_execQuery($sql, $params);
  }

  /**
   * Edit status entry in database
   *
   * @param $id {Integer}
   * @param $params {Array}
   *
   * @return {Function}
   */
  public function editStatusEntry ($id, $params) {
    $setClause = $this->_getSetClause($params);
    $params['id'] = $id; // should be set after $setClause so it's not added to it

    $sql = "UPDATE esc_statusEntries
      $setClause
      WHERE `id` = :id";

    return $this->_execQuery($sql, $params);
  }

  /**
   * Get employee(s) - either a specific employee or a complete list
   *
   * @param $shortname {String}
   *     Optional email shortname (text before '@') of employee to query
   *
   * @return {Function}
   */
  public function selectEmployees ($shortname=NULL) {
    $params = array();
    $whereClause = '';

    // Get specific employee
    if ($shortname) {
      $params['shortname'] = "$shortname@%";
      $whereClause .= 'WHERE `email` LIKE :shortname';
    }

    $sql = "SELECT * FROM esc_employees
      LEFT JOIN esc_locations USING (location)
      $whereClause
      ORDER BY `lastname` ASC, `firstname` ASC";

    return $this->_execQuery($sql, $params);
  }

  /**
   * Get status entries - for a specific employee or all employees
   *
   * @param $shortname {String}
   *     Optional email shortname (text before '@') of employee to query
   * @param $filter {String <past | current | future | recurring>}
   *     Optional - default is 'current' (past / future, etc. not included)
   *
   * @return {Function}
   */
  public function selectStatusEntries ($shortname=NULL, $filter='current') {
    $whereClause = 'WHERE `deleted` = 0'; // not deleted by user

    // Get status entries for a specific employee
    if ($shortname) {
      $params['shortname'] = $shortname;
      $whereClause .= ' AND `shortname` = :shortname';
    }

    if ($filter === 'recurring') {
      $orderbyClause = 'ORDER BY `shortname` ASC';
      $timePeriodFields = $this->_getSqlFieldList(
        array('monday', 'tuesday', 'wednesday', 'thursday', 'friday')
      );
      $whereClause .= ' AND `recurring` = 1';
    }
    else {
      $orderbyClause = 'ORDER BY `shortname` ASC, `begin` ASC';
      $params['today'] = date('Y-m-d');
      $timePeriodFields = $this->_getSqlFieldList(array('begin', 'end'));
      $whereClause .= ' AND `recurring` = 0';

      // Set up time period filter
      if ($filter === 'past') {
        $whereClause .= ' AND `end` < :today';
      }
      else if ($filter === 'current') {
        $whereClause .= ' AND `begin` <= :today AND (`end` >= :today OR `end` IS NULL)';
      }
      else if ($filter === 'future') {
        $whereClause .= ' AND `begin` > :today';
      }
    }

    $sql = "SELECT `shortname`, `id`, `status`, `contact`, `backup`, `comments`,
      $timePeriodFields
      FROM esc_statusEntries
      $whereClause
      $orderbyClause";

    return $this->_execQuery($sql, $params);
  }

  /**
   * Get specific status entry by id
   *
   * @param $id {Integer}
   * @param $recurring {Boolean}
   *
   * @return {Function}
   */
  public function selectStatusEntryById ($id, $recurring=false) {
    $params['id'] = $id;

    $sql = "SELECT `shortname`, `id`, `status`, `begin`, `end`, `contact`,
      `backup`, `comments` FROM esc_statusEntries
      WHERE `id` = :id";

    return $this->_execQuery($sql, $params);
  }
}
