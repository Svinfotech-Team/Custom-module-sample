<?php

namespace Drupal\active_campaign_api_integration\Controller;

/**
 * @file
 * File to show help snippets on page.
 */

/**
 * MappingHelp is to show mapping contents.
 *
 * @class Class Helping is to handle help content on page.
 */
class MappingHelp {

  /**
   * Helping content to entry method.
   *
   * @return array
   *   Array of theme information.
   */
  public function helping(): array {
    return [
      '#theme' => 'campaign_how_to_map',
      '#title' => 'ActiveCampaign Forms Listing',
      '#content' => ['forms' => ['nothing']],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];
  }

}
