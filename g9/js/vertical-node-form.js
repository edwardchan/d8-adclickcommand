/**
 * @file
 * Vertical mapping functionality.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  function performFieldFiltering(selected = null) {
    if (selected === undefined || selected === null) {
      return;
    }

    // Use a regex to extract the term id from the value string.
    var match = selected.match(/.+\s\(([^\)]+)\)/);
    var tid = (match && match.length > 1) ? match[1] : null;

    // If there is no tid found from the regex, return.
    if (!tid) {
      return;
    }

    // Get the mapping from the value passed in from the hook_form_alter().
    var mapping = drupalSettings.g9.vertical_mapping;
    // The key to look for in the mapping. This will determine which field
    // the selected "Brand" will be affect.
    var key = 'vertical_vocabulary_' + tid;
    Object.keys(mapping).forEach(function (name) {
      if (mapping[name] === undefined) {
        return;
      }

      // Get the field name and use it to find the parent form-wrapper element.
      var field_name = mapping[name];
      var $formWrapper = $("*[name='" + field_name + "[0][target_id]']").closest('.form-wrapper');

      // Check that the element exists.
      if (!$formWrapper.length) {
        return;
      }

      // Show the form elements of the "vertical" fields that map to that of
      // the selected "Brand".
      if (key === name) {
        $formWrapper.show();
      }
      // Otherwise, hide the field from display.
      else {
        $formWrapper.hide();
      }
    });
  }

  Drupal.behaviors.filterOptions = {
    attach: function (context) {
      var value = $('input[name="field_brand[0][target_id]"]').val();
      if (value) {
        // Filter the vertical fields based on the selected brand.
        performFieldFiltering(value);
      }

      // Event listener for when the autocomplete field loses focus.
      $('input[name="field_brand[0][target_id]"]').focusout(function () {
        // If the value of the autocomplete is null or empty, all the vertical
        // fields from the mapping should be shown.
        if ($(this).val() == '') {
          var mapping = drupalSettings.g9.vertical_mapping;
          Object.keys(mapping).forEach(function (name) {
            var field_name = mapping[name];
            $("*[name='" + field_name + "[0][target_id]']").closest('.form-wrapper').show();
          });
        }
      });

      // Event listener for when an option is selected in the autocomplete.
      $('input[name="field_brand[0][target_id]"]').on('autocompleteselect', function (event, ui) {
        // Get the value of the selected item. This value will be the label with
        // the term id in parentheses - i.e., "Thrillist (3)" where 3 is the
        // term id of the "Thrillist" term.
        var selected = ui.item.value;

        // Filter the vertical fields based on the selected brand.
        performFieldFiltering(selected);
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
