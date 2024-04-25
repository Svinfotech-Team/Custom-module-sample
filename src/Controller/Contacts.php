<?php

namespace Drupal\active_campaign_api_integration\Controller;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\active_campaign_api_integration\Plugin\TypeApiCalling;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @file
 * File contains Contacts class.
 */

/**
 * Contacts class to display and handle imports.
 */
class Contacts extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected $usersLoader
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new self(
      $container->get('active_campaign_api_integration.user_loader'),
    );
  }

  /**
   * Display contacts.
   *
   * @return array
   *   Returns render an array.
   */
  public function listing():array {
    $contacts = (new ApiCaller())->activeCampaignContacts();
    $request = active_campaign_api_integration_request();
    $base = $request->getBaseUrl();
    return [
      '#theme' => 'campaign_contacts_lists',
      '#title' => 'ActiveCampaign Contacts',
      '#content' => ['contacts' => $contacts, 'base' => $base],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];
  }

  /**
   * Displaying all users that are not in activeCampaign but available in db.
   *
   * @return array
   *   Returns render an array
   */
  public function importing():array {

    $contactsFromActiveCampaign = (new ApiCaller())->activeCampaignContacts();
    $campaignEmails = [];
    foreach ($contactsFromActiveCampaign as $value) {
      $campaignEmails[] = $value['email'] ?? NULL;
    }
    $campaignEmails = array_filter($campaignEmails, 'strlen');
    $users = $this->usersLoader->loadAllUsers();
    $remainUsers = [];

    if (!empty($users)) {
      foreach ($users as $key => $user) {
        if ($user instanceof User) {
          $email = $user->getEmail();
          if (!in_array($email, $campaignEmails) && !empty($email)) {
            $remainUsers[] = [
              'id' => $user->id(),
              'email' => $user->getEmail(),
            ];
          }
        }
      }
    }

    if (!empty($remainUsers)) {
      $request = active_campaign_api_integration_request();
      $base = $request->getBaseUrl();
      return [
        '#theme' => 'campaign_contacts_imports',
        '#title' => 'System Users To Export',
        '#content' => ['users' => $remainUsers, 'base' => $base],
        '#attached' => [
          'library' => [
            'active_campaign_api_integration/manager_assets',
          ],
        ],
      ];
    }
    $redirect = Url::fromRoute('active_campaign_api_integration.contacts')->toString();
    (new RedirectResponse($redirect))->send();
    exit;
  }

  /**
   * Importing contact from system to ActiveCampaign.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request an object.
   *
   * @return array
   *   Returns render an array.
   */
  public function import(Request $request):array {

    $uids = explode(',', $request->get('uid', ''));
    $uids = array_filter($uids, 'strlen');
    $mapping = (new StoragesHandlers(active_campaign_api_integration_db()))->userMappingconfiguration(FALSE);
    $mapping = $mapping[0]->mapping ?? NULL;
    if (!empty($mapping)) {

      // Making mapping into array.
      $mapping = json_decode($mapping, TRUE);
      foreach ($uids as $uid) {

        // Loading user.
        $user = User::load($uid);
        if ($user instanceof User) {

          // Initiation of useful arrays.
          $custom = [];
          $defaults = [];
          $email = $user->getEmail();

          // Using a mapping array to get value from user to campaign format.
          foreach ($mapping as $key => $item) {

            // Temporary holding value found by field in mapping.
            $value = $user->get($item)->getValue();
            if (is_numeric($key)) {

              // Building for custom fields.
              $custom[] = ['field' => $key, 'value' => $value[0]['value'] ?? ""];
            }
            else {

              // Building for default fields.
              $defaults[$key] = $value[0]['value'] ?? NULL;
            }
          }

          // Marging all together for transfer.
          $defaults['fieldValues'] = $custom;

          // Transferring contacts.
          if ((new TypeApiCalling(active_campaign_api_integration_db()))
            ->sendingUserContact($defaults)) {
            active_campaign_api_integration_msg()->addMessage("Exported user $email");
          }
          else {
            active_campaign_api_integration_msg()->addError("Failed to export user $email");
          }
        }
      }
    }
    $redirect = Url::fromRoute('active_campaign_api_integration.contacts_imports')->toString();
    (new RedirectResponse($redirect))->send();

    return ['#markup' => 'Something went wrong!'];
  }

}
