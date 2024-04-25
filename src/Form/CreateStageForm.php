<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * The File contains CreateStageForm class.
 */

/**
 * Class CreateStageForm handles Stages.
 */
class CreateStageForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return "campaign_stage_creation";
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $pipelines = (new ApiCaller())->pipeLines();
    $pipelineOptions = [];
    foreach ($pipelines as $key => $pipeline) {
      $pipelineOptions[$pipeline['id']] = $pipeline['title'];
    }
    $form['fields'] = [
      '#type' => 'fieldset',
    ];
    $form['fields']['wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Stages Creation Form'),
      '#open' => FALSE,
    ];
    $form['fields']['wrapper']['pipeline'] = [
      '#type' => 'select',
      '#title' => $this->t('Pipeline'),
      '#options' => $pipelineOptions,
      '#required' => TRUE,
    ];
    $form['fields']['wrapper']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
    ];
    $form['fields']['wrapper']['actions']['#type'] = 'actions';
    $form['fields']['wrapper']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ((new ApiCaller())->createStage(
      [
        'title' => $form_state->getValue('title'),
        'group' => $form_state->getValue('pipeline'),
      ]
    )) {
      active_campaign_api_integration_msg()->addMessage("Stage created successfully");
    }
    else {
      active_campaign_api_integration_msg()->addError("Failed to create stage");
    }
  }

}
