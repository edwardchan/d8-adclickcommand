<?php

namespace Drupal\g9\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\jwt\Authentication\Event\JwtAuthEvents;
use Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class JwtAuthIssuerSubscriber.
 *
 * @package Drupal\jwt_auth_issuer
 */
class JwtAuthRestIssuerSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The active request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(AccountInterface $user, RequestStack $request_stack) {
    $this->currentUser = $user;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set a priority higher than that in JwtAuthIssuerSubscriber, so these
    // get picked up first.
    $events[JwtAuthEvents::GENERATE][] = ['setStandardClaims', 101];
    $events[JwtAuthEvents::GENERATE][] = ['setDrupalClaims', 100];
    return $events;
  }

  /**
   * Sets the standard claims set for a JWT.
   *
   * @param \Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent $event
   *   The event.
   */
  public function setStandardClaims(JwtAuthGenerateEvent $event) {
    $request = $this->requestStack->getCurrentRequest();
    // If there is a client-id specified, set it as the 'aud' claim. By now,
    // all the validation should have been completed and an invalid client-id
    // would have thrown an error in one of the VALID/VALIDATE events.
    if ($client_id = $request->headers->get('client-id')) {
      $event->addClaim('aud', $client_id);
    }
    $event->addClaim('iat', time());
    // @todo: make these more configurable.
    $event->addClaim('exp', strtotime('+1 hour'));
  }

  /**
   * Sets claims for a Drupal consumer on the JWT.
   *
   * @param \Drupal\jwt\Authentication\Event\JwtAuthGenerateEvent $event
   *   The event.
   */
  public function setDrupalClaims(JwtAuthGenerateEvent $event) {
    // Add user's uid to the JWT payload.
    $event->addClaim(
      ['drupal', 'uid'],
      $this->currentUser->id()
    );
    // Add roles to the JWT payload.
    $event->addClaim(
      ['drupal', 'roles'],
      $this->currentUser->getRoles()
    );
  }

}
