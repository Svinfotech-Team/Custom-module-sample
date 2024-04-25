<?php

namespace Drupal\active_campaign_api_integration\Plugin;

use Drupal\Core\Database\Connection;

/**
 * @file
 * File that contain class that handlers all database functions.
 */

/**
 * All functionality of data saving.
 *
 * @class The class that handlers all database activities for campaign module.
 */
class StoragesHandlers {

  /**
   * Database Connection required.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Connection.
   */
  public function __construct(private readonly Connection $database) {}

  /**
   * Saves the api keys of ActiveCampaign api.
   *
   * @param string $url
   *   Url of your ActiveCampaign Account.
   * @param string $apiKey
   *   Api key of your ActiveCampaign Account.
   *
   * @return bool
   *   True if saved or false if fails.
   *
   * @throws \Exception
   */
  public function saveApiKeys(string $url, string $apiKey): bool {
    if (!empty($url) && !empty($apiKey)) {
      $old = $this->getCampaignKeys();
      if (!empty($old)) {
        $id = $old[0]['id'] ?? NULL;
        if (!is_null($id)) {
          return $this->updateActiveCampaignKeys(
            [
              'url' => $url,
              'api_key' => $apiKey,
            ],
            intval($id),
            "active_campaign_settings"
          );
        }
      }
      else {
        return $this->addActiveCampaignKeys(
          [
            'url' => $url,
            'api_key' => $apiKey,
          ],
          "active_campaign_settings"
              );
      }
    }
    return FALSE;
  }

  /**
   * Returns the keys of ActiveCampaign if exist else an empty array.
   *
   * @return array
   *   Array of key - url and — token.
   */
  public function getCampaignKeys(): array {
    $query = $this->database->select("active_campaign_settings", 'a');
    $query->addField("a", "id", "id");
    $query->addField("a", "api_key", "key");
    $query->addField("a", "url", "url");
    $data = $query->execute();
    $data = $data->fetchAll();

    $returns = [];
    if (!empty($data)) {
      $returns = array_fill(0, count($data), [
        'id' => NULL,
        'url' => NULL,
        'key' => NULL,
      ]);
      foreach ($data as $key => $value) {
        $returns[$key] = [
          'id' => $value->id,
          'url' => $value->url,
          'key' => $value->key,
        ];
      }
    }
    return $returns;
  }

  /**
   * Update ActiveCampaign Keys.
   *
   * @param array $data
   *   New data to update with.
   * @param int $id
   *   ID of ActiveCampaign row.
   * @param string $table
   *   Where to update info.
   *
   * @return bool
   *   True if updated or false if fails.
   */
  private function updateActiveCampaignKeys(array $data, int $id, string $table): bool {
    if (!empty($data)) {
      $query = $this->database->update($table);
      $query->fields($data);
      $query->condition("id", $id, "=");
      return !empty($query->execute());
    }
    return FALSE;
  }

  /**
   * To Added new active campaign account keys.
   *
   * @param array $data
   *   New ActiveCampaign keys.
   * @param string $table
   *   Where to add info.
   *
   * @return bool
   *   True if added or false if fails.
   *
   * @throws \Exception
   */
  private function addActiveCampaignKeys(array $data, string $table): bool {
    $query = $this->database->insert($table);
    $query->fields($data);
    return !empty($query->execute());
  }

  /**
   * Collect all submitted forms id.
   *
   * @return array
   *   Array of an object of ids and form ids or empty if no result.
   */
  public function formsAdded(): array {
    $query = $this->database->select("campaign_forms_added", "f");
    $query->addField("f", "form_id", "form");
    $query->addField("f", "id", "id");
    $query->addField("f", "form_label", "label");
    $data = $query->execute();
    $data = $data->fetchAll();
    $returns = [];
    if (!empty($data)) {
      $returns = array_fill(0, count($data), [
        'id' => NULL,
        'form' => NULL,
      ]);

      foreach ($data as $key => $value) {
        $returns[$key] = [
          'id' => $value->id,
          'form' => $value->form,
          'label' => $value->label,
        ];
      }
    }
    return $returns;
  }

  /**
   * By using form id to get data row id.
   *
   * @param string $form_id
   *   This active form id.
   *
   * @return int|null
   *   return id of form id database.
   */
  public function getIdByFormId(string $form_id): int|null {
    $query = $this->database->select("campaign_forms_added", "f");
    $query->addField("f", "id", "id");
    $query->condition("f.form_id", $form_id, "=");
    $data = $query->execute();
    $data = $data->fetchAll();
    if (!empty($data)) {
      return $data[0]->id ?? NULL;
    }
    return NULL;
  }

  /**
   * To save submitted form for mapping later.
   *
   * @param string $formId
   *   Submitted form id.
   * @param string $formLabel
   *   Form label.
   *
   * @return bool
   *   True if saved false is not.
   *
   * @throws \Exception
   */
  public function saveFormId(string $formId, string $formLabel): bool {
    $old = $this->formsAdded();
    if (!empty($old)) {
      foreach ($old as $value) {
        if (!empty($value['form']) && $value['form'] === $formId) {
          return $this->updateActiveCampaignKeys(
            ['form_id' => $formId, 'form_label' => $formLabel],
            intval($value['id']),
            "campaign_forms_added"
            );
        }
      }
    }
    return $this->addActiveCampaignKeys(
      [
        "form_id" => $formId,
        'form_label' => $formLabel,
      ],
      "campaign_forms_added"
    );
  }

  /**
   * Arg is form id from mapping table.
   *
   * @param int $form_id
   *   Form id in mapping data table.
   *
   * @return array
   *   Returns array of map data to be used.
   */
  public function mapping(int $form_id): array {
    $query = $this->database->select("campaign_mapping", "m");
    $query->addField("m", "mapping", "fields");
    $query->condition("m.form_id", $form_id, "=");
    $data = $query->execute();
    $data = $data->fetchAll();
    $returns = [];
    if (!empty($data)) {
      $returns = array_fill(0, count($data), [
        'list' => NULL,
        'type' => NULL,
        'map' => NULL,
      ]);
      foreach ($data as $key => $value) {
        $data = json_decode($value->fields, TRUE);
        $returns[$key] = [
          'list' => $data['list_id'] ?? NULL,
          'type' => $data['type'] ?? NULL,
          'map' => $data['mapping'] ?? NULL,
        ];
      }
    }
    return $returns;
  }

  /**
   * Saving mapping data.
   *
   * @param array $data
   *   Mapping data consist of list_id, type and mapping keys.
   * @param int $form_id
   *   Form id from forms_data_table.
   *
   * @return bool
   *   True if saved false is not.
   *
   * @throws \Exception
   */
  public function saveMapping(array $data, int $form_id): bool {
    $old = $this->mapping($form_id);
    if (!empty($old)) {
      return $this->updateActiveCampaignKeys(
        [
          'mapping' => json_encode($data, JSON_PRETTY_PRINT),
        ],
        $form_id,
        'campaign_mapping'
      );
    }
    else {
      return $this->addActiveCampaignKeys(
        [
          'form_id' => $form_id,
          'mapping' => json_encode($data, JSON_PRETTY_PRINT),
        ],
        'campaign_mapping'
          );
    }
  }

  /**
   * Saving contact created.
   *
   * @param array $data
   *   Data consist of active_id of created contact and node id.
   *
   * @return bool
   *   True if saved.
   *
   * @throws \Exception
   */
  public function saveContact(array $data): bool {
    $old = $this->getContacts($data['active_id']);
    if (!empty($old)) {
      return $this->database->update("campaign_contacts")
        ->fields($data)
        ->condition("contact_id", $old['id'], "=")
        ->execute();
    }
    return $this->database->insert("campaign_contacts")
      ->fields($data)
      ->execute();
  }

  /**
   * Collect Contacts array.
   *
   * @param int $contact_id
   *   contact id as of ActiveCampaign else leave blank to
   *   get All.
   *
   * @return array
   *   Array of Contacts or just one if id provided.
   */
  public function getContacts(int $contact_id = -1): array {
    $query = $this->database->select("campaign_contacts", "cc");
    $query->addField("cc", "contact_id", "id");
    $query->addField("cc", "active_id", "contact_id");
    $query->addField("cc", "firstname", "firstname");
    $query->addField("cc", "email", "email");

    if ($contact_id > 0) {
      $query->condition("cc.active_id", $contact_id, "=");
    }
    $data = $query->execute();
    $data = $data->fetchAll();
    $returns = [];
    if (!empty($data)) {
      foreach ($data as $value) {
        $returns[] = [
          'id' => $value->id ?? NULL,
          'contact_id' => $value->contact_id ?? NULL,
          'node' => $value->node ?? NULL,
        ];
      }
    }
    return $returns;
  }

  /**
   * Saving User registration form mappings.
   *
   * @param array $map
   *   User registration form map.
   *
   * @return bool
   *   True is saved.
   *
   * @throws \Exception
   */
  public function saveRegistrationMapping(array &$map) : bool {
    $data = $this->userMappingconfiguration();
    $query = $this->database->insert("campaign_users_map");
    if (empty($data[0]->id)) {
      $map['active'] = "yes";
    }
    $query->fields($map);
    return !empty($query->execute());
  }

  /**
   * Return Mapping for user registration form.
   *
   * @param bool $ll
   *   True indicate that select all.
   *
   * @return mixed
   *   StdClass of data.
   */
  public function userMappingconfiguration(bool $ll = TRUE): mixed {
    $query = $this->database->select("campaign_users_map", "m");
    $query->addField("m", "id", "id");
    $query->addField("m", "map", "mapping");
    $query->addField("m", "name", "name");
    $query->addField("m", "list", "list");
    $query->addField("m", "active", "active");
    if ($ll === FALSE) {
      $query->condition("m.active", "yes", "=");
    }
    $result = $query->execute();
    return $result->fetchAll();
  }

  /**
   * Update mapping active col.
   *
   * @param int $id
   *   Row id to update.
   * @param string $action
   *   Action string ie no or yes.
   *
   * @return bool
   *   True if updated.
   */
  public function updateUserMapping(int $id, string $action): bool {
    $old = $this->userMappingconfiguration();
    if (!empty($old[0]->id) && $action === "yes") {
      $query = $this->database->select("campaign_users_map", 'c');
      $query->addField("c", "id", "id");
      $query = $query->execute();
      $data = $query->fetchAll(\PDO::FETCH_ASSOC);
      unset($query);

      if (!empty($data)) {
        foreach ($data as $value) {
          $query = $this->database->update("campaign_users_map");
          $query->fields(['active' => 'no']);
          $query->condition("id", $value['id'], '=');
          $query->execute();
          unset($query);
        }
      }
    }

    $query = $this->database->update("campaign_users_map");
    $query->condition("id", $id, "=");
    $query->fields(["active" => $action]);
    return !empty($query->execute());
  }

  /**
   * Collect all supported currencies on activeCampaign.
   *
   * @return array
   *   List of all supported currencies on ActiveCampaign.
   */
  public static function supportedCurrencies():array {
    $values = [
      "AED", "CAD", "HKD", "MAD", "QAR", "UAH",
      "AFN", "CDF", "HNL", "MDL", "RON", "UGX",
      "ALL", "CHF", "HRK", "MGA", "RSD", "USD",
      "AMD", "CLP", "HTG", "MKD", "RUB", "UYU",
      "ANG", "CNY", "HUF", "MMK", "RWF", "UZS",
      "AOA", "COP", "IDR", "MNT", "SAR", "VED",
      "ARS", "CRC", "ILS", "MOP", "SBD", "VES",
      "AUD", "CUP", "INR", "MRU", "SCR", "VND",
      "AWG", "CVE", "IQD", "MUR", "SDG", "VUV",
      "AZN", "CZK", "IRR", "MVR", "SEK", "WST",
      "BAM", "DJF", "ISK", "MWK", "SGD", "XAF",
      "BBD", "DKK", "JMD", "MXN", "SHP", "XCD",
      "BDT", "DOP", "JOD", "MYR", "SLE", "XOF",
      "BGN", "DOP", "JPY", "MZN", "SOS", "XPF",
      "BHD", "DZD", "KES", "NAD", "SRD", "YER",
      "BIF", "EGP", "KGS", "NGN", "SSP", "ZAR",
      "BMD", "ERN", "KHR", "NIO", "STN", "ZMW",
      "BND", "ETB", "KMF", "NOK", "SYP",
      "BOB", "EUR", "KPW", "NPR", "SZL",
      "BRL", "FJD", "KRW", "NZD", "THB",
      "BSD", "FKP", "KWD", "OMR", "TJS",
      "BTN", "GBP", "KYD", "PAB", "TMT",
      "BWP", "GBP", "KZT", "PEN", "TND",
      "BYN", "GEL", "LAK", "PGK", "TOP",
      "BZD", "GHS", "LBP", "PHP", "TRY",
      "CAD", "GIP", "LKR", "PKR", "TTD",
      "CDF", "GMD", "LRD", "PLN", "TWD",
    ];
    $values = array_map('trim', array_filter($values, 'strlen'));
    sort($values);
    $final = [];
    foreach ($values as $value) {
      $final[$value] = $value;
    }
    return $final;
  }

  /**
   * Handles defaulting of list.
   *
   * @param mixed $list
   *   List id.
   * @param string $action
   *   Action of defaults.
   *
   * @return bool
   *   True is defaulting action happened with errors.
   *
   * @throws \Exception
   */
  public function listDefaulting(mixed $list, string $action): bool {

    $query = $this->database->select('campaign_list_defaults', 'c');
    $query->addField('c', 'list_id', 'list');
    $query->addField('c', 'id', 'id');
    $query = $query->execute();
    $data = $query->fetchAll(\PDO::FETCH_ASSOC);
    $flag = FALSE;
    foreach ($data as $value) {
      $query = $this->database->update('campaign_list_defaults');
      $query->fields(['enabled_status' => '0']);
      $query->condition("id", $value['id'], '=');
      $query->execute();
      unset($query);
      if ((int) $value['list'] === (int) $list) {
        $flag = TRUE;
      }
    }

    if ($action === 'enable') {
      if ($flag) {
        $query = $this->database->update("campaign_list_defaults");
        $query->fields(['enabled_status' => '1']);
        $query->condition('list_id', $list, '=');
        return !empty($query->execute());
      }
      $query = $this->database->insert('campaign_list_defaults');
      $query->fields(['list_id', 'enabled_status'], [$list, '1']);
      return !empty($query->execute());
    }
    return $flag;
  }

  /**
   * Checking if list id given is default for contacts from this system.
   *
   * @param int $list
   *   List id.
   *
   * @return bool
   *   True if a list is defaulted.
   */
  public function isDefaultList(int $list):bool {
    $fields = active_campaign_api_integration_schemas('campaign_list_defaults');
    if (!empty($fields)) {
      $query = $this->database->select('campaign_list_defaults', 'c');
      $query->addField('c', 'id', 'id');
      $query->condition('c.enabled_status', '1', '=');
      $query->condition('c.list_id', $list, '=');
      $query = $query->execute();
      $data = $query->fetchAll(\PDO::FETCH_ASSOC);
      return !empty($data[0]['id']);
    }
    return FALSE;
  }

}
