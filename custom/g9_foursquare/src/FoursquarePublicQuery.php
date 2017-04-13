<?php

namespace Drupal\g9_foursquare;

use Symfony\Component\HttpFoundation\JsonResponse;
use GuzzleHttp\Client;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\encrypt\EncryptService;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * FoursquarePublicQuery abstract class for making public queries.
 */
abstract class FoursquarePublicQuery implements FoursquareQueryInterface, ContainerInjectionInterface {

  /**
   * The base url to the Foursquare API.
   */
  const BASE_URL = 'https://api.foursquare.com/v2/';

  /**
   * The version of the API to use.
   */
  const API_VERSION = '20170101';

  /**
   * The endpoint to make the query.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * The parameters to pass to the query.
   *
   * @var array
   */
  protected $parameters;

  /**
   * The base url.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * The Guzzle client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The encryption profile.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile
   */
  protected $encryptionProfile;

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryption;

  /**
   * Returns the endpoint path to make the query.
   *
   * @return string
   *   The endpoint path.
   */
  public function endpointPath() {
    return '';
  }

  /**
   * FoursquarePublicQuery constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The Guzzle client.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\encrypt\EncryptService $encryption
   *   The encryption service.
   */
  public function __construct(Client $http_client, LoggerChannelFactory $logger, ConfigFactoryInterface $config_factory, EncryptService $encryption) {
    $this->httpClient = $http_client;
    $this->logger = $logger->get('g9_foursquare');
    $this->config = $config_factory->get('g9_foursquare.settings');
    $this->encryption = $encryption;
    $this->encryptionProfile = EncryptionProfile::load('foursquare');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('config.factory'),
      $container->get('encryption')
    );
  }

  /**
   * Executes the query to the Foursquare API.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function execute($params = []) {
    // Get the client key and secret from configuration. Use the encryption
    // profile to decrypt these keys.
    $client_key = $this->config->get('client_key');
    $client_key = $this->encryption->decrypt($client_key, $this->encryptionProfile);
    $client_secret = $this->config->get('client_secret');
    $client_secret = $this->encryption->decrypt($client_secret, $this->encryptionProfile);

    // The default API version is the const. The version should be in Ymd format
    // to represent the date of the latest version.
    $version = self::API_VERSION;
    // If a date is specified in the configuration, get the value of it and
    // format it to Ymd format.
    if ($version_date = $this->config->get('version_date')) {
      $date = DrupalDateTime::createFromFormat('Y-m-d', $version_date);
      $version_date = $date->format('Ymd');
    }

    // The required parameters to make the request to the API.
    $params += [
      'client_id' => $client_key,
      'client_secret' => $client_secret,
      'v' => $version_date,
    ];

    $foursquare = new FoursquareApi($client_key, $client_secret);

    try {
      $request = $this->httpClient->get(
        $this->getBaseUrl() . $this->getEndpoint() . '/' . implode('/', $this->arguments()) . '?' . http_build_query($params),
        ['headers' => ['Accept' => 'application/json']]
      );

      $response = json_decode($request->getBody());
      return new JsonResponse($response->response);
    }
    catch (ClientException $e) {
      $this->logger->error($e->getMessage());
    }

    return FALSE;
  }

  /**
   * Allows the providing of parameters to the query.
   *
   * @return array
   *   The array of parameters.
   */
  public function params() {
    return [];
  }

  /**
   * Returns the arguments.
   *
   * @return array
   *   The array of url arguments.
   */
  public function arguments() {
    return [];
  }

  /**
   * Returns the endpoint.
   */
  public function getEndpoint() {
    return $this->endpointPath();
  }

  /**
   * Returns the base url.
   */
  public function getBaseUrl() {
    if ($this->config->get('base_url')) {
      return $this->config->get('base_url');
    }
    return self::BASE_URL;
  }

}
