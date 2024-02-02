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

  $form['vite'] = [
    '#type' => 'details',
    '#title' => t('Vite'),
    '#open' => TRUE,
    '#tree' => TRUE,
  ];

  $form['vite']['developer_mode'] = [
    '#type' => 'checkbox',
    '#title' => t('Developer mode'),
    '#default_value' => \Drupal::state()->get('vite.developer_mode', FALSE),
    '#description' => t('Enable Vite HMR developer mode. Do not use in production.'),
  ];

  $form['vite']['port'] = [
    '#type' => 'textfield',
    '#title' => t('Vite port'),
    '#default_value' => theme_get_setting('vite.port'),
    '#description' => t('The port to listen for Vite.'),
    '#required' => TRUE,
  ];

  $form['vite']['dist_path'] = [
    '#type' => 'textfield',
    '#title' => t('Vite dist path'),
    '#default_value' => theme_get_setting('vite.dist_path'),
    '#description' => t('The path to the Vite dist directory.'),
    '#required' => TRUE,
  ];

  $form['vite']['manifest_path'] = [
    '#type' => 'textfield',
    '#title' => t('Vite manifest Path'),
    '#default_value' => theme_get_setting('vite.manifest_path'),
    '#description' => t('The path to the Vite manifest.'),
    '#required' => TRUE,
  ];

  $form['#submit'][] = 'dvb_form_system_theme_settings_submit';
}

/**
 * Form submit callback for dvb_form_system_theme_settings_alter().
 */
function dvb_form_system_theme_settings_submit($form, FormStateInterface $form_state) {
  \Drupal::state()->set(
    'vite.developer_mode',
    (bool) $form_state->getValue(['vite', 'developer_mode'])
  );

  $form_state->unsetValue(['vite', 'developer_mode']);

  drupal_flush_all_caches();
}
