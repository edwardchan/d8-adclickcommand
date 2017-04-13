<?php

namespace Drupal\g9\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\datetime\Plugin\Field\FieldWidget\DateTimeWidgetBase;

/**
 * Plugin implementation of the 'time_only' widget.
 *
 * @FieldWidget(
 *   id = "time_only",
 *   label = @Translation("Time Only Widget"),
 *   field_types = {
 *     "time_only"
 *   }
 * )
 */
class TimeOnlyWidget extends DateTimeWidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The date format storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $dateStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityStorageInterface $date_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->dateStorage = $date_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager')->getStorage('date_format')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#title'] = $this->t('Time title');

    $date_obj = NULL;
    // If there is a value, convert the string value to a DateTime object and
    // assign that as the default value of the form field.
    if ($date = $items[$delta]->value) {
      $format = $this->dateStorage->load('thrillist_time_format')->getPattern();
      $date_obj = DrupalDateTime::createFromFormat($format, $date);
    }

    $element['value'] = [
      '#type' => 'datetime',
      '#default_value' => $date_obj,
      '#date_increment' => 1,
      '#date_timezone' => drupal_get_user_timezone(),
      '#required' => $element['#required'],
      // Hide the date element from the form.
      '#date_date_element' => 'none',
      '#date_time_element' => 'time',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item['value'];
        switch ($this->getFieldSetting('datetime_type')) {
          case 'time':
            // Get the date pattern for the 'thrillist_time_format' date format
            // and set the default storage format of the time only field.
            $format = $this->dateStorage->load('thrillist_time_format')->getPattern();
            break;

          default:
            $format = DATETIME_DATETIME_STORAGE_FORMAT;
            break;
        }
        // Adjust the date for storage.
        $start_date->setTimeZone(timezone_open(drupal_get_user_timezone()));
        $item['value'] = $start_date->format($format);
      }
    }

    return $values;
  }

}
