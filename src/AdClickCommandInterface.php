<?php

namespace Drupal\ad_click_command;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup ad_click_command
 */
interface AdClickCommandInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}