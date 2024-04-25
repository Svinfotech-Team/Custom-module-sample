<?php

namespace Drupal\active_campaign_api_integration\Plugin;

/**
 * @file
 * Api v3 api calls file.
 */

/**
 * All v3 api callings go through this class.
 *
 * @class Handles all v3 api of ActiveCampaign.
 */
class ApiCaller {

  /**
   * Token variable.
   *
   * @var array
   */
  private array $tokens;

  /**
   * Get tokens.
   *
   * @return array
   *   Token array.
   */
  public function getTokens(): array {
    return $this->tokens;
  }

  /**
   * Construct.
   */
  public function __construct() {
    $storage = new StoragesHandlers(active_campaign_api_integration_db());
    $this->tokens = $storage->getCampaignKeys();
  }

  /**
   * Collecting custom fields from ActiveCampaign.
   *
   * @return array
   *   Returns list of fields.
   */
  public function customFieldsList():array {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/fields?limit=100",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    if (!empty($response)) {
      $list = json_decode($response, TRUE);
      return $list['fields'] ?? [];
    }
    return [];
  }

  /**
   * Collect ActiveCampaign.
   *
   * @return array
   *   ActiveCampaign list.
   */
  public function activeCampaignLists(): array {

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/lists",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    $response = json_decode($response, TRUE);
    return $response['lists'] ?? [];
  }

  /**
   * Contacts lists.
   *
   * @param string|null $params
   *   Params line without ? at the start.
   *
   * @return array
   *   Return contacts lists.
   */
  public function activeCampaignContacts(string|null $params = NULL):array {
    $url = "{$this->tokens[0]['url']}/api/3/contacts";
    if (!empty($params)) {
      $url .= "?" . $params;
    }
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE)['contacts'] ?? [];
  }

  /**
   * Fetching ids by contacts.
   *
   * @param array $ids
   *   IDS of contact to fetch.
   *
   * @return array
   *   List of contacts
   */
  public function getContactsByIds(array $ids):array {

    // Clear up all non numerical value.
    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $ids), function ($value) {
      return $value !== NULL;
    });

    // Build up params.
    if (!empty($filteredIds)) {
      $params = count($filteredIds) === 1 ? "ids=" . $filteredIds[0] : implode("&ids[]=", $filteredIds);
      if (count($filteredIds) > 1) {
        $params = "ids[]=" . $params;
      }

      // Search contacts.
      return $this->activeCampaignContacts($params);
    }
    return [];
  }

  /**
   * Deletions methods.
   *
   * @param array $ids
   *   List of ids for contacts to delete.
   *
   * @return bool
   *   True if deleted or false if fails.
   */
  public function deleteContacts(array $ids):bool {

    // Clear up all non numerical value.
    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $ids), function ($value) {
      return $value !== NULL;
    });

    $flags = [];
    foreach ($filteredIds as $id) {
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/contacts/$id",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
        ],
      ]);
      $response = curl_exec($curl);
      curl_close($curl);
      $response = json_decode($response, TRUE);

      // Check if deleted.
      $flags[] = empty($response);
    }
    return in_array(TRUE, $flags);
  }

  /**
   * Updating contacts with a list.
   *
   * @param array $contacts
   *   Contacts IDS.
   * @param int $id
   *   List id.
   * @param int $status
   *   Subscription number 1 or 2.
   *
   * @return bool
   *   True if successfully subscribed to list.
   */
  public function assignList(array $contacts, int $id, int $status = 1): bool {

    // Clear up all non numerical value.
    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $contacts), function ($value) {
      return $value !== NULL;
    });

    $flags = [];
    if (!empty($filteredIds)) {

      foreach ($filteredIds as $contact) {
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
              'list' => $id,
              'contact' => $contact,
              'status' => $status,
            ],
          ]),
          CURLOPT_HTTPHEADER => [
            "Api-Token: {$this->tokens[0]['key']}",
            "accept: application/json",
            "content-type: application/json",
          ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, TRUE);
        if (!empty($response['contactList']['list'])) {
          $flags[] = (string) $response['contactList']['list'] === (string) $id;
        }
      }
    }

    return in_array(TRUE, $flags);
  }

  /**
   * Get User Info from ActiveCampaign.
   *
   * @param int $user
   *   ID of user on ActiveCampaign.
   *
   * @return mixed
   *   Array of user of false if fails.
   */
  public function getUser(int $user): mixed {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/users/$user",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE);
  }

  /**
   * Collect all pipelines.
   *
   * @return array
   *   List of pipelines
   */
  public function pipeLines():array {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/dealGroups?orders[title]=ASC&orders[popular]=ASC",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE)['dealGroups'] ?? [];
  }

  /**
   * Collect All users on ActiveCampaign.
   *
   * @return array
   *   Users list from ActiveCampaign.
   */
  public function users():array {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/users",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE)['users'] ?? [];
  }

  /**
   * Getting all stages from ActiveCampaign.
   *
   * @return array
   *   Stages list collection is returned.
   */
  public function getStages():array {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/dealStages",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE)['dealStages'] ?? [];
  }

  /**
   * Creating Deal for contact or contacts.
   *
   * @param array $contacts
   *   Contacts list.
   * @param array $deal
   *   Deal array.
   * @param int $option
   *   Options 1,2 whether to duplicate deal or to create one for all.
   *
   * @return bool
   *   True if created.
   */
  public function createDeal(array $contacts, array $deal, int $option): bool {

    $required = [
      'status',
      'title',
      'description',
      'value',
      'currency',
      'group',
      'stage',
      'owner',
      'percent',
    ];

    foreach ($required as $value) {
      if (!isset($deal[$value])) {
        return FALSE;
      }
    }
    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $contacts), function ($value) {
      return $value !== NULL;
    });

    if (empty($filteredIds)) {
      return FALSE;
    }

    $flags = [];
    if ($option === 1) {
      foreach ($filteredIds as $filteredId) {
        $deal['contact'] = $filteredId;
        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/deals",
          CURLOPT_RETURNTRANSFER => TRUE,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode([
            'deal' => $deal,
          ]),
          CURLOPT_HTTPHEADER => [
            "Api-Token: {$this->tokens[0]['key']}",
            "accept: application/json",
            "content-type: application/json",
          ],
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $response = json_decode($response, TRUE);
        $flags[] = !empty($response['deal']);
      }
    }

    elseif ($option === 2) {

      $deal['contact'] = $filteredIds[0];
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/deals",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          'deal' => $deal,
        ]),
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
          "content-type: application/json",
        ],
      ]);
      $response = curl_exec($curl);
      curl_close($curl);
      $dealID = json_decode($response, TRUE)['deal']['id'] ?? NULL;
      if (!empty($response)) {
        unset($filteredIds[0]);
        $flags[] = $this->secondaryContactsForDeal($filteredIds, (int) $dealID);
      }

    }

    return in_array(TRUE, $flags);
  }

  /**
   * Creating pipeline.
   *
   * @param array $pipeline
   *   Pipeline data to be created.
   *
   * @return bool
   *   True if a pipeline created.
   */
  public function createPipeline(array $pipeline):bool {
    $curl = curl_init();

    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/dealGroups",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'dealGroup' => $pipeline,
      ]),
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
        "content-type: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return !empty(json_decode($response, TRUE)['dealGroup']['id']) ?? FALSE;
  }

  /**
   * Attaching more contacts to a deal.
   *
   * @param array $contacts
   *   Contact id.
   * @param int $dealID
   *   Deal id.
   *
   * @return bool
   *   True if contact attached.
   */
  public function secondaryContactsForDeal(array $contacts, int $dealID): bool {

    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $contacts), function ($value) {
      return $value !== NULL;
    });

    if (empty($filteredIds)) {
      return FALSE;
    }

    $flags = [];
    foreach ($filteredIds as $filteredId) {
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/contactDeals",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          'contactDeal' => [
            'deal' => $dealID,
            'contact' => $filteredId,
          ],
        ]),
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
          "content-type: application/json",
        ],
      ]);
      $response = curl_exec($curl);
      curl_close($curl);
      $results = (int) json_decode($response, TRUE)['contactDeal']['id'] ?? NULL;
      $flags[] = !empty($results);
    }

    return in_array(TRUE, $flags);
  }

  /**
   * Get pipeline by id.
   *
   * @param int $pipeline
   *   Pipeline id.
   *
   * @return array
   *   Collecting a pipeline.
   */
  public function getPipeline(int $pipeline):array {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/dealGroups/$pipeline",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE)['dealGroup'] ?? [];
  }

  /**
   * Creating Stage on ActiveCampaign.
   *
   * @param array $stage
   *   Stage data to create.
   *
   * @return bool
   *   True if stage created.
   */
  public function createStage(array $stage):bool {
    $required = [
      'title',
      'group',
    ];
    foreach ($required as $value) {
      if (!isset($stage[$value])) {
        return FALSE;
      }
    }
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/dealStages",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'dealStage' => [
          'order' => NULL,
          'dealOrder' => 'next-action DESC',
          'cardRegion1' => 'title',
          'cardRegion2' => 'next-action',
          'cardRegion3' => 'show-avatar',
          'cardRegion4' => 'contact-fullname-orgname',
          'cardRegion5' => 'value',
          'color' => '3f3f3f',
          'width' => 280,
          'group' => $stage['group'],
          'title' => $stage['title'],
        ],
      ]),
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
        "content-type: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return !empty(json_decode($response, TRUE)['dealStage']['id']);
  }

  /**
   * Creating a list on ActiveCampaign.
   *
   * @param array $list
   *   List data of a list.
   *
   * @return bool
   *   True if a list is created.
   */
  public function createList(array $list):bool {
    $required = [
      'name',
      'stringid',
      'sender_url',
      'sender_reminder',
      'group',
    ];
    foreach ($required as $value) {
      if (!isset($list[$value])) {
        return FALSE;
      }
    }

    $group = $list['group'];
    unset($list['group']);

    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/lists",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => json_encode([
        'list' => $list,
      ]),
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
        "content-type: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    $id = json_decode($response, TRUE)['list']['id'] ?? NULL;
    if (!empty($id)) {
      return $this->createGroupPermissions($id, $group);
    }
    return FALSE;
  }

  /**
   * Lists Group permissions of users.
   *
   * @return array
   *   Lists of groups.
   */
  public function groups():array {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/groups",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE)['groups'] ?? [];
  }

  /**
   * Creating permission for user to manage list.
   *
   * @param int $listID
   *   List id.
   * @param array $groups
   *   Group id.
   *
   * @return bool
   *   True if permission is created.
   */
  public function createGroupPermissions(int $listID, array $groups): bool {

    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $groups), function ($value) {
      return $value !== NULL;
    });
    $flags = [];
    foreach ($filteredIds as $group) {
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/listGroups",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode([
          'listGroup' => [
            'groupid' => (int) $group,
            'listid' => $listID,
          ],
        ]),
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
          "content-type: application/json",
        ],
      ]);
      $response = curl_exec($curl);
      curl_close($curl);
      $flags[] = !empty(json_decode($response, TRUE)['listGroup']['id']);
    }
    return in_array(TRUE, $flags);
  }

  /**
   * Collect list data from ActiveCampaign.
   *
   * @param array $list
   *   List id.
   *
   * @return array
   *   List data is returned if found.
   */
  public function getLists(array $list):array {

    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $list), function ($value) {
      return $value !== NULL;
    });
    $lists = [];
    foreach ($filteredIds as $filteredId) {

      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/lists/$filteredId",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
        ],
      ]);
      $response = curl_exec($curl);
      curl_close($curl);
      $lists[] = json_decode($response, TRUE)['list'] ?? [];
    }
    return $lists;
  }

  /**
   * Deleting list on ActiveCampaign.
   *
   * @param array $list
   *   List id.
   *
   * @return bool
   *   True if a list is deleted.
   */
  public function deleteLists(array $list): bool {
    $curl = curl_init();
    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $list), function ($value) {
      return $value !== NULL;
    });
    $flags = [];
    foreach ($filteredIds as $filteredId) {
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/lists/$filteredId",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
        ],
      ]);
      $response = curl_exec($curl);
      curl_close($curl);
      $flags[] = empty(json_decode($response, TRUE));
    }
    return in_array(TRUE, $flags);
  }

  /**
   * Collect all contacts on list.
   *
   * @param array $list
   *   List id.
   *
   * @return array
   *   Returns contacts associated if list id.
   */
  public function getContactsByLists(array $list):array {
    $contacts = [];
    $filteredIds = array_filter(array_map(function ($id) {
      return is_numeric($id) ? $id : NULL;
    }, $list), function ($value) {
      return $value !== NULL;
    });
    foreach ($filteredIds as $filteredId) {
      $curl = curl_init();
      curl_setopt_array($curl, [
        CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/contacts?listid=$filteredId&status=1",
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => [
          "Api-Token: {$this->tokens[0]['key']}",
          "accept: application/json",
        ],
      ]);
      $response = curl_exec($curl);
      curl_close($curl);
      $contacts[$filteredId] = json_decode($response, TRUE)['contacts'] ?? [];
    }
    return $contacts;
  }

  /**
   * Get campaigns.
   *
   * @return array
   *   Returns array of campaigns.
   */
  public function getCampaigns(): array {
    $curl = curl_init();
    curl_setopt_array($curl, [
      CURLOPT_URL => "{$this->tokens[0]['url']}/api/3/campaigns?orders[sdate]=ASC",
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => [
        "Api-Token: {$this->tokens[0]['key']}",
        "accept: application/json",
      ],
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE)['campaigns'] ?? [];
  }

}
