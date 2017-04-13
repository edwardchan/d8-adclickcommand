<?php

namespace Drupal\g9_foursquare\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\encrypt\EncryptService;

/**
 * Distribution Configuration form class.
 */
class ConfigForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The encryption profile.
   *
   * @var \Drupal\encrypt\Entity\EncryptionProfile
   */
  protected $encryptionProfile;

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptService
   */
  protected $encryption;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encrypt\EncryptService $encryption
   *   The encryption service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptService $encryption) {
    $this->encryptionProfile = EncryptionProfile::load('foursquare');
    $this->encryption = $encryption;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encryption')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'g9_foursquare_config_form';
  }

  /**
   * Returns an encrypted value.
   *
   * @param string $value
   *   The value to encrpt.
   *
   * @return string
   *   The encrypted string.
   */
  public function encrypt($value) {
    return $this->encryption->encrypt($value, $this->encryptionProfile);
  }

  /**
   * Returns an decrypted value.
   *
   * @param string $value
   *   The value to encrpt.
   *
   * @return string
   *   The encrypted string.
   */
  public function decrypt($value) {
    return $this->encryption->decrypt($value, $this->encryptionProfile);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('g9_foursquare.settings');

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Base Url'),
      '#description' => $this->t('The base url to the Foursquare API.'),
      '#default_value' => $config->get('base_url'),
      '#required' => TRUE,
    ];
    $form['version_date'] = [
      '#type' => 'date',
      '#title' => t('Version Date'),
      '#description' => $this->t('This required date is the version of the Foursquare API expected to be used. For more information, see the <a href=":url" target="_blank">documentation</a>.', [':url' => 'https://developer.foursquare.com/overview/versioning']),
      '#default_value' => $config->get('version_date'),
      '#required' => TRUE,
    ];
    $form['limit'] = [
      '#type' => 'number',
      '#title' => t('Number Of Results'),
      '#description' => $this->t('Number of results returned per query.'),
      '#default_value' => $config->get('limit') ? $config->get('limit') : 100,
      '#required' => TRUE,
    ];
    $form['client_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Key'),
      '#description' => $this->t('The client key to access the Foursquare API.'),
      '#default_value' => $this->decrypt($config->get('client_key')),
      '#required' => TRUE,
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#description' => $this->t('The client secret to access the Foursquare API.'),
      '#default_value' => $this->decrypt($config->get('client_secret')),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('g9_foursquare.settings');

    $config->set('base_url', $form_state->getValue('base_url'));
    $config->set('version_date', $form_state->getValue('version_date'));
    $config->set('limit', $form_state->getValue('limit'));
    $config->set('client_key', $this->encrypt($form_state->getValue('client_key')));
    $config->set('client_secret', $this->encrypt($form_state->getValue('client_secret')));

    // Save the configuration.
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return the configuration names.
   */
  protected function getEditableConfigNames() {
    return [
      'g9_foursquare.settings',
    ];
  }

}
