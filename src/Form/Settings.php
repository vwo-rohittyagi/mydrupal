<?php

namespace Drupal\vwo\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * VWO Settings form.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vwo_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vwo.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('vwo.settings');

    $id = $config->get('id');
    $form['id_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Account'),
    ];
    $form['id_fieldset']['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('VWO Account ID'),
      '#description' => implode('', [
        $this->t('Your numeric Account ID or placeholder "NONE". This is the number after <q>var _vis_opt_account_id =</q> in the VWO Smart Code.'),
        '<br /><strong>',
        $this->t('You can use the <a href=":url">Parse Account ID</a> tool to extract the Account ID from the full VWO Smart Code.', [
          ':url' => Url::fromRoute('vwo.settings.vwoid')->toString(),
        ]),
        '</strong>',
      ]),
      '#size' => 15,
      '#maxlength' => 20,
      '#required' => TRUE,
      '#default_value' => ($id == NULL) ? 'NONE' : $id,
    ];

    $form['synchtype_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Asynchronous/ Synchronous loading'),
    ];
    $form['synchtype_fieldset']['synchtype'] = [
      '#type' => 'radios',
      '#title' => $this->t('Javascript loading method'),
      '#description' => implode('', [
        '<p>',
        $this->t('Asynchronous loading is now the default. Please see <a target="_blank" href="https://vwo.com/blog/asynchronous-code/">https://vwo.com/blog/asynchronous-code/</a> for more details.'),
        '</p><p>',
        $this->t('The Asynchronous version of VWO code reduces page load time as the VWO code is downloaded in parallel to site code. It also ensures that your site is never slowed down even if the VWO servers are inaccessible.'),
        '</p><p>',
        $this->t('VWO have extensively tested the asynchronous code across variety of browsers (including IE7) and it works perfectly.'),
        '</p><p><strong>',
        $this->t('NB: Due to change in D8 inline javascript, synchronous code is not implemented.'),
        '</strong> ',
        $this->t('See changelog for more details.'),
        '</p>',
      ]),
      '#options' => [
        'async' => $this->t('Asynchronous (default)'),
        'sync' => $this->t('Synchronous'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('loading.type'),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#title' => $this->t('Advanced Asynchronous Settings'),
      '#description' => implode('', [
        '<p>',
        $this->t('These settings are only used when Asynchronous loading mode is selected above.'),
        '</p><p>',
        $this->t('Note that in normal circumstances, all the data and files that need to be download will get downloaded in 100-200 milliseconds, so the following timeouts are an absolute maximum threshold and can safely be kept as is.'),
        '</p><p>',
        $this->t('One possible side effect of decreasing these timeouts, would be users on slower internet connections reaching the timeout and as a result, missing out on becoming part of the test.'),
        '</p>',
      ]),
      '#states' => [
        'visible' => [
          ':input[name=synchtype]' => ['value' => 'async'],
        ],
      ],
    ];

    $form['advanced']['asynctollibrary'] = [
      '#type' => 'number',
      '#title' => $this->t('Test Library Download Timeout'),
      '#description' => $this->t('The maximum time in milliseconds the code snippet will wait for the VWO javascript library to be downloaded from the Amazon Cloudfront Content Delivery Network. If the library is not available in this time, your original page will be displayed without tests. Default: 2500 ms.'),
      '#size' => 10,
      '#min' => 0,
      '#max' => 9999,
      '#required' => TRUE,
      '#default_value' => $config->get('loading.timeout.library'),
    ];

    $form['advanced']['asynctolsettings'] = [
      '#type' => 'number',
      '#title' => $this->t('Test Settings Download Timeout'),
      '#description' => $this->t('The maximum time in milliseconds the code snippet will wait for test settings to arrive from the VWO servers. If no settings arrive within this period, your original page will be displayed without tests. Default: 2000 ms.'),
      '#size' => 10,
      '#min' => 0,
      '#max' => 9999,
      '#required' => TRUE,
      '#default_value' => $config->get('loading.timeout.settings'),
    ];

    $form['advanced']['asyncusejquery'] = [
      '#type' => 'radios',
      '#title' => $this->t('Use existing jQuery'),
      '#description' => $this->t('Configure the "use_existing_jquery" option in the code snippet. Please provide feedback to the module Author regarding your experiences with this setting.'),
      '#options' => [
        'local' => $this->t('True'),
        'import' => $this->t('False (default)'),
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('loading.usejquery'),
    ];

    $form['actions'] = [
      '#type' => 'actions',

      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save configuration'),
        '#button_type' => 'primary',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // VWO ID Was not set to "number" field to allow for setting it to "NONE",
    // and so much be manually validated.
    $vwoid = $form_state->getValue('id');
    if (!preg_match('/^\d+$/', $vwoid) && $vwoid != 'NONE') {
      $form_state->setErrorByName('id',
        $this->t('Your VWO ID must be numeric (or set to "NONE" to disable). If you have having issues locating it, please use the <a href=":parse_url">Extract Account Id tool</a>.', [
          ':parse_url' => Url::fromRoute('vwo.settings.vwoid')->toString(),
        ])
      );
    }

    // Translate that NONE into NULL for Config API.
    if ($vwoid == 'NONE') {
      $form_state->setValue('id', NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Grab the editable configuration.
    $config = $this->config('vwo.settings');

    // Set each of the configuration values.
    $field_key_config_map = [
      'id' => 'id',
      'synchtype' => 'loading.type',
      'asynctollibrary' => 'loading.timeout.library',
      'asynctolsettings' => 'loading.timeout.settings',
      'asyncusejquery' => 'loading.usejquery',
    ];
    foreach ($field_key_config_map as $field_key => $config_key) {
      $config->set($config_key, $form_state->getValue($field_key));
    }

    // Commit saved configuration.
    $config->save();

    $this->messenger()->addMessage($this->t('VWO settings have been saved.'));
  }

}
