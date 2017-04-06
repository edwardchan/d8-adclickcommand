<?php

namespace Drupal\ad_click_command\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AdClickCommandSettingsForm.
 *
 * @package Drupal\ad_click_command\Form
 *
 * @ingroup ad_click_command
 */
class AdClickCommandSettingsForm extends FormBase {
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'ad_click_command_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        // Empty implementation of the abstract submit class.
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['contact_settings']['#markup'] = 'Settings form for ad_click_command. Manage field settings here.';
        return $form;
    }

}