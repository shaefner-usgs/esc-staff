'use strict';

/**
 * Class: Status
 */
var Status = function () {

  var _this,
      _initialize,

      _errors,

      _addEvents,
      _hideModal,
      _initDatePicker,
      _showError,
      _showModal,
      _submitForm,
      _swapDateFields,
      _swapFields,
      _toggleEndDate;


  _initialize = function () {
    _addEvents();
    _initDatePicker();

    // Initialize form based on status entry settings (in edit mode; no effect in add mode)
    _swapDateFields();
    _swapFields();
    _toggleEndDate();
  };

  /**
   * Set up event handlers
   */
  _addEvents = function () {
    // Disable ending date field when user checks indefinite checkbox
    $('#indefinite').on('change', _toggleEndDate);

    // Show/hide form elements based on user's selections
    $('#recurring').on('change', _swapDateFields);
    $('#status').on('change', _swapFields);

    // Show/hide delete modal
    $('.delete').on('click', _showModal);
    $('#cancel').on('click', _hideModal);

    // Check for required fields, then submit form
    $('#submit').on('click', _submitForm);
  }

  /**
   * Hide delete modal (confirmation dialog)
   */
  _hideModal = function() {
    $('#deleteModal').trigger('reveal:close');
  }

  /**
   * Set up jQuery date picker
   */
  _initDatePicker = function () {
    $.datepicker.setDefaults({
      showAnim: false,
      constrainInput: false,
      hideIfNoPrevNext: true,
      maxDate: '+2y',
      minDate: '0',
      numberOfMonths: 2,
      showAnim: 'fadeIn',
      stepMonths: 1
    });

    $('#begin').datepicker({
      onSelect: function(selDate) {
        $('#end').datepicker('option', 'minDate', selDate);
        // Set 'end' date to default to 'begin' if empty
        if (!$('#end').val() && !$('#indefinite').is(':checked')) {
          $('#end').datepicker('setDate', selDate);
        }
      }
    });

    $('#end').datepicker({
      onSelect: function(selDate) {
        $('#begin').datepicker('option', 'maxDate', selDate);
        // Set 'begin' date to default to 'end' if empty
        if (!$('#begin').val()) {
          $('#begin').datepicker('setDate', selDate);
        }
      }
    });
  }

  /**
   * Display validation errors inline
   *
   * @param el {Element}
   * @param msg {String}
   */
  _showError = function (el, msg) {
    var selector;

    // Flag to stop form submission
    _errors = true;

    if (el === '#begin') {
      selector = '#forever';
    } else if (el === '#option-days') {
      selector = '#option-days label:last-child';
    } else {
      selector = el;
    }

    $(selector).after('<p class="error">' + msg + '</p>');
    $(el).addClass('error');
  }

  /**
   * Show delete modal (confirmation dialog) and populate w/ status entry details
   *
   * @param e {Event}
   */
  _showModal = function (e) {
    var msg = $(this).parents('.alert-box').find('p').html();

    $('#deleteModal p').html(msg);
    $('#delete').attr('href', $(this).attr('href'));
    $('#deleteModal').reveal({animationSpeed: 150});

    e.preventDefault();
  }

  /**
   * Validate and then submit form
   *
   * @param e {Event}
   */
  _submitForm = function (e) {
    var checked,
        days;

    // First, reset any previous errors
    _errors = false;
    $('input, select').removeClass('error');
    $('p.error').remove();

    // Begin date
    if (!$('#begin').val() && !$('#recurring').is(':checked')) {
      _showError('#begin', 'Enter a beginning date');
    }

    // Recurring
    if ($('#recurring').is(':checked')) {
      checked = false;

      days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
      days.forEach(function (day) {
        if ($('#' + day).is(':checked')) {
          checked = true;
        }
      });

      if (!checked) {
        _showError('#option-days', 'Select at least 1 day');
      }
    }

    // Status
    if (!$('#status option:selected').val()) {
      _showError('#status', 'Select a status');
    }

    if (_errors) {
      e.preventDefault();
    }
  }

  /**
   * Show/hide dates/days form elements based on 'recurring' selection
   */
  _swapDateFields = function () {
    if ($('#recurring').is(':checked')) {
      $('#option-dates').css('display', 'none');
      $('#option-days').css('display', 'block');
    }
    else {
      $('#option-dates').css('display', 'block');
      $('#option-days').css('display', 'none');
    }
  }

  /**
   * Show/hide fields based on 'status' selected by user
   */
  _swapFields = function () {
    var selected = $('#status option:selected').val();

    // First reset to defaults
    $('#option-backup, #option-contact').css('display', 'block');
    $('#backup, #contact').removeAttr('disabled');

    if (selected === 'annual leave') {
      $('#option-contact').css('display', 'none');
      $('#contact').attr('disabled', 'disabled');
    }
    else if (selected === 'working at home') {
      $('#option-backup').css('display', 'none');
      $('#backup').attr('disabled', 'disabled');
    }
  }

  /**
   * Disable 'end' field when 'indefinite' is selected
   */
  _toggleEndDate = function () {
    $('#end').datepicker('setDate', null);
    $('#end').attr('disabled', $('#indefinite').is(':checked'));
  }


  _initialize();
  return _this;
};

// Instantiate
Status();
