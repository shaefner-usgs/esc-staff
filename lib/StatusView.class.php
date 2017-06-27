<?php

/**
 * Status view - creates the HTML for status.php
 *
 * @param $employee {Object: Employee instance}
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class StatusView {
  private $_employee, $_view;

  public function __construct (Employee $employee) {
    $this->_employee = $employee;

    // determine view mode (add or edit)
    $this->_view = 'add';
    if (property_exists($this->_employee->status, 'edit')) {
      $this->_view = 'edit';
    }
  }

  /**
   * Get back link
   *
   * @return {String}
   */
  private function _getBackLink () {
    return '<p>&laquo; <a href="../">Back</a></p>';
  }

  /**
   * Get html snippet for marking a checkbox as checked
   *
   * @param $name {String}
   *   name value of form field
   *
   * @return $checked {String}
   */
  private function _getCheckedSnippet ($name) {
    $checked = '';

    // Only need to get checked state when in edit mode
    if ($this->_view === 'edit') {
      $Status = $this->_employee->status->edit->entries[0];
      if ($Status->$name === '1') {
        $checked = ' checked="checked"';
      }
    }

    return $checked;
  }

  /**
   * Create (generic) HTML for delete modal (links and details added via JavaScript)
   *   (added to bottom of page per Foundation framework)
   *
   * return html {String}
   */
  private function _getDeleteModal () {
    $html = '<div id="deleteModal" class="reveal-modal large">
        <h2>Delete Entry?</h2>
        <p></p>
        <ul class="actions">
          <li><a id="cancel" class="radius button secondary">Cancel</a></li>
          <li><a id="delete" class="radius button alert">Delete</a></li>
        </ul>
      </div>';

    return $html;
  }

  /**
   * Create HTML for status form
   *
   * @return $html {String}
   */
  private function _getForm () {
    $days = array(
      'monday', 'tuesday', 'wednesday', 'thursday', 'friday'
    );
    $statuses = array(
      'administrative leave',
      'alternative work schedule',
      'annual leave',
      'fieldwork',
      'intermittent schedule',
      'jury duty',
      'local meeting / travel',
      'official travel',
      'sick leave',
      'working at home'
    );

    // If editing an entry, use its Status instance; otherwise create a new one
    if ($this->_view === 'edit') {
      $Status = $this->_employee->status->edit->entries[0]; // there's only ever 1 edit entry
    } else {
      $Status = new Status(array('id' => NULL));
    }

    $dayCheckBoxes = '';
    foreach ($days as $day) {
      $dayCheckBoxes .= sprintf('<input name="%s" id="%s" type="checkbox" value="1"%s>
        <label for="%s">%s</label>',
        $day,
        $day,
        $this->_getCheckedSnippet($day),
        $day,
        ucwords($day)
      );
    }

    $indefiniteChecked = '';
    if ($Status->begin && !$Status->end) {
      $indefiniteChecked = ' checked="checked"';
    }

    // Build html for option tags in status select menu
    $optionTags = '';
    foreach ($statuses as $status) {
      $optionTags .= $this->_getOptionTag($status, $Status->status);
    }

    $html = sprintf('<form action="/contact/staff/%s/status/" name="form1" id="form1"
      method="post" enctype="application/x-www-form-urlencoded">
        <label for="status" class="fieldtitle">Status</label>
        <select id="status" name="status" required>
          <option value="">Choose&hellip;</option>
          %s
        </select>
        <div id="select-type" class="checkbox">
          <input name="recurring" id="recurring" type="checkbox" value="1"%s >
          <label for="recurring">Recurring</label>
        </div>
        <div id="option-dates">
          <label for="begin" class="fieldtitle">
            Date range <em>Enter a date value: mm/dd/yyyy, today, next wednesday, mar 1, etc.</em>
          </label>
          <input type="text" name="begin" id="begin" size="12" value="%s" />
            &ndash;
          <input type="text" name="end" id="end" size="12" value="%s" />
          <span id="forever" class="checkbox">
            <input name="indefinite" id="indefinite" type="checkbox" value="1"%s />
            <label for="indefinite">Indefinite</label>
          </span>
        </div>
        <div id="option-days" class="checkbox">
          <p class="fieldtitle">Repeat every:</p>
          %s
        </div>
        <div id="option-contact">
          <label for="contact" class="fieldtitle">Location & contact info <em>Include city, hotel, etc.</em></label>
          <textarea name="contact" id="contact" rows="2" cols="40">%s</textarea>
        </div>
        <div id="option-backup" class="fieldtitle">
          <label for="backup">Backup person</label>
          <input name="backup" id="backup" type="text" value="%s" size="40" maxlength="64" />
        </div>
        <label for="comments" class="fieldtitle">Comments</label>
        <textarea name="comments" id="comments" rows="3" cols="40">%s</textarea>
        <input name="action" id="action" type="hidden" value="%s" />
        <input name="id" id="id" type="hidden" value="%s" />
        <input name="shortname" id="shortname" type="hidden" value="%s" />
        <button name="submit" id="submit" type="submit" class="radius button success">Set Status</button>
      </form>',
      $this->_employee->shortname,
      $optionTags,
      $this->_getCheckedSnippet('recurring'),
      $Status->formatDate($Status->begin),
      $Status->formatDate($Status->end),
      $indefiniteChecked,
      $dayCheckBoxes,
      $Status->contact,
      $Status->backup,
      $Status->comments,
      $this->_view,
      $Status->id,
      $this->_employee->shortname
    );

    return $html;
  }

  /**
   * Create HTML for maintenance message
   *   shows a message that system is under maintenance w/ auto-generated timestamp
   *
   * @return $html {String}
   */
  private function _getMaintenanceMsg () {
    $timestamp = filemtime('lib/StatusView.class.php');
    $datetime = date('D, M j h:i A', $timestamp);
    $html = '<div class="alert-box alert">
        Currently under maintenance <em>' . $datetime. '</em>
      </div>
      <p>Please check back in 15-30 minutes. </p>';

    return $html;
  }

  /**
   * Create HTML for option tag in form
   *
   * @param $status {String}
   * @param $selectedStatus {String}
   *
   * @return $html {String}
   */
  private function _getOptionTag ($status, $selectedStatus) {
    $selected = '';
    if ($status === $selectedStatus) {
      $selected .= ' selected="selected"';
    }

    $html = sprintf('<option%s value="%s">%s</option>',
      $selected,
      $status,
      $status
    );

    return $html;
  }

  /**
   * Create HTML for list of status entries
   *
   * @return $html {String}
   */
  private function _getStatusEntries () {
    $statusEntries = $this->_employee->status;

    // Combine present and recurring entries
    if (!property_exists($statusEntries, 'present')) {
      $statusEntries->present = new stdClass();
      $statusEntries->present->entries = array();
    }
    if (!property_exists($statusEntries, 'recurring')) {
      $statusEntries->recurring = new stdClass();
      $statusEntries->recurring->entries = array();
    }
    $currentEntries = array_merge(
      $statusEntries->present->entries,
      $statusEntries->recurring->entries
    );

    // Current (present and recurring)
    $activeEntry = false;
    $StatusNow = $this->_employee->getStatusNow('(Default setting)');
    $html = '';
    foreach ($currentEntries as $Entry) {
      if ($Entry->isActive()) {
        $activeEntry = true;
      }
      if (count($currentEntries) > 1 && $Entry === $StatusNow) {
        // Indicate 'selected' status to user
        $html .= $Entry->getHtml('showButtons', 'selected');
      } else {
        $html .= $Entry->getHtml('showButtons');
      }
    }
    if (!$activeEntry) { // create default status if no current status is set
      $Status = new Status(array(
        'status' => 'in the office',
        'timespan' => '(Default setting)',
        'type' => 'present'
      ));
      $html = $Status->getHtml() . $html;
    }
    $html = '<h2>Currently</h2>' . $html;

    // Future
    if (property_exists($statusEntries, 'future')) {
      $html .= '<h2>Future Plans</h2>';
      foreach ($statusEntries->future->entries as $Entry) {
        $html .= $Entry->getHtml('showButtons');
      }
    }

    // Past
    if (property_exists($statusEntries, 'past')) {
      $statusEntries->past->sort('DESC');
      $html .= '<h2>Past</h2>';
      foreach ($statusEntries->past->entries as $Entry) {
        $html .= $Entry->getHtml('showButtons');
      }
    }

    return $html;
  }

  /**
   * Create HTML for page subtitle
   *
   * @return $html {String}
   */
  private function _getSubTitle () {
    if ($this->_view === 'add') {
      $html = '<h2>Create New Entry</h2>';
    } else {
      $html = '<h2>Edit Entry</h2>';
    }

    return $html;
  }

  public function render () {
    //print $this->_getMaintenanceMsg();
    print $this->_getSubTitle();
    print $this->_getForm();
    print $this->_getBackLink();
    if ($this->_view === 'add') { // show status entries for editing below form
      print $this->_getStatusEntries();
      print $this->_getDeleteModal();
    }
  }
}
