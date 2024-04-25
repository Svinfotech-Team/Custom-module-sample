<?php

namespace Drupal\active_campaign_api_integration\Form;

use Drupal\active_campaign_api_integration\Plugin\StoragesHandlers;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @file
 * Form ID collection class.
 */

/**
 * Class used to collect form id.
 *
 * @class This class will collect form id of drupal site form.
 */
class TargetForm extends FormBase {

  /**
   * Construct injecting dependencies.
   *
   * {@inheritdoc }
   */
  public function __construct(
    private readonly Connection $database,
    protected $messenger
  ) {

  }

  /**
   * BuildUp dependencies.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   *
   * @return static
   *   BuildUp object.
   */
  public static function create(ContainerInterface $container): static {
    return new self(
      $container->get('database'),
      $container->get('messenger')
    );
  }

  /**
   * Building this form.
   *
   * @param array $form
   *   Form itself.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate.
   *
   * @return array
   *   Array of form definitions. Ie fields.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['custom_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Your Form ID'),
      '#description' => $this->t("Please you will need to have id
      of your form and submit it."),
    ];

    $form['custom_fieldset']['form_submitted_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form ID'),
      '#description' => $this->t('Enter the form ID.'),
    ];

    $form['custom_fieldset']['form_target_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Form Label'),
      '#description' => $this->t('Enter the form ID.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    // Attach a library to the form element.
    $form['#attached']['library'][] = 'active_campaign_api_integration/manager_assets';
    return $form;
  }

  /**
   * Validating this form upon submission.
   *
   * @param array $form
   *   Form itself.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate of this form.
   *
   * @return void
   *   Nothing is returned.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $form_id = $form_state->getValue('form_submitted_field');
    $form_label = $form_state->getValue("form_target_label");
    // Add your validation logic here.
    if (empty($form_id)) {
      $form_state->setErrorByName('form_submitted_field', $this->t('The Form ID field is required.'));
    }
    if (empty($form_label)) {
      $form_state->setErrorByName('form_submitted_field', $this->t('The Form Label field is required.'));
    }
  }

  /**
   * Handling final data submitted.
   *
   * @param array $form
   *   From itself.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate of this form.
   *
   * @return void
   *   Notthing is returned
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // You can process the form data here.
    $form_id = $form_state->getValue('form_submitted_field');
    $form_label = $form_state->getValue("form_target_label");
    $storage = new StoragesHandlers($this->database);
    if ($storage->saveFormId($form_id, $form_label)) {
      $this->messenger->addMessage(
        "Form ID added please proceed to Map page to configure with ActiveCampaign Fields"
      );
    }
    else {
      $this->messenger->addError("Failed to save Form ID");
    }
  }

  /**
   * Returns form id.
   *
   * @return string
   *   This form id
   */
  public function getFormId(): string {
    return "campaign_target_form";
  }

}
