<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * The file contains UpdateContactListForm class.
 */

/**
 * Class UpdateContactListForm handles ContactsList updates.
 */
class UpdateContactListForm extends FormBase {

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return "active_campaign_update_contact_list";
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Bring in Lists from activeCampaign.
    $lists = (new ApiCaller())->activeCampaignLists();

    $contacts = active_campaign_api_integration_request()->get('contacts', '');
    $listedContacts = "";

    if (!empty($contacts)) {
      $contacts = explode(',', $contacts);
      $contacts = (new ApiCaller())->getContactsByIds($contacts);
      if (gettype($contacts) === "array") {
        foreach ($contacts as $contact) {
          $listedContacts .= "<li>{$contact['email']}</li>";
        }
      }
    }

    // Options for list field.
    $options = [];
    foreach ($lists as $item) {
      $options[$item['id']] = $item['name'];
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

    $form['fields']['list_on_active_campaign'] = [
      '#type' => 'select',
      '#title' => $this->t('List On ActiveCampaign'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['fields']['status_on_active_campaign'] = [
      '#type' => 'select',
      '#title' => $this->t('Status on ActiveCampaign'),
      '#options' => ['1' => 'Subscribed', '2' => 'Unsubscribed'],
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
    $contacts = active_campaign_api_integration_request()->get("contacts", "");
    $listID = $form_state->getValue('list_on_active_campaign');
    $status = $form_state->getValue('status_on_active_campaign');
    $result = (new ApiCaller())->assignList(explode(',', $contacts), (int) $listID, (int) $status);
    $total = count(explode(',', $contacts));
    if ($result) {
      active_campaign_api_integration_msg()->addMessage("Contacts ($total) updated successfully.");
    }
    else {
      active_campaign_api_integration_msg()->addError("Contacts list updating failed");
    }
    $form_state->setRedirect('active_campaign_api_integration.contacts');
  }

}
