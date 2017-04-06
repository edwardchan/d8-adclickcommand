<?php

namespace Drupal\g9\EventSubscriber;

use Drupal\jwt\Authentication\Event\JwtAuthValidateEvent;
use Drupal\jwt\Authentication\Event\JwtAuthValidEvent;
use Drupal\jwt\Authentication\Event\JwtAuthEvents;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\jwt_auth_consumer\EventSubscriber\JwtAuthConsumerSubscriber;

/**
 * Listens to the dynamic route events.
 */
class JwtAuthRestSubscriber extends JwtAuthConsumerSubscriber {

  /**
   * A User Interface.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[JwtAuthEvents::VALIDATE][] = ['validate'];
    $events[JwtAuthEvents::VALID][] = ['loadUser'];

    return $events;
  }

  /**
   * Validates that a uid and roles are present in the JWT.
   *
   * This validates the format of the JWT and validate the uid is a
   * valid uid in the system.
   *
   * @param \Drupal\jwt\Authentication\Event\JwtAuthValidateEvent $event
   *   A JwtAuth event.
   */
  public function validate(JwtAuthValidateEvent $event) {
    $token = $event->getToken();
    // If there is an 'aud' claim value, that means the call is coming from a
    // client and has already been validated. Otherwise, the JWT token would
    // have returned an error for an invalid client-secret combination.
    if ($token->getClaim('aud')) {
      return TRUE;
    }

    // Otherwise, if no client is set, look for the Drupal user to validate.
    $uid = $token->getClaim(['drupal', 'uid']);
    if ($uid === NULL) {
      $event->invalidate("No Drupal uid was provided in the JWT payload.");
    }
    $user = $this->entityManager->getStorage('user')->load($uid);
    if ($user === NULL) {
      $event->invalidate("No UID exists.");
    }

    $roles = $token->getClaim(['drupal', 'roles']);

    // If the user does not have the appropriate roles, deny access.
    if (!$user->hasPermission('access rest endpoints')) {
      $event->invalidate('User does not have access.');
    }
  }

  /**
   * Load and set a Drupal user to be authentication based on the JWT's uid.
   *
   * @param \Drupal\jwt\Authentication\Event\JwtAuthValidEvent $event
   *   A JwtAuth event.
   */
  public function loadUser(JwtAuthValidEvent $event) {
    $token = $event->getToken();
    $user_storage = $this->entityManager->getStorage('user');
    $uid = $token->getClaim(['drupal', 'uid']);
    $user = $user_storage->load($uid);
    $event->setUser($user);
  }

}
