<?php

/**
 * Employee view - creates the HTML for employee.php
 *
 * @param $employee {Employee instance}
 * @param $statusEntries {Array}
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class EmployeeView {
  private $_employee, $_statusEntries;

  public function __construct (Employee $employee, $statusEntries) {
    $this->_employee = $employee;
    $this->_statusEntries = $statusEntries;
  }

  /**
   * Create HTML for mailing address
   *
   * @return $html {String}
   */
  private function _getAddress () {
    $Employee = $this->_employee;

    $html = sprintf('<div class="vcard">
        <div class="adr">
          <div class="fn org">%s</div>
          <div class="street-address">%s %s</div>
          <span class="locality">%s</span>,
          <span class="region">%s</span>
          <span class="postal-code">%s</span>
        </div>
      </div>',
      $Employee->institution,
      $Employee->address1,
      $Employee->address2,
      $Employee->city,
      $Employee->state,
      $Employee->zipcode
    );

    return $html;
  }

  /**
   * Create HTML for employee details table
   *
   * @return $html {String}
   */
  private function _getEmployeeDetails () {
    $Employee = $this->_employee;

    $html = '<table>';
    if ($Employee->phone) {
      $html .= '<tr><th>Office</th><td>' . $Employee->phone . '</td></tr>';
    }
    if ($Employee->mobile) {
      $html .= '<tr><th>Mobile</th><td>' . $Employee->mobile . '</td></tr>';
    }
    $html .= sprintf('<tr><th>Email</th><td><a href="mailto:%s">%s</a></td></tr>',
      $Employee->email,
      $Employee->email
    );
    if ($Employee->room) {
      $html .= '<tr><th>Room</th><td>' . $Employee->room . '</td></tr>';
    }
    // As of 2015-06-02, Trudy is everyone's timekeeper
    $html .= '<tr><th>Timekeeper</th><td>Trudy Cervantes</td></tr>';
    $html .= '<tr><th>Address</th><td>' . $this->_getAddress() . '</td></tr>';
    if ($Employee->dutystation) {
      $html .= '<tr><th>Duty Station</th><td>' . autop($Employee->dutystation) . '</td></tr>';
    }
    if ($Employee->webpage) {
      $html .= sprintf('<tr><th>Web</th><td><a href="%s">%s</a></td></tr>',
        $Employee->webpage,
        $Employee->webpage
      );
    }
    if ($Employee->orcid) {
      $html .= sprintf('<tr>
          <th>ORCid</th>
          <td><a href="https://orcid.org/%s">%s</a></td>
        </tr>',
        $Employee->orcid,
        $Employee->orcid
      );
    }
    if ($Employee->comment) {
      $html .= '<tr><th>Comments</th><td>' . $Employee->comment . '</td></tr>';
    }
    $html .= '</table>';

    return $html;
  }

  /**
   * Create HTML for status entries
   *
   * @return $html {String}
   */
  private function _getStatusEntries () {
    $statusEntries = $this->_statusEntries;

    $html = '';
    if ($statusEntries['current']) {
      foreach ($statusEntries['current'] as $Status) {
        $html .= $Status->getHtml('current');
      }
    } else {
      // Create default status if no current status is set
      $Status = new Status(array(
        'status' => 'in the office',
        'timespan' => 'Today'
      ));
      $html .= $Status->getHtml('current');
    }

    if ($statusEntries['future']) {
      $html .= '<h2>Future Plans</h2>';
      foreach ($statusEntries['future'] as $Status) {
        $html .= $Status->getHtml('future');
      }
    }

    return $html;
  }

  private function _getSetStatusLink () {
    return sprintf('<p><a href="/contact/staff/%s/status/">Set status</a> &raquo;</p>',
      $this->_employee->shortname
    );
  }

  /**
   * Create HTML for page title
   *
   * @return {String}
   */
  private function _getTitle () {
    return sprintf('<h1>%s <em>(%s)</em></h1>',
      $this->_employee->fullname,
      $this->_employee->classification
    );
  }

  /**
   * Render HTML
   */
  public function render () {
    print $this->_getTitle();
    print $this->_getEmployeeDetails();
    print $this->_getStatusEntries();
    print $this->_getSetStatusLink();
  }
}
