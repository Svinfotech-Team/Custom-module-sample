<?php

namespace Drupal\active_campaign_api_integration\Controller;

use Drupal\active_campaign_api_integration\Form\CreatePipelineForm;
use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\Core\Controller\ControllerBase;

/**
 * @file
 * File contains Pipelines class.
 */

/**
 * Pipelines class handles pipelines.
 */
class Pipelines extends ControllerBase {

  /**
   * Construct of this class.
   */
  public function __construct() {}

  /**
   * Route handler.
   *
   * @return array
   *   Returns render array.
   *
   * @throws \Exception
   */
  public function displayAll():array {
    // Build the form.
    $form = $this->formBuilder()->getForm(CreatePipelineForm::class);

    $pipelines = (new ApiCaller())->pipeLines();
    $table = $this->pipelines($pipelines);

    // Combine form and table in a render array.
    return [
      'form' => $form,
      'table' => ['#markup' => $table],
    ];
  }

  /**
   * Building table of pipelines.
   *
   * @param array $pipelines
   *   Pipelines data to be displayed in table.
   *
   * @return string
   *   Table string is returned.
   *
   * @throws \Exception
   */
  private function pipelines(array $pipelines): string {

    if (empty($pipelines)) {
      return "<table></table>";
    }
    $allFinal = [];
    $stages = (new ApiCaller())->getStages();

    foreach ($pipelines as $pipeline) {
      $pStages = $pipeline['stages'];
      $function = function () use ($stages, $pStages):array {
        $titles = [];
        foreach ($pStages as $stage) {
          foreach ($stages as $s) {
            if ($stage == $s['id']) {
              $titles[] = $s['title'];
            }
          }
        }
        return $titles;
      };
      $allFinal[] = [
        'title' => $pipeline['title'] ?? NULL,
        'currency' => strtoupper($pipeline['currency'] ?? ""),
        'created' => (new \DateTime($pipeline['cdate'] ?? 'now'))->format('d F, Y'),
        'stages' => $function(),
      ];
    }
    // Build the render array.
    $renderArray = [
      '#theme' => 'campaign_listing_pipelines',
      '#title' => 'ActiveCampaign Pipelines',
      '#content' => ['lists' => $allFinal],
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
