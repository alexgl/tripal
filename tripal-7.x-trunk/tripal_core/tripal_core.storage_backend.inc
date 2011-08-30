<?php

/**
 * @file defines the storage backend for all fields stored in the chado database
 *
 * NOTE: Assumes there is a $database['chado']['default'] = settings for your chado database
 */
 
/**
 * Implements hook_field_storage_info()
 * Expose Field API storage backends
 */
function tripal_core_field_storage_info() {
  return array(
    'chado_field_sql_storage' => array(
      'label' => t('GMOD Chado SQL storage'), 
      'description' => t('Stores fields in the chado SQL database, using per-field tables where a field might be stock or feature.'),
    ),
  );
 }
 
/**
 * Implement hook_field_storage_details()
 */
function chado_field_sql_storage_field_storage_details($field) {
  // We probably want to implement that at one point, but this is informative only.
}

/**
 * Implement hook_field_storage_create_field().
 */
function chado_field_sql_storage_field_storage_create_field($field) {
  // Normally would create the tables the fields are going to be stored in
  // Since chado is a pre-existing schema and fields map to pre-existing tables, 
  // we don't need to create any tables
}

