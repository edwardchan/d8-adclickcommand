/**
 * @file
 * Duplicate venue confirmation dialog.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Displays a confirm dialog if an a venue already exists with the same ID.
   */
  Drupal.behaviors.duplicate_confirm = {
    attach: function (context) {
      $('#node-venue-form').on('submit', function () {
        // Get the venue field mapping from Drupal. This is set in the
        // hook_form_alter() in g9_foursquare.module.
        var alreadyExists = drupalSettings.g9_foursquare.venue_exists;
        if (alreadyExists) {
          return confirm('A venue already exists with the venue API id "' + drupalSettings.g9_foursquare.venue_id + '". Do you want to continue?');
        }
      });
    }
  }

})(jQuery, Drupal, drupalSettings);
