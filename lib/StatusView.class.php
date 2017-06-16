<?php

/**
 * Status view - creates the HTML for status.php
 *
 * @param $employee {Employee instance}
 * @param $statusEntries {Array}
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */

class StatusView {
  private $_employee, $_statusEntries, $_view;

  public function __construct (Employee $employee, $statusEntries) {
    $this->_employee = $employee;
    $this->_statusEntries = $statusEntries;

    // determine view mode (add or edit)
    $this->_view = 'add';
    if (array_key_exists('edit', $this->_statusEntries)) {
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
   * Create HTML for delete modal (added to bottom of page in Foundation framework)
   *   Links and details added by JavaScript
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
      $Status = $this->_statusEntries['edit'];
    } else {
      $Status = new Status(array('id' => NULL));
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
        <label for="status">Status</label>
      	<select id="status" name="status" required>
      		<option value="">Choose&hellip;</option>
          %s
        </select>
        <label for="begin">
          Date range <em>Enter a date value: mm/dd/yyyy, today, next wednesday, mar 1, etc.</em>
        </label>
        <input type="text" name="begin" id="begin" size="12" value="%s" required />
          &ndash;
        <input type="text" name="end" id="end" size="12" value="%s" />
        <span id="forever">
          <input name="indefinite" id="indefinite" type="checkbox" value="true"%s />
          <label for="indefinite">Indefinite</label>
        </span>
      	<div id="option-contact">
      		<label for="contact">Location & contact info <em>Include city, hotel, etc.</em></label>
      		<textarea name="contact" id="contact" rows="2" cols="40">%s</textarea>
      	</div>
      	<div id="option-backup">
      		<label for="backup">Backup person</label>
      		<input name="backup" id="backup" type="text" value="%s" size="40" maxlength="64" />
      	</div>
      	<label for="comments">Comments</label>
      	<textarea name="comments" id="comments" rows="3" cols="40">%s</textarea>
      	<input name="action" id="action" type="hidden" value="%s" />
      	<input name="id" id="id" type="hidden" value="%s" />
      	<input name="shortname" id="shortname" type="hidden" value="%s" />
      	<button name="submit" id="submit" type="submit" class="radius button success">Set Status</button>
      </form>',
      $this->_employee->shortname,
      $optionTags,
      $Status->formatDate($Status->begin),
      $Status->formatDate($Status->end),
      $indefiniteChecked,
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
   *   shows message that system is under maintenance w/ auto-generated timestamp
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
   * Create HTML for status entries
   *
   * @return $html {String}
   */
  private function _getStatusEntries () {
    $statusEntries = $this->_statusEntries;

    $html = '<h2>Currently</h2>';
    if ($statusEntries['current']) {
      foreach ($statusEntries['current'] as $Status) {
        $html .= $Status->getHtml('current', 'actionButtons');
      }
    } else {
      // Create default status if no current status is set
      $Status = new Status(array(
        'status' => 'in the office',
        'timespan' => '(Default setting)'
      ));
      $html .= $Status->getHtml('current');
    }

    if ($statusEntries['future']) {
      $html .= '<h2>Future Plans</h2>';
      foreach ($statusEntries['future'] as $Status) {
        $html .= $Status->getHtml('future', 'actionButtons');
      }
    }

    if ($statusEntries['past']) {
      $html .= '<h2>Past</h2>';
      foreach ($statusEntries['past'] as $Status) {
        $html .= $Status->getHtml('past', 'actionButtons');
      }
    }

    return $html;
  }

  /**
   * Create HTML for page subtitle
   *
   * @return $h2 {String}
   */
  private function _getSubTitle () {
    if ($this->_view === 'add') {
      $h2 = '<h2>Create New Entry</h2>';
    } else {
      $h2 = '<h2>Edit Entry</h2>';
    }

    return $h2;
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