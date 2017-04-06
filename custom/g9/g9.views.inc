<?php

/**
 * @file
 * The Group Nine views hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function g9_views_data_alter(array &$data) {
  $data['node']['node_flagger'] = [
    'title' => t('Node flagger'),
    'field' => [
      'title' => t('Node flagger'),
      'help' => t('Provides logic for output of flag fields.'),
      'id' => 'node_flagger',
    ],
  ];

  $data['node']['node_field_brand'] = [
    'title' => t('Company Access filter'),
    'filter' => [
      'title' => t('Company Access filter'),
      'help' => 'Filter nodes based on the current user\'s company.',
      'field' => 'field_brand',
      'id' => 'company_access_filter',
    ],
  ];
}
