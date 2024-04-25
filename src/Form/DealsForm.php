<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @file
 * The file contains DealsForm class.
 */

/**
 * Class DealsForm handles deals.
 */
class DealsForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return "active_campaign_deals_form";
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $api = new ApiCaller();

    // Making calls.
    $users = $api->users();
    $pipelines = $api->pipeLines();
    $stages = $api->getStages();

    if (empty($pipelines)) {
      active_campaign_api_integration_msg()->addWarning("You dont have
       pipeline on ActiveCampaign to create Deal. Use this form to create
        pipeline.");
      $url = Url::fromRoute('active_campaign_api_integration.create_pipeline')->toString();
      (new RedirectResponse($url))->send();
      exit;
    }
    $stageUrl = Url::fromRoute('active_campaign_api_integration.create_stage')->toString();

    $message = $this->t('Create new stage, Visit @create_link.',
      ['@create_link' => $stageUrl]);

    active_campaign_api_integration_msg()->addMessage($message);

    // Pipeline options.
    $pipelineOptions = [];
    foreach ($pipelines as $key => $pipeline) {
      $pipelineOptions[$pipeline['id']] = $pipeline['title'];
    }

    // Stages options.
    $stagesOptions = [];
    foreach ($stages as $stage) {
      $pipeline = (new ApiCaller())->getPipeline((int) $stage['group']);
      $pipelineTitle = $pipeline['title'] ?? NULL;
      $stagesOptions[$stage['id']] = $stage['title'] . ' @' . $pipelineTitle;
    }

    // Owner options.
    $usersOptions = [];
    foreach ($users as $user) {
      $usersOptions[$user['id']] = $user['firstName'] . ' - ' . $user['email'];
    }

    // Currency code.
    $currencies = StoragesHandlers::supportedCurrencies();

    // Contacts.
    $contacts = active_campaign_api_integration_request()->get('contacts', '');
    $listedContacts = "";
    $checks = [];

    if (!empty($contacts)) {
      $contacts = explode(',', $contacts);

      if (count($contacts) === 1) {
        $checks['1'] = $this->t('Create this deal for contacts selected');
      }

      if (count($contacts) > 1) {
        $checks['1'] = $this->t('Create this deal for all listed contacts individually');
        $checks['2'] = $this->t('Create one deal for all contacts listed');
      }

      if (count($contacts) <= 0) {
        $checks['3'] = $this->t('Create blank deal');
      }

      $contacts = (new ApiCaller())->getContactsByIds($contacts);
      if (gettype($contacts) === "array") {
        foreach ($contacts as $contact) {
          $listedContacts .= "<li>{$contact['email']}</li>";
        }
      }
    }

    $form['fields'] = [
      '#type' => 'fieldset',
    ];

    $form['fields']['wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('Contacts Selected'),
      '#open' => FALSE,
    ];
    $form['fields']['wrapper']['fieldset']['markup'] = [
      '#markup' => "<ul>{$listedContacts}</ul>",
    ];

    $form['fields']['deal_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deal Title'),
      '#required' => TRUE,
    ];

    $form['fields']['checkboxes'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Options'),
      '#options' => $checks,
      '#required' => TRUE,
    ];

    $form['fields']['pipeline'] = [
      '#type' => 'select',
      '#title' => $this->t('Pipeline'),
      '#options' => $pipelineOptions,
      '#required' => TRUE,
    ];

    $form['fields']['stages'] = [
      '#type' => 'select',
      '#title' => $this->t('Stages'),
      '#options' => $stagesOptions,
      '#required' => TRUE,
    ];

    $form['fields']['deal_owner'] = [
      '#type' => 'select',
      '#title' => $this->t('Deal Owner'),
      '#options' => $usersOptions,
      '#required' => TRUE,
    ];

    $form['fields']['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Deal Status'),
      '#options' => ['0' => 'Open', '1' => 'Won', '2' => 'Lost'],
      '#required' => TRUE,
      '#default_value' => ['0'],
    ];

    $form['fields']['percentage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deal Percentage'),
      '#default_value' => "50",
    ];

    $form['fields']['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => $currencies,
      '#default_value' => ['USD'],
      '#required' => TRUE,
    ];

    $form['fields']['deal_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deal Value'),
      '#required' => TRUE,
    ];

    $form['fields']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#required' => TRUE,
    ];

    $form['fields']['actions']['#type'] = 'actions';
    $form['fields']['actions']['submit'] = [
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
    $deal = [
      'title' => $form_state->getValue('deal_title'),
      'group' => $form_state->getValue('pipeline'),
      'stage' => $form_state->getValue('stages'),
      'owner' => $form_state->getValue('deal_owner'),
      'currency' => $form_state->getValue('currency'),
      'value' => $form_state->getValue('deal_value'),
      'description' => $form_state->getValue('description'),
      'status' => $form_state->getValue('status'),
      'percent' => $form_state->getValue('percentage'),
    ];
    $option = $form_state->getValue('checkboxes');
    $contacts = active_campaign_api_integration_request()->get('contacts', '');
    if (!empty($contacts)) {
      $results = (new ApiCaller())->createDeal(explode(',', $contacts), $deal, (int) $option);
      if ($results) {
        active_campaign_api_integration_msg()->addMessage("Deal created successfully");
      }
      else {
        active_campaign_api_integration_msg()->addError("Failed to create deal");
      }
      $form_state->setRedirect('active_campaign_api_integration.contacts');
    }
    else {
      active_campaign_api_integration_msg()->addError("Something went wrong!");
    }
  }

}
