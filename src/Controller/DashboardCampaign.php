<?php

namespace Drupal\active_campaign_api_integration\Controller;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/*
 *@file
 * Dashboard controller file this file is handling dashboard.
 */

/**
 * Class Dashboard is for only dashboard activities.
 *
 * @class Controller class for ActiveCampaign Dashboard.
 */
class DashboardCampaign extends ControllerBase {

  /**
   * Main entry point for dashboard.
   */
  public function dashboard():array {

    $condigRegistration = Url::fromRoute(
      "active_campaign_api_integration.form_registration_set_up"
    )->toString();
    $keysAdd = Url::fromRoute(
      "active_campaign_api_integration.api_keys"
    )->toString();
    $keysList = Url::fromRoute(
      "active_campaign_api_integration.keys"
    )->toString();
    $mapList = Url::fromRoute(
      "active_campaign_api_integration.map_user_list"
    )->toString();
    $listActive = Url::fromRoute(
      "active_campaign_api_integration.listing"
    )->toString();
    $contacts = Url::fromRoute(
      "active_campaign_api_integration.contacts"
    )->toString();
    $stages = Url::fromRoute(
      "active_campaign_api_integration.stages"
    )->toString();
    $pipelines = Url::fromRoute(
      "active_campaign_api_integration.pipelines"
    )->toString();
    $campaigns = (new ApiCaller())->getCampaigns();
    return [
      '#theme' => 'campaign_dashboard',
      '#title' => 'ActiveCampaign Dashboard',
      '#content' => [
        'campaigns' => $campaigns,
        'links' => [
          'regi' => $condigRegistration,
          'kadd' => $keysAdd,
          'klist' => $keysList,
          'userList' => $mapList,
          'alist' => $listActive,
          'contacts' => $contacts,
          'stages' => $stages,
          'pipelines' => $pipelines,
        ],
      ],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];
  }

}
