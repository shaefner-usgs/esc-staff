<?php

include_once 'lib/Db.class.php'; // Database connector, queries
include_once 'lib/Employee.class.php'; // Employee model
include_once 'lib/Status.class.php'; // Status model
include_once 'lib/StatusView.class.php'; // View
include_once 'lib/StatusCollection.class.php'; // Status collection

include_once $_SERVER['DOCUMENT_ROOT'] . '/template/functions/functions.inc.php';

if (!isset($TEMPLATE)) {
  $Db = new Db;

  $action = safeParam('action', NULL); // add, delete, edit or NULL
  $id = safeParam('id', NULL);
  $shortname = safeParam('shortname');
  $view = safeParam('view', 'add'); // add or edit

  if ($action) { // user is adding, deleting, or editing an entry
    if ($action === 'add') {
      $Status = new Status($_POST);
      $Status->add($Db);
    }
    else if ($action === 'delete') {
      $Status = new Status(array('id' => $id));
      $Status->delete($Db);

      // Take user back to their status page
      header("Location: ../../");
    }
    else if ($action === 'edit') {
      $Status = new Status($_POST);
      $Status->edit($Db);
    }
  }

  // Query database to get employee's details and create an Employee instance
  $rsEmployee = $Db->selectEmployees($shortname);

  $rsEmployee->setFetchMode(PDO::FETCH_CLASS, 'Employee');
  $Employee = $rsEmployee->fetch();

  $TITLE = '<em>Set status for</em> ' . $Employee->fullname;
  $CSS = '/contact/staff/css/status.css';
  $JS = '/contact/staff/js/Status.js';
  $WIDGETS = 'jquery-ui';
  $TEMPLATE = 'sidenav';

  include_once ($_SERVER['DOCUMENT_ROOT'] . "/template/core/template.inc.php");
}

if (!$Employee) {
  print "<h1>Employee ($shortname) Does Not Exist</h1>";
  return;
}

$statusEntries = array();

if ($view === 'add') { // default view: show add form + list of status entries
  // Query database to get employee's status entries
  $rsStatusEntriesPresent = $Db->selectStatusEntries($shortname);
  $rsStatusEntriesFuture = $Db->selectStatusEntries($shortname, 'future');
  $rsStatusEntriesPast = $Db->selectStatusEntries($shortname, 'past');
  $rsStatusEntriesRec = $Db->selectStatusEntries($shortname, 'recurring');

  // Create a Status instance for each entry
  $statusEntries = array(
    'future' => $rsStatusEntriesFuture->fetchall(PDO::FETCH_CLASS, 'Status',
      array(array('type' => 'future'))
    ),
    'past' => $rsStatusEntriesPast->fetchall(PDO::FETCH_CLASS, 'Status',
      array(array('type' => 'past'))
    ),
    'present' => $rsStatusEntriesPresent->fetchall(PDO::FETCH_CLASS, 'Status',
      array(array('type' => 'present'))
    ),
    'recurring' => $rsStatusEntriesRec->fetchall(PDO::FETCH_CLASS, 'Status',
      array(array('type' => 'recurring'))
    )
  );
  // Combine all 'current' (present and recurring) entries in 1 place
  $statusEntries['current'] = array_merge(
    $statusEntries['present'],
    $statusEntries['recurring']
  );
}
else if ($view === 'edit') { // edit entry view: show edit form containing entry
  // Create a Status instance for entry user is editing
  $rsStatusEntry = $Db->selectStatusEntryById($id);
  $rsStatusEntry->setFetchMode(PDO::FETCH_CLASS, 'Status');
  $Status = $rsStatusEntry->fetch();

  $statusEntries['edit'] = array($Status); // use array to match types w/ multiple entries
}

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
$View = new StatusView($Employee);
$View->render();
