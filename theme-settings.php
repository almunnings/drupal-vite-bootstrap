<?php

/**
 * @file
 * Drupal Vite Bootstrap Theme Settings.
 */

declare(strict_types=1);

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements THEME_form_system_theme_settings_alter().
 */
function dvb_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  // Work-around for a core bug affecting admin themes. See issue #943212.
  if (isset($form_id)) {
    return;
  }

  $form['dvb'] = [
    '#type' => 'details',
    '#title' => t('Vite'),
    '#open' => TRUE,
  ];

  $form['dvb']['developer_mode'] = [
    '#type' => 'checkbox',
    '#title' => t('Developer Mode'),
    '#default_value' => \Drupal::state()->get('theme_dvb_developer_mode', FALSE),
    '#description' => t('Enable Vite HMR developer mode. Do not use in production.'),
  ];

  $form['#submit'][] = 'dvb_form_system_theme_settings_submit';
}

/**
 * Form submit callback for dvb_form_system_theme_settings_alter().
 */
function dvb_form_system_theme_settings_submit($form, FormStateInterface $form_state) {
  \Drupal::state()->set(
      'theme_dvb_developer_mode',
      (bool) $form_state->getValue('developer_mode')
    );

  $form_state->unsetValue('developer_mode');

  drupal_flush_all_caches();
}
