<?php

namespace Drupal\active_campaign_api_integration\Controller;

use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @file
 * Listing form is for all form that are doing listing.
 */

/**
 * Listing Class is in use of listing configurations.
 *
 * @class ListingClass entries to listing pages.
 */
class ListingForm extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    private readonly Connection $database,
    protected $messenger
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new self(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * Returns array to theme.
   *
   * @return array
   *   Array of variables to them.
   */
  public function listing(): array {
    $storage = new StoragesHandlers($this->database);
    $forms = $storage->formsAdded();
    return [
      '#theme' => 'forms_listing_campaign',
      '#title' => 'ActiveCampaign Forms Listing',
      '#content' => ['forms' => $forms],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];
  }

  /**
   * Returns mappings for a user registration form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return array
   *   Array of listed mapping.
   */
  public function userMappingListing(Request $request): array {
    $this->actioning($request);
    $maps = (new StoragesHandlers($this->database))->userMappingconfiguration();
    $mapper = [];
    if (!empty($maps)) {
      foreach ($maps as $value) {
        $active = $value->active === "yes" ?
          Url::fromRoute("active_campaign_api_integration.map_user_list",
            ["id" => $value->id, "action" => 'disabled']) :
          Url::fromRoute("active_campaign_api_integration.map_user_list",
            ["id" => $value->id, "action" => 'enabled']);
        $mapper[] = [
          'name' => $value->name ?? NULL,
          'list' => $value->list ?? 0,
          'id' => $value->id ?? NULL,
          'action' => $active->toString(),
          'text' => str_contains($active->toString(), 'enabled') ? "Disabled" :
          "Enabled",
        ];
      }
    }
    return [
      '#theme' => 'user_listing_campaign',
      '#title' => 'ActiveCampaign Mapping Listing',
      '#content' => ['map' => $mapper],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];
  }

  /**
   * For enabling and disabling mapping.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request Object.
   *
   * @return void
   *   Returned nothing.
   */
  private function actioning(Request $request): void {

    if ($request->get("id") && $request->get("action")) {
      $id = intval($request->get("id"));
      $action = $request->get("action") === "disabled" ? "no" : "yes";
      $storage = new StoragesHandlers($this->database);
      if (!empty($storage->updateUserMapping($id, $action))) {
        $this->messenger->addMessage("Configuration updated");
      }
      else {
        $this->messenger->addError("Failed to update configuration");
      }
    }
  }

}
