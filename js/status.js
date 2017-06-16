(function() {

  $(document).ready(function() {
    initConfirm();
    initForm();
  });

  var initConfirm = function() {

    // attach modal confirm window to delete buttons, set html
    $('.delete').on('click', function(e) {
      var box_text = $(this).parents('.alert-box').html(),
        status_msg = box_text.split('<ul class="actions">'),
        del_href = $(this).attr('href');
      e.preventDefault();
      $('#deleteModal p').html(status_msg[0]);
      $('#delete').attr('href', del_href);
      $('#deleteModal').reveal({animationSpeed: 150});
    });

    // close modal
    $('#cancel').on('click', function(e) {
      $('#deleteModal').trigger('reveal:close');
    });
  };

  var initForm = function() {

    // show / hide fields based on status selection
    swapFields();

    $('#status').on('change', function() {
      swapFields();
    });

    // disable 'to' field when indefinite is checked
    if ($('#indefinite').is(':checked')) {
      disableToField();
    }

    $('#indefinite').on('change', function() {
      disableToField();
    });

    // set up date picker
    $.datepicker.setDefaults({
      showAnim: false,
      constrainInput: false,
      //dateFormat: 'D, M d, yy',
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
        if (!$('#end').val() && !$('#indefinite').is(':checked')) { // set 'to' date to default to 'from' if empty
          $('#end').datepicker('setDate', selDate);
        }
      }
    });
    $('#end').datepicker({
      onSelect: function(selDate) {
        $('#begin').datepicker('option', 'maxDate', selDate);
        if (!$('#begin').val()) { // set 'from' date to default to 'to' if empty
          $('#begin').datepicker('setDate', selDate);
        }
      }
    });

    // require status, date fields
    $('#submit').on('click', function(e) {
      $('input, select').removeClass('error');
      $('p.error').remove();
      var selector,
        show_error = function(elem, msg) {
          e.preventDefault();
          $(elem).addClass('error');
          if (elem === '#status') {
            selector = elem;
          } else {
            selector = '#forever';
          }
          $(selector).after('<p class="error">' + msg + '</p>');
        };

      if (!$('#end').val() && !$('#indefinite').is(':checked')) {
        //show_error('#end', 'Please enter an end date');
      }
      if (!$('#begin').val()) {
        show_error('#begin', 'Please enter a beginning date');
      }
      if (!$('#status option:selected').val()) {
        show_error('#status', 'Please select a status');
      }
    });
  };

  // disable 'to' field if indefinite selected
  var disableToField = function () {
    $("#end").attr('disabled', $('#indefinite').is(':checked'));
    $("#end").datepicker('setDate', null);
    $("#begin").datepicker('option', 'maxDate', '+2y');
  }

  // show/hide fields based on status selected
  var swapFields = function() {
    var sel = $('#status option:selected').val();

    // first reset to defaults
    $('#option-contact').css('display', 'block');
    $('#contact').removeAttr('disabled');
    $('#option-backup').css('display', 'block');
    $('#backup').removeAttr('disabled');

    if (sel === 'annual leave') {
      $('#option-contact').css('display', 'none');
      $('#contact').attr('disabled', 'disabled');
    } else if (sel === 'working at home') {
      $('#option-backup').css('display', 'none');
      $('#backup').attr('disabled', 'disabled');
    }
  };

})();
