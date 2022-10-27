<?php

namespace Drupal\vwo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FORM: Extract ID.
 *
 * Form to accept full cut'n'paste of <del>Tracking</del>Smart Code from
 * website and pull Account ID from it using preg_match().
 */
class ExtractID extends FormBase {
  /**
   * Messenger Object.
   *
   * @var messenger
   */
  protected $messenger;

  /**
   * Class constructor.
   */
  public function __construct(MessengerInterface $messenger) {
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vwo_settings_vwoid';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('vwo.settings');

    // Display current ID if it exists.
    $id = $config->get('id');
    $form['current_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Current Account ID'),
      '#disabled' => TRUE,
      '#default_value' => ($id == NULL) ? 'NONE' : $id,
      '#size' => 15,
    ];

    // Text area to paste into.
    $form['parse_area'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paste full VWO Smart Code here'),
      '#description' => $this->t('The Smart Code is available by logging into the VWO website and from your dashboard, selecting the <em><strong>Settings</strong></em> menu down the left side, and then <em><strong>Smart Code</strong></em> along the top. Select all the text in the <em><strong>VWO Smart Code</strong></em> text box and copy it here.'),
      '#rows' => '15',
    ];

    $form['actions'] = [
      '#type' => 'actions',

      'extract' => [
        '#type' => 'submit',
        '#value' => $this->t('Extract Account ID from pasted VWO Smart Code'),
        '#button_type' => 'primary',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $pasted_code = $form_state->getValue('parse_area');

    // Synchronous version of code.
    if (preg_match('/var _vis_opt_account_id = ([[:digit:]]+);/', $pasted_code, $matches)) {
      $form_state->set('parsed_id', $matches[1]);
    }

    // Asynchronous version of code.
    elseif (preg_match('/var account_id=([[:digit:]]+),/', $pasted_code, $matches)) {
      $form_state->set('parsed_id', $matches[1]);
    }

    // Failure.
    else {
      $form_state->setErrorByName(
        'parse_area',
        $this->t('Unable to locate Account ID in pasted code.')
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Grab the editable configuration.
    $config = $this->configFactory()->getEditable('vwo.settings');

    // Set the parsed value.
    $config->set('id', $form_state->get('parsed_id'));

    // Commit saved configuration.
    $config->save();

    // Redirect back to main settings page.
    $form_state->setRedirect('vwo.settings');

    $this->messenger->addStatus($this->t('Saved Account ID as @id', ['@id' => $form_state->get('parsed_id')]));
  }

}
