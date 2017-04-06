/**
 * @file
 * Venue import functionality.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Prepopulates fields on the venue node form w/ the values returned from API.
   */
  Drupal.behaviors.prepopulate_fields = {
    attach: function (context) {
      // Get the venue field mapping from Drupal. This is set in the
      // hook_form_alter() in g9_foursquare.module.
      var mapping = drupalSettings.g9_foursquare.mapping;
      // Iterate through each of the name => value mapping and set the values
      // of the form input elements to the values.
      Object.keys(mapping).forEach(function (name) {
        if (mapping[name]) {
          $("*[name='" + name + "'").val(mapping[name]);
        }
      });

      // Set the venue API ID field to readonly.
      $('#edit-field-venue-api-id-0-value').prop('readonly', true);
    }
  }

})(jQuery, Drupal, drupalSettings);
