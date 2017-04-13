<?php

namespace Drupal\g9\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'Thrillist Highlight' widget.
 *
 * @FieldWidget (
 *   id = "thrillist_highlight",
 *   label = @Translation("Thrillist Highlight widget"),
 *   field_types = {
 *     "thrillist_highlight"
 *   }
 * )
 */
class ThrillistHighlightWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {
    $element['url'] = [
      '#type' => 'textfield',
      '#title' => t('URL path'),
      '#default_value' => isset($items[$delta]->url) ? $items[$delta]->url : '',
    ];
    $element['weight'] = [
      '#type' => 'number',
      '#title' => t('Weight'),
      '#default_value' => isset($items[$delta]->weight) ? $items[$delta]->weight : 0,
    ];

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += [
        '#type' => 'fieldset',
        '#attributes' => ['class' => ['container-inline']],
      ];
    }

    return $element;
  }

}
