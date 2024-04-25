<?php

namespace Drupal\active_campaign_api_integration\Controller;

use Drupal\active_campaign_api_integration\Form\CreateListForm;
use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @file
 * File contains ActiveCampaignLists class.
 */

/**
 * ActiveCampaignLists handles Lists.
 */
class ActiveCampaignLists extends ControllerBase {

  /**
   * List listing handle.
   *
   * @return array
   *   Return render array containing form and table.
   */
  public function listing(): array {
    $list = (new ApiCaller())->activeCampaignLists();
    $form = $this->formBuilder()->getForm(CreateListForm::class);
    $table = $this->listsTable($list);
    return [
      'form' => $form,
      'table' => [
        '#markup' => $table,
        '#attached' => [
          'library' => [
            'active_campaign_api_integration/manager_assets',
          ],
        ],
      ],
    ];
  }

  /**
   * Buidling list table.
   *
   * @param array $list
   *   List data to display in table.
   *
   * @return string
   *   Returns table html.
   */
  private function listsTable(array $list):string {
    $base = active_campaign_api_integration_request()->getBasePath();
    // Initialize a new array to store 'id' values.
    $idArray = [];
    $default = [];

    // Loop through the main array and extract 'id' values.
    foreach ($list as $key => $subArray) {
      if (isset($subArray['id'])) {
        $idArray[] = $subArray['id'];
      }
      $id = (int) $subArray['id'];
      $isDefault = (new StoragesHandlers(active_campaign_api_integration_db()))->isDefaultList($id);
      $list[$key]['is_default_list'] = $isDefault;
      if (!empty($isDefault)) {
        $default = ['id' => $id, 'name' => $subArray['name']];
      }
    }
    $renderArray = [
      '#theme' => 'listing_campaign_activecampaign',
      '#title' => 'ActiveCampaign Lists',
      '#content' => [
        'lists' => $list,
        'base' => $base,
        'data' => implode(',', $idArray),
        'default' => $default,
      ],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];
    // Get the HTML output.
    return active_campaign_api_integration_renderer()->renderRoot($renderArray);
  }

  /**
   * Displays and handles defaults list.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return array
   *   Return render array.
   *
   * @throws \Exception
   */
  public function defaultConfiguration(Request $request): array {

    $base = active_campaign_api_integration_request()->getBasePath();
    $list = $request->get('list', NULL);
    $action = $request->get('action', '');

    if ($action === "enable" || $action === "disable") {
      $db = active_campaign_api_integration_db();
      $fields = active_campaign_api_integration_schemas('campaign_list_defaults');
      if (empty($fields)) {
        $schema = $this->schema()['campaign_list_defaults'];
        $db->schema()->createTable('campaign_list_defaults', $schema);
      }

      $fields = active_campaign_api_integration_schemas('campaign_list_defaults');
      if (!empty($fields)) {
        $results = (new StoragesHandlers($db))->listDefaulting($list, $action);
        if ($results) {
          active_campaign_api_integration_msg()->addMessage("Your have successfully $action");
        }
        else {
          active_campaign_api_integration_msg()->addError("Failed $action");
        }
      }
    }

    $listData = (new ApiCaller())->getLists([$list]);
    $contactsOnList = (new ApiCaller())->getContactsByLists([$list]);
    return [
      '#theme' => 'campaign_contacts_list_info',
      '#title' => 'ActiveCampaign List',
      '#content' => ['contacts' => $contactsOnList, 'list' => $listData[0], 'base' => $base],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];
  }

  /**
   * Get schema of campaign_list_defaults.
   *
   * @return array
   *   Returns schema of campaign_list_defaults.
   */
  private function schema():array {
    $schema['campaign_list_defaults'] = [
      'description' => 'Table for campaign default used list',
      'fields' => [
        'id' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'description' => 'Primary Key: Unique identifier for each contact.',
        ],
        'enabled_status' => [
          'type' => 'varchar',
          'length' => 10,
          'not null' => TRUE,
          'description' => 'Campaign list enabled status',
        ],
        'list_id' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'null' => TRUE,
          'description' => 'Campaign list id.',
        ],
      ],
      'primary key' => ['id'],
    ];
    return $schema;
  }

  /**
   * Unsubscribing handle.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request an object.
   *
   * @return array
   *   returns render an array.
   */
  public function unsubscribingContacts(Request $request):array {

    $contacts = explode(',', $request->get('contacts', ''));
    $list = $request->get('list');
    if (!empty($contacts)) {
      $contacts = (new ApiCaller())->getContactsByIds($contacts);
    }

    if (!str_contains($request->getRequestUri(), 'action')) {
      $delete = $request->getRequestUri() . "?action=unsubscribe";
      $cancel = $request->getRequestUri() . "?action=cancel";
    }

    $action = $request->get('action', NULL);
    $contacts = explode(',', $request->get('contacts', ''));
    $redirect = Url::fromRoute('active_campaign_api_integration.default_list',
      ['list' => $list])->toString();

    if (empty($action)) {
      return [
        '#theme' => 'confirmation_campaign_activecampaign',
        '#title' => 'ActiveCampaign Unsubscribing Contacts',
        '#content' => [
          'contacts' => $contacts,
          'name' => 'Unsubscribe these Contact (s)',
          'delete' => $delete,
          'cancel' => $cancel,
        ],
        '#attached' => [
          'library' => [
            'active_campaign_api_integration/manager_assets',
          ],
        ],
      ];
    }
    $total = count($contacts);

    if ($action === "unsubscribe") {
      if ((new ApiCaller())->assignList($contacts, (int) $list, 2)) {
        active_campaign_api_integration_msg()->addMessage("You have successfully unsubscribe
        contacts ($total) from this list");
      }
      else {
        active_campaign_api_integration_msg()->addError("Failed to unsubscribe contacts ($total)");
      }
    }
    (new RedirectResponse($redirect))->send();
    exit;
  }

}
