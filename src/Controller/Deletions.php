<?php

namespace Drupal\active_campaign_api_integration\Controller;

use Drupal\active_campaign_api_integration\Plugin\ApiCaller;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @file
 * File contains Deletions class.
 */

/**
 * Deletions class handles deletions of lists and contacts.
 */
class Deletions {

  /**
   * Confirmation of contacts deletions.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return array|string[]
   *   Returns render array.
   */
  public function confirmation(Request $request):array {
    $contacts = $request->get('contacts', NULL);
    if (!empty($contacts)) {
      $contacts = (new ApiCaller())->getContactsByIds(explode(',', $contacts));
    }

    if (!str_contains($request->getRequestUri(), 'action')) {
      $delete = $request->getRequestUri() . "?action=delete";
      $cancel = $request->getRequestUri() . "?action=cancel";
    }

    $action = $request->get('action', NULL);
    if (empty($action)) {
      return [
        '#theme' => 'confirmation_campaign_activecampaign',
        '#title' => 'ActiveCampaign Lists',
        '#content' => [
          'contacts' => $contacts,
          'name' => 'Delete these Contact (s)',
          'delete' => $delete,
          'cancel' => $cancel,
        ],
        '#attached' => [
          'library' => [
            'active_campaign_api_integration/manager_assets',
          ],
        ],
      ];
    }

    $redirect = Url::fromRoute("active_campaign_api_integration.contacts")->toString();

    // Delete action.
    if ($action === 'delete') {
      $contacts = explode(',', $request->get('contacts', ""));
      $total = count($contacts);
      if ((new ApiCaller())->deleteContacts($contacts)) {
        active_campaign_api_integration_msg()->addMessage("Contacts ($total) deleted successfully");
      }
      else {
        active_campaign_api_integration_msg()->addError("Deletions of contacts ($total) failed");
      }
      (new RedirectResponse($redirect))->send();
      exit;
    }

    // On cancel just redirect back to listing page.
    if ($action === "cancel") {
      (new RedirectResponse($redirect))->send();
      exit;
    }
    return ['#markup' => 'Something went wrong!'];
  }

  /**
   * Confirmation of list and other list actions related.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request an object for params.
   *
   * @return array
   *   Returns render an array.
   */
  public function listConfirmation(Request $request):array {

    $list = explode(',', $request->get("lists", ''));
    if (!empty($list)) {
      $list = (new ApiCaller())->getLists($list);
    }

    if (!str_contains($request->getRequestUri(), 'action')) {
      $delete = $request->getRequestUri() . "?action=delete";
      $cancel = $request->getRequestUri() . "?action=cancel";
    }

    $action = $request->get('action', NULL);
    if (empty($action)) {
      return [
        '#theme' => 'confirmation_campaign_activecampaign',
        '#title' => 'ActiveCampaign Lists',
        '#content' => [
          'contacts' => $list,
          'name' => 'List (s)',
          'delete' => $delete,
          'cancel' => $cancel,
        ],
        '#attached' => [
          'library' => [
            'active_campaign_api_integration/manager_assets',
          ],
        ],
      ];
    }

    $total = count($list);
    $redirect = Url::fromRoute('active_campaign_api_integration.listing')->toString();

    if ($action === "delete") {
      if ((new ApiCaller())->deleteLists(explode(',', $request->get('lists')))) {
        active_campaign_api_integration_msg()->addMessage("Lists ($total) deleted successfully");
      }
      else {
        active_campaign_api_integration_msg()->addError("Failed to delete lists ($total)");
      }
    }
    (new RedirectResponse($redirect))->send();
    exit;
  }

}
