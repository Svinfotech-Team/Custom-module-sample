<?php

namespace Drupal\active_campaign_api_integration\Plugin;

use Symfony\Component\HttpFoundation\Request;

/**
 * @file
 * This is handling Request parameters.
 */

/**
 * Injecting Request stack in MapperForm.
 *
 * @class This class does Injection.
 */
class RequestHandler {

  /**
   * Object of RequestStack or null.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected ?Request $request;

  /**
   * Build up an object of RequestStack.
   */
  public function __construct() {
    $this->request = active_campaign_api_integration_request();
  }

  /**
   * To get the value of key in params.
   *
   * @param string $name
   *   Key of get request params.
   *
   * @return mixed
   *   Returns value of key name passed.
   */
  public function getRequestParameter(string $name): mixed {
    return $this->request->get($name);
  }

}
