<?php

namespace Drupal\g9\Plugin\Field\FieldWidget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\g9\Component\Utility\ArrayHelper;

/**
 * Plugin implementation of the 'Company Specific Autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "company_specific_entity_reference",
 *   label = @Translation("Company Specific Autocomplete"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class CompanySpecificEntityReferenceAutocompleteWidget extends EntityReferenceAutocompleteWidget {

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
    return [
      'companies' => [$term->id(), 'Thrillist'],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $terms = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadTree('brand');
    $companies = [];
    foreach ($terms as $term) {
      $companies[$term->tid] = $term->name;
    }

    $element['companies'] = [
      '#type' => 'checkboxes',
      '#target_type' => 'taxonomy_term',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'target_bundles' => ['brand'],
      ],
      '#options' => $companies,
      '#default_value' => $this->getSetting('companies'),
      '#description' => t('The companies that this field will be enabled for.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $companies = array_filter($this->getSetting('companies'));
    $output = [];
    foreach ($companies as $key => $tid) {
      $term = Term::load($tid);
      if ($term) {
        $output[] = $term->getName();
      }
    }

    $companies = implode(', ', $output);
    $summary[] = t('Companies: @companies', ['@companies' => $companies]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $company_allowed = FALSE;
    $allowed_companies = array_filter($this->getSetting('companies'));

    $node_exists = (bool) \Drupal::routeMatch()->getParameter('node');
    if (!$node_exists) {
      // If the node doesn't exist yet (currently being created), check to make
      // sure the company the user belongs to is an allowed company.
      $id = \Drupal::currentUser()->id();
      $user = User::load($id);

      $company_id = $user->get('field_brand')
        ->first()
        ->get('entity')
        ->getTarget()
        ->getValue()
        ->id();

      if (in_array($company_id, $allowed_companies)) {
        $company_allowed = TRUE;
      }
    }
    else {
      // If the node already exists, check to make sure the company it belongs
      // to is an allowed company.
      $nid = \Drupal::routeMatch()->getParameter('node')->id();
      $node = Node::load($nid);
      $companies = [];
      foreach ($node->get('field_brand')->getValue() as $term) {
        $target_id = ArrayHelper::get($term, 'target_id', FALSE);
        if ($target_id) {
          $companies[] = $target_id;
        }
      }
      foreach ($companies as $company) {
        if (in_array($company, $allowed_companies)) {
          $company_allowed = TRUE;
        }
      }
    }

    return ($company_allowed) ? parent::formElement($items, $delta, $element, $form, $form_state) : [];
  }

}
