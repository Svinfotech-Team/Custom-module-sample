<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * File contains CreateListForm.
 */

/**
 * Class CreateListForm build, validate, submit CreateListForm.
 */
class CreateListForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return "campaign_list_creation";
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $groups = (new ApiCaller())->groups();
    $users = (new ApiCaller())->users();
    $owners = [];
    $notify = [];
    $groupOptions = [];

    foreach ($users as $user) {
      $owners[$user['id']] = $user['firstName'] . ' - ' . $user['email'];
      $notify[$user['email']] = $user['firstName'] . ' - ' . $user['email'];
    }

    foreach ($groups as $group) {
      $groupOptions[$group['id']] = $group['title'];
    }

    $form['fields'] = [
      '#type' => 'fieldset',
    ];
    $form['fields']['wrapper'] = [
      '#type' => 'details',
      '#title' => $this->t('List Creation Form'),
      '#open' => FALSE,
    ];
    $form['fields']['wrapper']['name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
    ];
    $form['fields']['wrapper']['send_last_broadcast'] = [
      '#type' => 'select',
      '#title' => $this->t('Send Last BroadCast'),
      '#options' => ['1' => 'Yes', '0' => 'No'],
      '#required' => TRUE,
      '#default_value' => '1',
      '#description' => $this->t('Whether or not to send the last sent campaign to this list to a new subscriber upon subscribing'),
    ];
    $form['fields']['wrapper']['user_owner'] = [
      '#type' => 'select',
      '#title' => $this->t('User ID'),
      '#options' => $owners,
      '#required' => TRUE,
      '#description' => $this->t('A list owner is able to control campaign branding. A property of list.userid also exists on this object; both properties map to the same list owner field and are being maintained in the response object for backward compatibility.'),
    ];
    $form['fields']['wrapper']['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Group ID'),
      '#options' => $groupOptions,
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#description' => $this->t('Which group you want this List to be visible to.'),
    ];
    $form['fields']['wrapper']['subscription_notify'] = [
      '#type' => 'select',
      '#title' => $this->t('Subscription Notification'),
      '#options' => $notify,
      '#multiple' => TRUE,
      '#description' => $this->t('Select Email addresses to notify when a new subscriber joins this list.'),
    ];
    $form['fields']['wrapper']['unsubscription_notify'] = [
      '#type' => 'select',
      '#title' => $this->t('Unsubscription Notification'),
      '#options' => $notify,
      '#multiple' => TRUE,
      '#description' => $this->t('Email addresses selected above to be notify when a subscriber unsubscribes from this list'),
    ];
    $form['fields']['wrapper']['carboncopy'] = [
      '#type' => 'select',
      '#title' => $this->t('Who to receive all Email Upon send'),
      '#options' => $notify,
      '#multiple' => TRUE,
      '#description' => $this->t('Email addresses to send a copy of all mailings to upon send'),
    ];

    $form['fields']['wrapper']['sender_url'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Website URL'),
      '#description' => $this->t('The website URL this list is for.'),
      '#default_value' => active_campaign_api_integration_request()->getSchemeAndHttpHost(),
    ];
    $form['fields']['wrapper']['sender_reminder'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Reminder Message'),
      '#required' => TRUE,
      '#description' => $this->t('A reminder for your contacts as to why they are on this list and you are messaging them'),
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

    $newList = [
      'send_last_broadcast' => !empty((int) $form_state->getValue('send_last_broadcast')),
      'name' => $form_state->getValue('name'),
      'stringid' => strtolower(str_replace(' ', '-', $form_state->getValue('name'))),
      'sender_url' => $form_state->getValue('sender_url'),
      'sender_reminder' => $form_state->getValue('sender_reminder'),
      'carboncopy' => implode(',', array_filter(array_filter($form_state->getValue('carboncopy'), 'strlen'), 'trim')),
      'subscription_notify' => implode(',', array_filter(array_filter($form_state->getValue('subscription_notify'), 'strlen'), 'trim')),
      'unsubscription_notify' => implode(',', array_filter(array_filter($form_state->getValue('unsubscription_notify'), 'strlen'), 'trim')),
      'user' => (int) $form_state->getValue('user_owner'),
      'group' => $form_state->getValue('group'),
    ];
    if ((new ApiCaller())->createList($newList)) {
      active_campaign_api_integration_msg()->addMessage("New List with name {$newList['name']} created successfully");
    }
    else {
      active_campaign_api_integration_msg()->addError("Failed to create new List");
    }
  }

}
