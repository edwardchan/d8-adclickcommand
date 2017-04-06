<?php

namespace Drupal\g9_foursquare;

use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encrypt\EncryptService;

/**
 * The class that performs the Foursquare venue search.
 */
class FoursquareVenueSearch extends FoursquarePublicQuery {

  /**
   * The Foursquare venue details search.
   *
   * @var \Drupal\g9_foursquare\FoursquareVenueSearchDetails
   */
  protected $venueSearchDetails;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * FoursquareVenueSearch constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\encrypt\EncryptService $encryption
   *   The encryption service.
   * @param \Drupal\g9_foursquare\FoursquareVenueSearchDetails $venue_search_details
   *   THe venue search details service.
   */
  public function __construct(Client $http_client, LoggerChannelFactory $logger, ConfigFactoryInterface $config_factory, EncryptService $encryption, FoursquareVenueSearchDetails $venue_search_details) {
    parent::__construct($http_client, $logger, $config_factory, $encryption);
    $this->venueSearchDetails = $venue_search_details;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('encryption'),
      $container->get('g9_foursquare.venue_search_details')
    );
  }

  /**
   * Executes the search with the provided parameters.
   *
   * @param string $search_term
   *   The search term.
   * @param string $city
   *   The city to search in.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response containing the results.
   */
  public function searchFor($search_term, $city) {
    $limit = $this->config->get('limit');
    $parameters = [
      'query' => $search_term,
      'near' => $city,
      'limit' => $limit,
    ];

    return $this->execute($parameters);
  }

  /**
   * Executes the query for the venue.
   *
   * @param string $venue_id
   *   The venue ID.
   */
  public function getVenueDetails($venue_id = '') {
    if (!$venue_id) {
      return NULL;
    }
    return $this->venueSearchDetails->searchFor($venue_id);
  }

  /**
   * {@inheritdoc}
   */
  public function endpointPath() {
    return 'venues/search';
  }

}
