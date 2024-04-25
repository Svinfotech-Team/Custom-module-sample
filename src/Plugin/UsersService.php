<?php

namespace Drupal\active_campaign_api_integration\Plugin;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * @file
 * The file contains the service of users loader.
 */

/**
 * UsersService is services.
 */
class UsersService {

  /**
   * EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * EntityTypeManager property.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Injected service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Collection of users.
   *
   * @return array
   *   Users in a system.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function loadAllUsers(): array {
    $user_storage = $this->entityTypeManager->getStorage('user');
    return $user_storage->loadMultiple();
  }

}
