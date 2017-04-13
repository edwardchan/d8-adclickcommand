<?php

namespace Drupal\g9\EventSubscriber;

use Drupal\autotag\TagEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class TagEventSubscriber.
 *
 * @package Drupal\g9
 */
class TagEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[TagEvent::TAG][] = ['excludeTags', 800];
    return $events;
  }

  /**
   * Subscriber Callback for the event.
   *
   * Exclude terms that have the exclude field checked (or parent does).
   *
   * @param \Drupal\autotag\TagEvent $event
   *   The tag event object.
   */
  public function excludeTags(TagEvent $event) {
    $exclude = FALSE;
    $term = $event->getTerm();
    if ($term->hasField('field_tags_autotag_exclude')) {
      $exclude = $term->get('field_tags_autotag_exclude')->first()->value;
      if (!$exclude) {
        // Check if the parent term is excluded.
        $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term->id());
        $parent = reset($parents);
        if ($parent) {
          $exclude = $parent->get('field_tags_autotag_exclude')->first()->value;
        }
      }
    }

    if ($exclude) {
      // If either the term or its parent are set to exclude, deny the tagging.
      $event->deny();
    }
  }

}
