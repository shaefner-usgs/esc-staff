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
$rsStatusEntriesPresent = $Db->selectStatusEntries();
$rsStatusEntriesRec = $Db->selectStatusEntries(NULL, 'recurring');

// Create an Employee instance for each employee
$employees = $rsEmployees->fetchAll(PDO::FETCH_CLASS, 'Employee');

// Index status entries by employee shortname (1st column in query)
$statusEntries = array(
  'present' => $rsStatusEntriesPresent->fetchAll(PDO::FETCH_GROUP),
  'recurring' => $rsStatusEntriesRec->fetchAll(PDO::FETCH_GROUP)
);

// Create a Collection of Employees (including thier 'current' status entries)
$EmployeeCollection = new EmployeeCollection;
foreach ($employees as $Employee) {

  // Group status entries into Collections by type
  $StatusCollection = new stdClass(); // initialize empty object
  foreach ($statusEntries as $type => $entries) {
    if (count($entries) > 0) {
      $StatusCollection->$type = new StatusCollection;

      foreach ($entries as $Entry) {
        $StatusCollection->$type->add($Entry);
      }
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
