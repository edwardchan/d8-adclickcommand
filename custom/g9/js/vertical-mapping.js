/**
 * @file
 * Vertical mapping functionality.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.filterOptions = {
    attach: function (context) {
      if (context == document && $('input[name="filter_by_vertical_string"]').length) {
        checkFilter();
      }
      // The checkbox change listener.
      $('input[name="filter_by_vertical_string"]').change(function () {
        checkFilter();
      });
    }
  };

  /**
   * Checks if the options should be filtered by those that contain 'vertical'.
   */
  function checkFilter() {
    // If the filter checkbox is checked, filter the select options to only
    // the vocabularies that contain 'vertical' in the machine name.
    if ($('input[name="filter_by_vertical_string"]').is(':checked')) {
      $('.vertical_mapping_options option').not('[value*="vertical"]').hide();
    }
    // Otherwise, show all the vocabulary options.
    else {
      $('.vertical_mapping_options option').show();
    }
  }

})(jQuery, Drupal, drupalSettings);
