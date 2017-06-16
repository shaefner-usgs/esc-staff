<?php

include_once 'lib/Db.class.php'; // Database connector, queries
include_once 'lib/Employee.class.php'; // Employee model
include_once 'lib/EmployeeView.class.php'; // View
include_once 'lib/Status.class.php'; // Status model

include_once $_SERVER['DOCUMENT_ROOT'] . '/template/functions/functions.inc.php';

if (!isset($TEMPLATE)) {
  $Db = new Db;

  $shortname = safeParam('shortname');

  // Query database to get employee's details and status entries
  $rsEmployee = $Db->selectEmployees($shortname);
  $rsStatusEntries = $Db->selectStatusEntries($shortname);
  $rsStatusEntriesFuture = $Db->selectStatusEntries($shortname, 'future');

  // Use db results to set up params needed by the View
  $statusEntries = array(
    'current' => $rsStatusEntries->fetchall(PDO::FETCH_CLASS, 'Status'),
    'future' => $rsStatusEntriesFuture->fetchall(PDO::FETCH_CLASS, 'Status')
  );

  $rsEmployee->setFetchMode(PDO::FETCH_CLASS, 'Employee',
    array($statusEntries['current'])
  );
  $Employee = $rsEmployee->fetch();

  $TITLETAG = $Employee->fullname;
  $CSS = '/contact/staff/css/employee.css';
  $TEMPLATE = 'sidenav';

  include_once ($_SERVER['DOCUMENT_ROOT'] . "/template/core/template.inc.php");
}

// Create and render view
$View = new EmployeeView($Employee, $statusEntries);
$View->render();
