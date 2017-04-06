<?php

namespace Drupal\g9\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'Thrillist Highlight' formatter.
 *
 * @FieldFormatter (
 *   id = "thrillist_highlight",
 *   label = @Translation("Thrillist Highlight"),
 *   field_types = {
 *     "thrillist_highlight"
 *   }
 * )
 */
class ThrillistHighlightFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = $item->value;
    }

    return $elements;
  }

}
