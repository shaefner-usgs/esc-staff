<?php

include_once 'lib/Db.class.php'; // Database connector, queries
include_once 'lib/Employee.class.php'; // Employee model
include_once 'lib/EmployeeCollection.class.php'; // Collection
include_once 'lib/IndexView.class.php'; // View
include_once 'lib/Status.class.php'; // Status model

if (!isset($TEMPLATE)) {
  $TITLE = 'Staff Directory';
  $CSS = '/contact/staff/css/index.css';
  $WIDGETS = 'responsive-tables';
  $TEMPLATE = 'sidenav';

  include_once ($_SERVER['DOCUMENT_ROOT'] . "/template/core/template.inc.php");
}

$sortBy = safeParam('sortby', 'name');

// Query database to get all employees and their associated status entries
$Db = new Db;
$rsEmployees = $Db->selectEmployees();
$rsStatusEntries = $Db->selectStatusEntries();

// Index status entries by employee shortname (1st column in query)
$statusEntries = $rsStatusEntries->fetchAll(PDO::FETCH_GROUP);

// Create an employee object for each employee (including their current status)
$employees = $rsEmployees->fetchAll(PDO::FETCH_CLASS, 'Employee', array($statusEntries));

// Create a collection of employees and sort it (if necessary)
$EmployeeCollection = new EmployeeCollection;
foreach ($employees as $Employee) {
  $EmployeeCollection->add($Employee);
}
if ($sortBy !== 'name') { // Sorted by (last) name by default via MySQL query
  $EmployeeCollection->sort($sortBy);
}

// Create and render view
$View = new IndexView($EmployeeCollection);
$View->render();
