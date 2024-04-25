<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * ActiveCampign api configs.
 */

/**
 * Form for saving api keys.
 *
 * @class Class to handle api keys.
 */
class ActiveCampaignForm extends FormBase {

  /**
   * Construct injecting dependencies.
   *
   * {@inheritdoc }
   */
  public function __construct(
    private readonly Connection $database,
    protected $messenger
  ) {

  }

  /**
   * Build and inject dependencies.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return static
   *   Returned static Object.
   */
  public static function create(ContainerInterface $container): static {
    return new self(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * Form getting this form id.
   *
   * @return string
   *   Return form id.
   */
  public function getFormId(): string {
    return 'active_campaign_settings';
  }

  /**
   * Building up form definitions.
   *
   * @param array $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This form state.
   *
   * @return array
   *   Array definitions.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['active_campaign_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Active Campaign Settings'),
    ];

    $form['active_campaign_settings']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('Enter the Active Campaign URL.'),
    ];

    $form['active_campaign_settings']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Enter the Active Campaign API Key.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    // Attach a library to the form element.
    $form['#attached']['library'][] = 'active_campaign_api_integration/manager_assets';

    return $form;
  }

  /**
   * Validation of this form submission.
   *
   * @param array $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This form state.
   *
   * @return void
   *   Nothing returns
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Validate the URL field.
    $url = $form_state->getValue('url');
    if (empty($url)) {
      $form_state->setErrorByName('url', $this->t('The URL field is required.'));
    }

    // Validate the API Key field.
    $api_key = $form_state->getValue('api_key');
    if (empty($api_key)) {
      $form_state->setErrorByName('api_key', $this->t('The API Key field is required.'));
    }
  }

  /**
   * Handling the saving of the final data after validation.
   *
   * @param array $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This form state.
   *
   * @return void
   *   Nothing.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $url = $form_state->getValue("url");
    $token = $form_state->getValue("api_key");
    $storage = new StoragesHandlers($this->database);
    if ($storage->saveApiKeys($url, $token)) {
      $this->messenger->addMessage("ActiveCampaign Keys Added.");
    }
    else {
      $this->messenger->addError("Failed to add ActiveCampaign Keys");
    }
  }

}
