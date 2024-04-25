<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * File contains CreatePipelineForm class.
 */

/**
 * Class CreatePipelineForm is for Pipelines.
 */
class CreatePipelineForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return "active_campaign_pipeline_creation";
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['wrapper'] = [
      '#type' => 'fieldset',
    ];

    $form['wrapper']['fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Add Pipeline Form.'),
      '#open' => FALSE,
    ];

    $form['wrapper']['fields']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pipeline Title'),
      '#required' => TRUE,
    ];

    $currencies = StoragesHandlers::supportedCurrencies();
    $form['wrapper']['fields']['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => $currencies,
      '#default_value' => ['USD'],
      '#required' => TRUE,
    ];

    $form['wrapper']['fields']['actions']['#type'] = 'actions';
    $form['wrapper']['fields']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    // Attach a library to the form element.
    $form['#attached']['library'][] = 'active_campaign_api_integration/manager_assets';
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $pipeline = [
      'allgroups' => 1,
      'allusers' => 0,
      'autoassign' => 1,
      'title' => $form_state->getValue('title'),
      'currency' => strtoupper($form_state->getValue('currency')),
    ];
    if ((new ApiCaller())->createPipeline($pipeline)) {
      active_campaign_api_integration_msg()->addMessage("Pipeline created
      successfully.");
    }
    else {
      active_campaign_api_integration_msg()->addError("Failed to create
      pipeline");
    }
  }

}
