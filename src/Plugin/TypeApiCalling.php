<?php

namespace Drupal\active_campaign_api_integration\Plugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Files that handle some api calls.
 */

/**
 * TypeApiCalling handles api calling to all types.
 *
 * @class Class to handle api calling to ActiveCampaign.
 */
class TypeApiCalling {

  /**
   * All tokens of api url,key.
   *
   * @var array Api tokens
   */
  private array $tokens;

  /**
   * Construct.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Connection.
   */
  public function __construct(private readonly Connection $database) {
    $storage = new StoragesHandlers($this->database);
    $this->tokens = $storage->getCampaignKeys();
  }

  /**
   * For handling a Contact Sync type.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Formstate submitted.
   *
   * @return void
   *   Nothing is returned.
   */
  public function contactSyncHandler(FormStateInterface $formState): void {

    // Get form id.
    $drupalForm = $formState->getFormObject()->getFormId();
    $storages = new StoragesHandlers($this->database);
    $formID = $storages->getIdByFormId($drupalForm);

    if (empty($formID) || !is_numeric($formID)) {
      active_campaign_api_integration_logger()->error("Failed to acquire form
       ID in campaign_forms_added form drupal FORM $drupalForm");
      return;
    }

    // Getting mapping data.
    $maps = $storages->mapping(intval($formID));
    if (empty($maps)) {
      active_campaign_api_integration_logger()->error("Failed to acquire
      mapping data form $formID in campaign_mapping");
      return;
    }

    // Build up an array with ActiveCampaign field.
    $mapping = $maps[0]['map'] ?? [];
    $postArray = [];

    foreach ($mapping as $key => $item) {
      $postArray = array_merge($postArray, $this->fieldMapping(
        $key,
        $item,
        $formState
      ));
    }
    if (empty($postArray)) {
      active_campaign_api_integration_logger()->error("Mapping failed
       for $formID");
    }
    $status = ["status[{$maps[0]['list']}]" => 1];
    $list = ["p[{$maps[0]['list']}]" => $maps[0]['list']];
    $postArray = array_merge($postArray, $status, $list);
    $this->curlCall($postArray, $maps[0]['type']);
  }

  /**
   * To be used to map the field.
   *
   * @param string $formField
   *   Your drupal form machine name.
   * @param array $activeField
   *   ActiveCampaign item.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Formstate.
   *
   * @return array|string[]
   *   Array of field full mapped.
   */
  private function fieldMapping(string $formField,
    array $activeField,
  FormStateInterface $formState): array {
    if ($activeField['ac_type'] === "Text Input") {
      $data = $formState->getValue($formField) ?? "";
      $field = strtolower($activeField['ac_field']);
      if ($field === "email") {
        return ["email" => $this->parseData($data, $activeField['ac_type'])];
      }
      elseif ($field === "first_name" || $field === "firstname") {
        return [
          "first_name" =>
          $this->parseData($data, $activeField['ac_type']),
        ];
      }
      elseif ($field === "last_name" || $field === "lastname") {
        return [
          "last_name" =>
          $this->parseData($data, $activeField['ac_type']),
        ];
      }
      elseif ($field === "phone") {
        return ["phone" => $this->parseData($data, $activeField['ac_type'])];
      }
      elseif ($field === "customer_acct_name" || $field === "acct_name") {
        return [
          "customer_acct_name" =>
          $this->parseData($data, $activeField['ac_type']),
        ];
      }
      return [
        "field[%{$activeField['ac_field']}%,0]" =>
        $this->parseData($data, $activeField['ac_type']),
      ];
    }
    return [];
  }

  /**
   * Parse data based on a field type in ActiveCampaign.
   *
   * @param mixed $data
   *   Simple data parsing.
   * @param string $type
   *   Type of field in ActiveCampaign.
   *
   * @return mixed
   *   returned Mapped value.
   */
  private function parseData(mixed $data, string $type = "Text Input"): mixed {
    if ($type === "Text Input") {
      if (gettype($data) === "string") {
        return $data;
      }

      if (gettype($data) === "array") {
        return $data[0]['value'] ?? NULL;
      }
    }
    // @todo continue with other types.
    return $data;
  }

  /**
   * This is applicable for calling api.
   *
   * @param array $post
   *   Data to be posted.
   * @param string $type
   *   Type of api_action eg contact_sync.
   *
   * @return void
   *   Nothing is returned
   */
  public function curlCall(array $post, string $type): void {
    $storages = new StoragesHandlers($this->database);
    $apiTokens = $storages->getCampaignKeys();

    if (empty($apiTokens)) {
      active_campaign_api_integration_logger()->error("ActiveCampaign Keys
       not found");
      return;
    }
    $url = $apiTokens[0]['url'];
    $token = $apiTokens[0]['key'];
    $params = [
      'api_action'   => $type,
      'api_output'   => 'json',
    ];
    $query = "";
    foreach ($params as $key => $value) {
      $query .= urlencode($key) . '=' . urlencode($value) . '&';
    }
    $query = rtrim($query, '& ');
    $data = "";
    foreach ($post as $key => $value) {
      $data .= urlencode($key) . '=' . urlencode($value) . '&';
    }
    $data = rtrim($data, '& ');
    $url = rtrim($url, '/ ');
    $api = $url . '/admin/api.php?' . $query;
    $request = curl_init($api);
    // Provide the API Token via the API-TOKEN header.
    curl_setopt($request, CURLOPT_HTTPHEADER, ['API-TOKEN: ' . $token]);
    // Set to 0 to eliminate header info from response.
    curl_setopt($request, CURLOPT_HEADER, 0);
    // Returns response data instead of TRUE(1)
    curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
    // Use HTTP POST to send form data.
    curl_setopt($request, CURLOPT_POSTFIELDS, $data);
    curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);

    // Execute curl post and store results in $response.
    $response = (string) curl_exec($request);
    curl_close($request);
    if (!empty($response)) {
      $result = json_decode($response, TRUE);
      active_campaign_api_integration_logger()->notice($result['result_message']);
    }
  }

  /**
   * Contacts.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   This formstate.
   *
   * @return void
   *   Nothing returned
   */
  public function contactAddHandler(FormStateInterface $form_state): void {
    $this->contactSyncHandler($form_state);
  }

  /**
   * Sending contact via contact Sync.
   *
   * @param array $userInformation
   *   User info.
   *
   * @return bool
   *   return true if submitted.
   */
  public function sendingUserContact(array $userInformation): bool {

    if (!empty($userInformation)) {
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/contact/sync",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          'contact' => $userInformation,
        ]),
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
          "content-type: application/json",
        ],
      ]);

      $response = curl_exec($curl);
      curl_close($curl);
      $contact = json_decode($response, TRUE)['contact']['id'] ?? NULL;
      if (!empty($contact)) {
        return $this->assignListToContact((int) $contact);
      }
      return !empty($response);
    }
    return FALSE;
  }

  /**
   * Updating list of new Contact.
   *
   * @param int $contactID
   *   Just created contact id.
   * @param int $listID
   *   List id to belong to.
   *
   * @return bool
   *   True if submitted.
   */
  public function updateContactList(int $contactID, int $listID): bool {
    if (!empty($contactID) && !empty($listID)) {
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/contactLists",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          'contactList' => [
            'sourceid' => 0,
            'list' => "$listID",
            'contact' => "$contactID",
            'status' => '1',
          ],
        ]),
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
          "content-type: application/json",
        ],
      ]);
      $err = curl_error($curl);
      curl_close($curl);
      if ($err) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Assign contact to a defaulted list.
   *
   * @param int $contact
   *   Contact id.
   *
   * @return bool
   *   True if a process is done correctly.
   */
  public function assignListToContact(int $contact): bool {
    $field = active_campaign_api_integration_schemas('campaign_list_defaults');
    if (!empty($field)) {
      $query = active_campaign_api_integration_db()->select("campaign_list_defaults", 'c');
      $query->addField('c', 'list_id', 'id');
      $query->condition('c.enabled_status', '1', '=');
      $query = $query->execute();
      $data = $query->fetchAll(\PDO::FETCH_ASSOC);
      if (!empty($data[0]['id'])) {
        return (new ApiCaller())->assignList([$contact], (int) $data[0]['id']);
      }
    }
    return FALSE;
  }

}
