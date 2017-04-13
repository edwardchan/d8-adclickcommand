<?php

namespace Drupal\g9\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\Views;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\user\Entity\User;

/**
 * Filters nodes based on user's company.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("company_access_filter")
 */
class CompanyAccessFilter extends FilterPluginBase implements ContainerFactoryPluginInterface {

  protected $currentUser;

  /**
   * Creates the CompanyAccessFilter object.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Session\AccountProxy $account
   *   The current user object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxy $account) {
    $this->currentUser = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    return $this->t('Company Access Filter');
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
    // There are no operators for this filter, so.
  }

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    // We don't need to expose anything to the user.
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $configuration = [
      'table' => 'node__field_brand',
      'field' => 'entity_id',
      'left_table' => 'node_field_data',
      'left_field' => 'nid',
      'operator' => '=',
    ];

    // Load the current user and get the target ids of all the 'company' terms
    // tagged on the user object. These companies will be used to in a condition
    // to filter the nodes.
    $user_id = $this->currentUser->id();
    $user = User::load($user_id);

    // If the user has the Administrator role , we do not need to limit the
    // results based on their company data.
    if (!$user->hasRole('administrator') && $user->id() !== 1) {

      // Create a join with the 'node__field_company' table based on the config.
      $join = Views::pluginManager('join')
        ->createInstance('standard', $configuration);
      $this->query->addRelationship('node__field_brand', $join, 'node_field_data');

      // Create an OR condition group, so that nodes are displayed if the node
      // references a company assigned to the user, OR, if the node references
      // no company - in which case, should be displayed to all users.
      $condition = db_or()
        ->condition('node__field_brand.field_brand_target_id', NULL, 'IS NULL');

      $user_companies = [];
      if ($user->hasField('field_brand') && $user->get('field_brand')->getValue()
      ) {
        $user_company_references = $user->get('field_brand')
          ->referencedEntities();
        foreach ($user_company_references as $company) {
          $user_companies[] = $company->id();
        }
      }

      if (!empty($user_companies)) {
        $condition->condition('node__field_brand.field_brand_target_id', $user_companies, 'IN');
      }

      $this->query->addWhere($this->options['group'], $condition);
    }
  }

}
