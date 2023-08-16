(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.mtMagnificPopupFieldImage = {
    attach: function (context, settings) {
      $('.view-schedules table').addClass('table table-bordered');
    }
  };
})(jQuery, Drupal, drupalSettings);
