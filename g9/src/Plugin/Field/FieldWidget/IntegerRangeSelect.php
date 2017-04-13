<?php

namespace Drupal\g9\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements simple dropdown widget for integer fields using min/max values.
 *
 * @FieldWidget(
 *   id = "integer_range_select",
 *   label = @Translation("Integer Select Dropdown"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class IntegerRangeSelect extends WidgetBase implements WidgetInterface {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Check for saved values or set to NULL.
    $value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;
    $field_settings = $this->getFieldSettings();
    // Get the 'min' and 'max' values as specified in the field settings.
    $range = range($field_settings['min'], $field_settings['max']);

    // Build the element render array.
    $element += [
      '#type' => 'select',
      '#default_value' => $value,
      '#options' => array_combine($range, $range),
      '#empty_option' => '--',
    ];

    // Add prefix and suffix.
    if ($field_settings['prefix']) {
      $prefixes = explode('|', $field_settings['prefix']);
      $element['#field_prefix'] = FieldFilteredMarkup::create(array_pop($prefixes));
    }
    if ($field_settings['suffix']) {
      $suffixes = explode('|', $field_settings['suffix']);
      $element['#field_suffix'] = FieldFilteredMarkup::create(array_pop($suffixes));
    }

    return ['value' => $element];
  }

}
