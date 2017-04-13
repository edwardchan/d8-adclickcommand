<?php

namespace Drupal\g9\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\BooleanCheckboxWidget;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\g9\Component\Utility\ArrayHelper;

/**
 * Plugin implementation of the 'Brand Specific Checkbox' widget.
 *
 * @FieldWidget(
 *   id = "brand_specific_checkbox",
 *   label = @Translation("Brand Specific Checkbox"),
 *   field_types = {
 *     "boolean"
 *   },
 *   multiple_values = TRUE
 * )
 */
class BrandSpecificCheckboxWidget extends BooleanCheckboxWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $properties = [
      'name' => 'Thrillist',
      'vid' => 'brand',
    ];
    $terms = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadByProperties($properties);
    $term = reset($terms);
    return array(
        'brands' => [$term->id(), 'Thrillist'],
      ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $terms = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadTree('brand');
    $brands = [];
    foreach ($terms as $term) {
        $brands[$term->tid] = $term->name;
    }

    $element['brands'] = array(
      '#type' => 'checkboxes',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'target_bundles' => array('brand'),
      ],
      '#options' => $brands,
      '#default_value' => $this->getSetting('brands'),
      '#description' => t('The brands that this field will be enabled for.'),
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();

    $brands = array_filter($this->getSetting('brands'));
    $output = [];
    foreach ($brands as $key => $tid) {
      $term = Term::load($tid);
      if ($term) {
        $output[] = $term->getName();
      }
    }

    $brands = implode(', ', $output);
    $summary[] = t('Brands: @brands', array('@brands' => $brands));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $brand_allowed = FALSE;
    $allowed_brands = array_filter($this->getSetting('brands'));

    $node_exists = (bool) \Drupal::routeMatch()->getParameter('node');
    if (!$node_exists) {
      // If the node doesn't exist yet (currently being created), check to make
      // sure the brand the user belongs to is an allowed brand.
      $id = \Drupal::currentUser()->id();
      $user = User::load($id);

      $brand_id = $user->get('field_brand')
        ->first()
        ->get('entity')
        ->getTarget()
        ->getValue()
        ->id();

      if (in_array($brand_id, $allowed_brands)) {
        $brand_allowed = TRUE;
      }
    }
    else {
      // If the node already exists, check to make sure the brand it belongs
      // to is an allowed brand.
      $nid = \Drupal::routeMatch()->getParameter('node')->id();
      $node = Node::load($nid);
      $brands = [];
      foreach ($node->get('field_brand')->getValue() as $term) {
        $target_id = ArrayHelper::get($term, 'target_id', FALSE);
        if ($target_id) {
            $brands[] = $target_id;
        }
      }
      foreach ($brands as $brand) {
        if (in_array($brand, $allowed_brands)) {
          $brand_allowed = TRUE;
        }
      }
    }

    return ($brand_allowed) ? parent::formElement($items, $delta, $element, $form, $form_state) : array();
  }

}
