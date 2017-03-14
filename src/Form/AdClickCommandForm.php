<?php

namespace Drupal\adclickcommand\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Language\Language;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the adclickcommand entity edit forms.
 *
 * @ingroup adclickcommand
 */
class AdClickCommandForm extends ContentEntityForm {

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        /* @var $entity \Drupal\adclickcommand\Entity\AdClickCommand */
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
        $form_state->setRedirect('entity.adclickcommand.collection');
        $entity = $this->getEntity();
        $entity->save();
    }

}
