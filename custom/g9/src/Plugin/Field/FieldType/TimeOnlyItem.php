<?php

namespace Drupal\g9\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldItemBase;

/**
 * Plugin implementation of the 'datetime' field type.
 *
 * @FieldType(
 *   id = "time_only",
 *   label = @Translation("Time Only"),
 *   description = @Translation("Create and store time values only."),
 *   default_widget = "time_only"
 * )
 */
class TimeOnlyItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'datetime_type' => 'time',
    ] + parent::defaultStorageSettings();
  }

  /**
   * Value for the 'datetime_type' setting: store only the time.
   */
  const DATETIME_TYPE_TIME = 'time';

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('datetime_iso8601')
      ->setLabel(t('Date value'))
      ->setRequired(TRUE);

    $properties['date'] = DataDefinition::create('any')
      ->setLabel(t('Computed date'))
      ->setDescription(t('The computed DateTime object.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\datetime\DateTimeComputed')
      ->setSetting('date source', 'value');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'description' => 'The time value.',
          'type' => 'varchar',
          'length' => 20,
        ],
      ],
      'indexes' => [
        'value' => ['value'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = [];

    $element['datetime_type'] = [
      '#type' => 'select',
      '#title' => t('Date type'),
      '#description' => t('Choose the type of date to create.'),
      '#default_value' => $this->getSetting('datetime_type'),
      '#options' => [
        static::DATETIME_TYPE_TIME => t('Time only'),
      ],
      '#disabled' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $type = $field_definition->getSetting('datetime_type');

    // Just pick a date in the past year. No guidance is provided by this Field
    // type.
    $timestamp = REQUEST_TIME - mt_rand(0, 86400 * 365);
    if ($type == DateTimeItem::DATETIME_TYPE_DATE) {
      $values['value'] = gmdate(DATETIME_DATE_STORAGE_FORMAT, $timestamp);
    }
    else {
      $values['value'] = gmdate(DATETIME_DATETIME_STORAGE_FORMAT, $timestamp);
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Enforce that the computed date is recalculated.
    if ($property_name == 'value') {
      $this->date = NULL;
    }
    parent::onChange($property_name, $notify);
  }

}
