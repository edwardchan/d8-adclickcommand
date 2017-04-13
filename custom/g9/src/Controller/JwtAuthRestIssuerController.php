<?php

namespace Drupal\g9\Controller;

use Drupal\jwt\Authentication\Provider\JwtAuth;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\key\KeyRepositoryInterface;
use Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent;
use Drupal\jwt\Authentication\Event\JwtAuthEvents;
use Drupal\jwt\Transcoder\JwtTranscoderInterface;
use Drupal\jwt\JsonWebToken\JsonWebToken;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class JwtAuthIssuerController.
 *
 * @package Drupal\jwt_auth_issuer\Controller
 */
class JwtAuthRestIssuerController extends ControllerBase {

  /**
   * The JWT Auth Service.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  private $auth;

  /**
   * The key repository.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * The JWT Transcoder service.
   *
   * @var \Drupal\jwt\Transcoder\JwtTranscoderInterface
   */
  protected $transcoder;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   *
   * JwtAuthRestIssuerController constructor.
   *
   * @param \Drupal\jwt\Authentication\Provider\JwtAuth $auth
   *   The JWT auth service.
   * @param \Drupal\key\KeyRepositoryInterface $key_repo
   *   The key repository.
   * @param \Drupal\jwt\Transcoder\JwtTranscoderInterface $transcoder
   *   The JWT transcoder.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(JwtAuth $auth, KeyRepositoryInterface $key_repo, JwtTranscoderInterface $transcoder, EventDispatcherInterface $event_dispatcher) {
    $this->auth = $auth;
    $this->keyRepository = $key_repo;
    $this->transcoder = $transcoder;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jwt.authentication.jwt'),
      $container->get('key.repository'),
      $container->get('jwt.transcoder'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Generate.
   *
   * @return string
   *   Return Hello string.
   */
  public function tokenResponse(Request $request) {
    $response = new \stdClass();
    if (!$client_id = $request->headers->get('client-id')) {
      $response->error = "The client ID is required.";
      return new JsonResponse($response, 500);
    }
    if (!$secret = $request->headers->get('secret')) {
      $response->error = "The secret is required.";
      return new JsonResponse($response, 500);
    }

    $key = $this->keyRepository->getKey($client_id);
    if (is_null($key)) {
      $response->error = "The client id provided ($client_id) does not exist.";
      return new JsonResponse($response, 500);
    }

    $key_value = $key->getKeyValue();
    if ($key_value != $secret) {
      $response->error = "The secret provided is invalid.";
      return new JsonResponse($response, 500);
    }

    // Based on the application id, get the secret stored in the key repository.
    // If it is there and matches, continue to generate the token. Otherwise,
    // reject the request.
    $token = $this->generateToken($secret);
    if ($token === FALSE) {
      $response->error = "Error. Please set a key in the JWT admin page.";
      return new JsonResponse($response, 500);
    }

    $response->token = $token;
    return new JsonResponse($response);
  }

  /**
   * Generates the token based on the secret, if any.
   *
   * @param string|null $secret
   *   The secret to generate the token with.
   *
   * @return string
   *   The token string.
   */
  public function generateToken($secret = '') {
    $event = new JwtAuthGenerateEvent(new JsonWebToken());
    $this->eventDispatcher->dispatch(JwtAuthEvents::GENERATE, $event);
    $jwt = $event->getToken();
    $transcoder = $this->transcoder;

    return $transcoder->encode($jwt);
  }

}
