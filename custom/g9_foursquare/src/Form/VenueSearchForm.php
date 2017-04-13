<?php

namespace Drupal\g9_foursquare\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\g9_foursquare\FoursquareVenueSearch;
use Drupal\user\PrivateTempStoreFactory;

/**
 * The venue search form.
 */
class VenueSearchForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The Foursquare venue search.
   *
   * @var \Drupal\g9_foursquare\FoursquareVenueSearch
   */
  protected $venueSearch;

  /**
   * The temporary storage.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * VenueSearchForm constructor.
   *
   * @param \Drupal\g9_foursquare\FoursquareVenueSearch $venue_search
   *   The Foursquare venue search.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temporary storage factory.
   */
  public function __construct(FoursquareVenueSearch $venue_search, PrivateTempStoreFactory $temp_store_factory) {
    $this->venueSearch = $venue_search;
    $this->tempStore = $temp_store_factory->get('g9_foursquare');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('g9_foursquare.venue_search'),
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'g9_foursquare_venue_search';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="search-venues-wrapper">';
    $form['#suffix'] = '</div>';
    $form['#tree'] = TRUE;

    // The search term.
    $form['search_term'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Term'),
      '#description' => $this->t('The search term to search for.'),
      '#weight' => -90,
    ];

    // The location to search for.
    $form['near'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Near'),
      '#description' => $this->t('The area to search for.'),
      '#weight' => -90,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['search'] = [
      '#type' => 'submit',
      '#id' => 'search_venues',
      '#value' => $this->t('Search'),
      '#ajax' => [
        'callback' => [$this, 'searchAjaxCallback'],
        'event' => 'click',
        'wrapper' => 'search-venues-wrapper',
      ],
      '#weight' => -89,
    ];

    $form['venues'] = [
      '#type' => 'container',
      '#title' => $this->t('Venues'),
      '#weight' => -90,
    ];

    $headers = [
      'name' => $this->t('Name'),
      'location' => $this->t('Location'),
      'address' => $this->t('Address'),
    ];

    // The array to hold all the venues to be displayed in tableselect element.
    $options = [];
    // If there are venues after a form re-build, loop through them to generate
    // the options array.
    if (!empty($form_state->getValue('venues'))) {
      // Iterate through each of the venues to generate the options array that
      // will be used in the tableselect.
      foreach ($form_state->getValue('venues') as $key => $venue) {
        // If the venue ID does not exist, move to the next one.
        if (!isset($venue['id']) || !$id = $venue['id']) {
          continue;
        }

        // Set the options for the search results tableselect.
        $options[$id] = [
          'name' => isset($venue['name']) ? $venue['name'] : '',
          'location' => isset($venue['location']['city']) ? $venue['location']['city'] : '',
          'address' => isset($venue['location']['address']) ? $venue['location']['address'] : '',
        ];
      }

      $form['import'] = [
        '#id' => 'import_venues',
        '#type' => 'submit',
        '#value' => $this->t('Import selected'),
        '#ajax' => [
          'callback' => [$this, 'importSelectedAjax'],
          'event' => 'click',
          'wrapper' => 'search-venues-wrapper',
        ],
      ];
    }

    // The venue results from the search.
    $form['search_results'] = [
      '#type' => 'tableselect',
      '#header' => $headers,
      '#options' => $options,
      '#empty' => $this->t('No results.'),
      '#weight' => -60,
      '#multiple' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // If the import button was clicked, make sure there is something selected
    // in the tableselect.
    if (isset($triggering_element['#id']) && $triggering_element['#id'] == 'import_venues') {
      // If no venues have been selected, set an error.
      if (!$form_state->getValue('search_results')) {
        $form_state->setErrorByName('import', $this->t('A venue must be selected for import.'));
        return $form;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $term = $form_state->getValue('search_term');
    $near = $form_state->getValue('near');

    $results = $this->venueSearch->searchFor($term, $near);
    if ($results) {
      $results = json_decode($results->getContent(), TRUE);
    }

    if (!isset($results['venues'])) {
      return $form;
    }

    $form_state->setValue('venues', $results['venues']);

    $form_state->setCached(FALSE);
    $form_state->setRebuild();
  }

  /**
   * The AJAX callback for importing the selected AJAX.
   */
  public function importSelectedAjax($form, FormStateInterface $form_state) {
    // If there are any form errors, do not redirect.
    if ($form_state->hasAnyErrors()) {
      return $form;
    }

    // Get the venue details for the venue selected on the search results table.
    $results = $this->venueSearch->getVenueDetails($form_state->getValue('search_results'));
    if ($results) {
      $venue_details = json_decode($results->getContent(), TRUE);
    }
    // Store the venue details array in a temporary storage for retrieval on
    // the venue node add page.
    $this->tempStore->set('venue_details', $venue_details);

    $response = new AjaxResponse();
    // Get the url for the venue node/add page and redirect to that page.
    $url = Url::fromRoute('node.add', ['node_type' => 'venue'])->toString();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

  /**
   * The AJAX callback for the search submit.
   */
  public function searchAjaxCallback($form, FormStateInterface $form_state) {
    return $form;
  }

}
