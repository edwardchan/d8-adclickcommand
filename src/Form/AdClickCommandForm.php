<?php

namespace Drupal\ad_click_command\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the ad_click_command entity edit forms.
 *
 * @ingroup ad_click_command
 */
class AdClickCommandForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\ad_click_command\Entity\AdClickCommand */
        $form = parent::buildForm($form, $form_state);
        $entity = $this->entity;

        $form['langcode'] = array(
            '#title' => $this->t('Language'),
            '#type' => 'language_select',
            '#default_value' => $entity->getUntranslated()->language()->getId(),
            '#languages' => Language::STATE_ALL,
        );
        if ($entity->id()) {
            $form['generated'] = array(
                '#markup' => 'Generated URL ' . $entity->generateURL($entity->id()),
                '#weight' => 10,
            );
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state) {
        $form_state->setRedirect('entity.ad_click_command.collection');
        $entity = $this->entity;
        $entity->save();
    }

}
