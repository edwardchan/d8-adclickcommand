<?php

namespace Drupal\ad_click_command\Form;

use Drupal\Core\Entity\AdClickCommandConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting a ad_click_command entity.
 *
 * @ingroup ad_click_command
 */
class AdClickCommandDeleteForm extends ContentEntityConfirmFormBase {

    /**
     * {@inheritdoc}
     */
    public function getQuestion() {
        return $this->t('Are you sure you want to delete entity %name?', array('%name' => $this->entity->label()));
    }

    /**
     * {@inheritdoc}
     *
     * If the delete command is canceled, return to the contact list.
     */
    public function getCancelUrl() {
        return new Url('entity.ad_click_command.collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getConfirmText() {
        return $this->t('Delete');
    }

    /**
     * {@inheritdoc}
     *
     * Delete the entity and log the event. logger() replaces the watchdog.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $entity = $this->getEntity();
        $entity->delete();

        $this->logger('ad_click_command')->notice('@type: deleted %title.',
            array(
                '@type' => $this->entity->bundle(),
                '%title' => $this->entity->label(),
            ));
        $form_state->setRedirect('entity.ad_click_command.collection');
    }

}