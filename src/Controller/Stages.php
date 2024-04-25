<?php

namespace Drupal\active_campaign_api_integration\Controller;

use Drupal\active_campaign_api_integration\Form\CreateStageForm;
use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\Core\Controller\ControllerBase;

/**
 * @file
 * File contains Stages Class.
 */

/**
 * Stages class handles Stages activities.
 */
class Stages extends ControllerBase {

  /**
   * Stages controller listener.
   *
   * @return array
   *   Returns render array.
   *
   * @throws \Exception
   */
  public function displayAll():array {
    $form = $this->formBuilder()->getForm(CreateStageForm::class);
    $stages = (new ApiCaller())->getStages();

    $table = $this->stagesTables($stages);
    return [
      'form' => $form,
      'table' => ['#markup' => $table],
    ];
  }

  /**
   * Build table of stages data.
   *
   * @param array $stages
   *   Stages data.
   *
   * @return string
   *   Returns table string.
   *
   * @throws \Exception
   */
  private function stagesTables(array $stages):string {
    foreach ($stages as $key => $stage) {
      $group = $stage['group'];
      $pipeline = (new ApiCaller())->getPipeline((int) $group);
      $stages[$key]['group'] = $pipeline['title'] ?? NULL;
      $created = "Default";
      if (!empty($stage['cdate'])) {
        $created = (new \DateTime($stage['cdate'] ?? 'now'))->format('d F, Y');
      }
      $stages[$key]['cdate'] = $created;
    }
    // Build the render array.
    $renderArray = [
      '#theme' => 'campaign_listing_stages',
      '#title' => 'ActiveCampaign Stages',
      '#content' => ['lists' => $stages],
      '#attached' => [
        'library' => [
          'active_campaign_api_integration/manager_assets',
        ],
      ],
    ];

    // Get the HTML output.
    return active_campaign_api_integration_renderer()->renderRoot($renderArray);
  }

}
