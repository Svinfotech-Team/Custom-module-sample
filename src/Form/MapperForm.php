<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\RequestHandler;
use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * File to handle form mappings.
 */

/**
 * Mapping form with active fields.
 *
 * @class MapperForm used to map form with Active fields.
 */
class MapperForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    private readonly Connection $database,
    protected $messenger,
    protected RequestHandler $requestHandler
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new self(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('active_campaign_api_integration.request_handler')
    );
  }

  /**
   * Returns this form id.
   *
   * @return string
   *   Form id
   */
  public function getFormId(): string {
    return "campaign_mapping_form";
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form,
                            FormStateInterface $form_state): array {

    $formID = $this->requestHandler->getRequestParameter("form_id");
    $default = "";
    if (!empty($formID) && is_numeric($formID)) {
      $map = (new StoragesHandlers($this->database))->mapping($formID);
      if (!empty($map)) {
        $default = $map[0]['map'] ?? NULL;
        if (!empty($default)) {
          $default = json_encode($default, JSON_PRETTY_PRINT);
        }
      }
    }
    $form['mapping_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mapping Fieldset'),
    ];

    $form['mapping_fieldset']['list_number'] = [
      '#type' => 'hidden',
      '#title' => $this->t('List ID'),
      '#required' => TRUE,
      '#default_value' => '1',
    ];

    $form['mapping_fieldset']['mapping_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Mapping Type'),
      '#options' => [
        'contact_sync' => $this->t('Contact Sync'),
        'contact_add' => $this->t('Add Contact'),
      ],
      '#required' => TRUE,
    ];

    $form['mapping_fieldset']['mapping'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mapping'),
      '#default_value' => $default,
      '#description' => $this->t('Mapping field need to be done with
      carefully consideration failing to do so your form may not work
      accordingly'),
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    // Attach a library to the form element.
    $form['#attached']['library'][] = 'active_campaign_api_integration/manager_assets';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form,
                               FormStateInterface $form_state): void {

    // Validate the 'mapping' textarea field.
    $mapping = $form_state->getValue('mapping');
    if (empty($mapping)) {
      $form_state->setErrorByName('mapping', $this->t('Mapping field is required.'));
    }

    // Validate the 'mapping_type' select field.
    $mapping_type = $form_state->getValue('mapping_type');
    if (empty($mapping_type)) {
      $form_state->setErrorByName('mapping_type', $this->t('Mapping Type field is required.'));
    }

    // Validate the 'mapping_type' select field.
    if (empty($mapping_type)) {
      $form_state->setErrorByName('list_number', $this->t('List ID field is required.'));
    }

  }

  /**
   * Handling this form submission.
   *
   * @param array $form
   *   Form itself.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate.
   *
   * @return void
   *   Nothing is returned
   *
   * @throws \Exception
   */
  public function submitForm(array &$form,
                             FormStateInterface $form_state): void {
    // @todo Implement submitForm() method.
    $mapping = $form_state->getValue('mapping');
    $mapping_type = $form_state->getValue('mapping_type');
    $list_id = $form_state->getValue('list_number');

    if (!empty(json_decode($mapping, TRUE))) {
      $formID = $this->requestHandler->getRequestParameter("form_id");
      if (is_numeric($formID)) {
        $data['list_id'] = $list_id;
        $data['type'] = $mapping_type;
        $data['mapping'] = json_decode($mapping, TRUE);

        $storage = new StoragesHandlers($this->database);
        if ($storage->saveMapping($data, intval($formID))) {
          $this->messenger->AddMessage("Successfully saved mapping please try
          to submit the form submit mapped for to see if it is working
          properly.");
        }
        else {
          $this->messenger->addError("Failed to save mapping");
        }
      }
    }
    else {
      $this->messenger->addError("Failed to parse your mapping please
       Make sure its valid json format");
    }

  }

}
