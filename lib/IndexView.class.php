<?php

/**
 * Employee list view - creates the HTML for index.php\
 *
 * @param $collection {Object}
 *
 * @author Scott Haefner <shaefner@usgs.gov>
 */
class IndexView {
  private $_collection, $_sets;

  public function __construct (EmployeeCollection $collection) {
    $this->_collection = $collection;
    $this->_sets = array();
  }

  /**
   * Create HTML for callout box
   *
   * @return $html {String}
   */
  private function _getCallout () {
    $html = '<div class="panel radius">
    	<h3>See Also</h3>
    	<ul class="disc">
      	<li><a href="https://www2.usgs.gov/phonebook/employee/">USGS Employee Phonebook</a></li>
    		<li><a href="https://www.usgs.gov/connect/staff-profiles">USGS Staff Profiles</a>
    		  (<a href="https://sites.google.com/a/usgs.gov/web-reengineering/staff-profiles/staff-profile-access/about-staff-profiles">Request
    		    access to edit your page</a>)
    		</li>
    		<li><a href="https://www.usgs.gov/connect/locations">USGS Offices and Science Centers</a></li>
    		<li><a href="https://www.usgs.gov/about/key-officials">USGS Directory of Key Officials</a></li>
    	</ul>
    </div>';

    return $html;
  }

  /**
   * Create HTML for employee list
   *
   * @return $html {String}
   */
  private function _getEmployeeList () {
    $html = '<table class="responsive">';
    $setPrev = '';
    $sortBy = $this->_collection->sortBy;

    foreach ($this->_collection->employees as $Employee) {
      if ($sortBy === 'name') {
        $set = $Employee->firstletter;
      } else if ($sortBy === 'location') {
        $set = $Employee->location;
      } else if ($sortBy === 'status') {
        $set = ucwords($Employee->status->status);
      }

      if ($setPrev !== $set) {
        $html .= sprintf('<tr id="%s" class="header"><th colspan="4">%s</th></tr>',
          $set,
          $set
        );
        $this->_sets[] = $set;
      }
      $setPrev = $set;

      $openTag = '';
      $closeTag = '';
      if ($Employee->status->status !== 'in the office') {
        $openTag = '<strong class="has-tip tip-right" title="' . $Employee->status->getTimeSpan() . '">';
        $closeTag = '</strong>';
      }

      $html .= sprintf('<tr class="hover">
          <td><a href="/contact/staff/%s/">%s</a></td>
          <td>%s%s%s</td>
          <td>%s</td>
          <td>%s</td>
        </tr>',
        $Employee->shortname,
        $Employee->lastfirst,
        $openTag,
        $Employee->status->status,
        $closeTag,
        $Employee->email,
        $Employee->phone
      );
    }

    $html .= '</table>';

    return $html;
  }

  /**
   * Create HTML for jump list
   *
   * @return $html {String}
   */
  private function _getJumpList () {
    $html = '<dl class="sub-nav">
      <dt>Go to:</dt>';

    foreach($this->_sets as $set) {
      $html .= sprintf('<dd><a href="#%s" title="Scroll page to &lsquo;%s&rsquo;">%s</a></dd>',
        $set,
        $set,
        $set
      );
    }

    $html .= '</dl>';

    return $html;
  }

  /**
   * Create HTML for sort module
   *
   * @return $html {String}
   */
  private function _getSortModule () {
    $selected = array_fill_keys(array('name', 'location', 'status'), '');
    $selected[$this->_collection->sortBy] = 'active';

    $html = sprintf('<dl class="sub-nav">
        <dt>Sort by:</dt>
        <dd class="%s"><a href="/contact/staff/name/">Last Name</a></dd>
        <dd class="%s"><a href="/contact/staff/location/">Location</a></dd>
        <dd class="%s"><a href="/contact/staff/status/">Status</a></dd>
      </dl>',
      $selected['name'],
      $selected['location'],
      $selected['status']
    );

    return $html;
  }

  /**
   * Render HTML
   */
  public function render () {
    $employeeList = $this->_getEmployeeList(); // populates $this->_sets[] used by _getJumpList

    print $this->_getSortModule();
    print $this->_getJumpList();
    print $this->_getCallout();
    print $employeeList;
  }
}