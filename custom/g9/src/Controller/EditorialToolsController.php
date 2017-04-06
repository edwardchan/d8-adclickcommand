<?php

namespace Drupal\g9\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class UtilController.
 *
 * @package Drupal\util\Controller
 */
class EditorialToolsController extends ControllerBase {

  /**
   * Editorial_tools.
   *
   * @return string
   *   Return Hello string.
   */
  public function editorialTools() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: editorialTools with parameter(s): $name'),
    ];
  }

}
