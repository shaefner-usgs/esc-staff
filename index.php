<?php

include_once 'lib/Db.class.php'; // Database connector, queries
include_once 'lib/Employee.class.php'; // Employee model
include_once 'lib/EmployeeCollection.class.php'; // Employee collection
include_once 'lib/IndexView.class.php'; // View
include_once 'lib/Status.class.php'; // Status model
include_once 'lib/StatusCollection.class.php'; // Status collection

if (!isset($TEMPLATE)) {
  $TITLE = 'Staff Directory';
  $CSS = '/contact/staff/css/index.css';
  $WIDGETS = 'responsive-tables';
  $TEMPLATE = 'sidenav';

  include_once ($_SERVER['DOCUMENT_ROOT'] . "/template/core/template.inc.php");
}

$sortBy = safeParam('sortby', 'name');

// Query database to get all employees / associated 'current' status entries
$Db = new Db;
$rsEmployees = $Db->selectEmployees();
$rsStatusEntriesCurrent = $Db->selectStatusEntries();

// Create an Employee instance for each employee
$employees = $rsEmployees->fetchAll(PDO::FETCH_CLASS, 'Employee');

// Index status entries by employee shortname (1st column in query)
$statusEntries = array(
  'current' => $rsStatusEntriesCurrent->fetchAll(PDO::FETCH_GROUP)
);

// Create a Collection of Employees (including thier 'current' status entries)
$EmployeeCollection = new EmployeeCollection;
foreach ($employees as $Employee) {
  $StatusCollection = new stdClass(); // initialize empty object
  // Create a Collection of 'current' Status instances for Employee
  if (array_key_exists($Employee->shortname, $statusEntries['current'])) {
    $StatusCollection->current = new StatusCollection;
    foreach ($statusEntries['current'][$Employee->shortname] as $entry) {
      $Status = new Status($entry);
      $StatusCollection->current->add($Status);
    }
  }
  $Employee->status = $StatusCollection;

  $EmployeeCollection->add($Employee);
}

// Sort employee Collection (if necessary)
if ($sortBy !== 'name') { // Sorted by (last) name by default via MySQL query
  $EmployeeCollection->sort($sortBy);
}

// Create and render View
$View = new IndexView($EmployeeCollection);
$View->render();
