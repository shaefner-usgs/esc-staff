<?php

include_once 'lib/Db.class.php'; // Database connector, queries
include_once 'lib/Employee.class.php'; // Employee model
include_once 'lib/EmployeeView.class.php'; // View
include_once 'lib/Status.class.php'; // Status model
include_once 'lib/StatusCollection.class.php'; // Status collection

include_once $_SERVER['DOCUMENT_ROOT'] . '/template/functions/functions.inc.php';

if (!isset($TEMPLATE)) {
  $Db = new Db;

  $shortname = safeParam('shortname');

  // Query database to get employee's details and create an Employee instance
  $rsEmployee = $Db->selectEmployees($shortname);
  $rsEmployee->setFetchMode(PDO::FETCH_CLASS, 'Employee');
  $Employee = $rsEmployee->fetch();

  $TITLETAG = $Employee->fullname;
  $CSS = '/contact/staff/css/employee.css';
  $TEMPLATE = 'sidenav';

  include_once ($_SERVER['DOCUMENT_ROOT'] . "/template/core/template.inc.php");
}

if (!$Employee) {
  print "<h1>Employee ($shortname) Does Not Exist</h1>";
  return;
}

// Query database to get employee's status entries
$rsStatusEntriesFuture = $Db->selectStatusEntries($shortname, 'future');
$rsStatusEntriesPresent = $Db->selectStatusEntries($shortname);
$rsStatusEntriesRec = $Db->selectStatusEntries($shortname, 'recurring');

// Create a Status instance for each entry
$statusEntries = array(
  'future' => $rsStatusEntriesFuture->fetchall(PDO::FETCH_CLASS, 'Status',
    array(array('type' => 'future'))
  ),
  'present' => $rsStatusEntriesPresent->fetchall(PDO::FETCH_CLASS, 'Status',
    array(array('type' => 'present'))
  ),
  'recurring' => $rsStatusEntriesRec->fetchall(PDO::FETCH_CLASS, 'Status',
    array(array('type' => 'recurring'))
  )
);

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

// Create and render View
$View = new EmployeeView($Employee);
$View->render();
