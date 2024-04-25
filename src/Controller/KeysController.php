<?php

namespace Drupal\active_campaign_api_integration\Controller;

use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * Page to listing keys.
 */

/**
 * Is being used to list all keys.
 *
 * @class KeyController list in table on page.
 */
class KeysController extends ControllerBase {

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
      $container->get('messenger'),

    );
  }

  /**
   * Listing keys on page.
   *
   * @return array
   *   Theme template information.
   */
  public function listingKeys():array {
    $storages = new StoragesHandlers($this->database);
    $keys = $storages->getCampaignKeys();
    $output = [
      '#theme' => 'campaign_keys_listing',
      '#title' => 'ActiveCampaign Forms Listing',
      '#content' => ['tokens' => $keys],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];
    return $output;
  }

}
