<?php

include_once 'lib/Db.class.php'; // Database connector, queries
include_once 'lib/Employee.class.php'; // Employee model
include_once 'lib/Status.class.php'; // Status model
include_once 'lib/StatusView.class.php'; // View

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

  // Query database to get employee's details
  $rsEmployee = $Db->selectEmployees($shortname);

  $rsEmployee->setFetchMode(PDO::FETCH_CLASS, 'Employee');
  $Employee = $rsEmployee->fetch();

  $TITLE = '<em>Set status for</em> ' . $Employee->fullname;
  $CSS = '/contact/staff/css/status.css';
  $JS = '/contact/staff/js/status.js';
  $WIDGETS = 'jquery-ui';
  $TEMPLATE = 'sidenav';

  include_once ($_SERVER['DOCUMENT_ROOT'] . "/template/core/template.inc.php");
}

$statusEntries = array();

// Query database to get employee's status entries and populate array for View
if ($view === 'add') { // default view - create new entry + list of status entries
  $rsStatusEntries = $Db->selectStatusEntries($shortname);
  $rsStatusEntriesFuture = $Db->selectStatusEntries($shortname, 'future');
  $rsStatusEntriesPast = $Db->selectStatusEntries($shortname, 'past');

  $statusEntries['current'] = $rsStatusEntries->fetchall(PDO::FETCH_CLASS, 'Status');
  $statusEntries['future'] = $rsStatusEntriesFuture->fetchall(PDO::FETCH_CLASS, 'Status');
  $statusEntries['past'] = $rsStatusEntriesPast->fetchall(PDO::FETCH_CLASS, 'Status');
}
else if ($view === 'edit') { // edit entry view
  $rsStatusEntry = $Db->selectStatusEntryById($id);
  $rsStatusEntry->setFetchMode(PDO::FETCH_CLASS, 'Status');
  $Status = $rsStatusEntry->fetch();

  $statusEntries['edit'] = $Status;
}

// Create and render view
$View = new StatusView($Employee, $statusEntries);
$View->render();
