<?php

/** 
 * Implements hook_entity_info()
 *
 * Defines the chado entity (content type) to Drupal
 *
 * @return
 *   An array describing the core entity
 */ 
function tripal_core_entity_info() {
  $entity = array(
    'chado' => array(
      'label' => t('Chado (Biological) Content'),
      'base table' => 'chado_content',
      'controller class' => 'ChadoController',
      'load hook' => 'chado_load',
      'uri callback' => 'chado_uri',
      'entity keys' => array(
        'id' => 'chado_entity_id',
        // the field in the entity where the bundle name is stored
        'bundle' => 'chado_type'
      ),
      
      // make this entity fieldable (ie: admin can add fields to this entity using the Field API)
      'fieldable' => TRUE,
      'bundle keys' => array(
        'bundle' => 'chado_type', // as above.
      ),
      'bundles' => array(
        'default' => array(
          'label' => t('Default'),
          'admin' => array(
            'path' => 'admin/structure/chado/manage/default',
            'access arguments' => array('administer chado content'),
          ),
        ),
      ),
    ),
  );
  
  $result = db_query('SELECT ct.type, ct.name FROM {chado_type} ct');
  foreach ($result->fetchAll() as $type) {
    $entity['chado']['bundles'][$type->type] = array(
      'label' => $type->name,
      'admin' => array(
        'path' => 'admin/structure/chado/manage/' . $type->type,
        'access arguments' => array('administer chado content'),
      ),
    );
  }
  
  return $entity;
}

// Paths for Entities ////////////////////////////////////////////////////////////////////////

/**
 * The uri callback for the chado entity
 *
 * This allows chado entities to be found at <drupal site>/chado/<entity type/<chado_id>
 *
 * @param $chado_entity
 *   The chado entity for which the uri is being generated
 * @return
 *   The generated uri
 */
function chado_uri ($chado_entity) {
  return array(
    'path' => 'chado/' . $chado_entity->chado_entity_id,
  );
}

// Loading Entities //////////////////////////////////////////////////////////////////////

/**
 * Loads a single chado entity using entity_load (default entity loading function)
 *
 * @param $chado_entity_id
 *   The primary key of the chado entity
 * @param $reset
 *   Whether to reset the internal cache for the requested entity type.
 * @return
 *   An array of entity objects indexed by their ids. When no results are found, 
 *   an empty array is returned.
 */
function chado_load($chado_entity_id, $reset = FALSE) {

  $entities = entity_load('chado', array($chado_entity_id), array(), $reset);
  return reset($entities);
  
}

/**
 * Loads multiple chado entities using entity_load (default entity loading function)
 *
 * @param $chado_entity_ids
 *   An array of  primary keys of the chado entities to load
 * @param $reset
 *   Whether to reset the internal cache for the requested entity type.
 * @return
 *   An array of entity objects indexed by their ids. When no results are found, 
 *   an empty array is returned.
 */
function chado_load_multiple($chado_entity_ids, $conditions = array(), $reset = FALSE) {

  $entities = entity_load('chado', $chado_entity_ids, $conditions, $reset);
  return $entities;
  
}

// Viewing Entities /////////////////////////////////////////////////////////////////////

/**
 * Build Viewing modes for chado entities
 *
 * @params $entity_type
 *   The type of entity modes are buing built for
 * @return
 *   An array of view modes
 */
function chado_build_modes($entity_type) {
  $modes = array();
  if ($obj_type == 'chado') {
    $modes = array(
      'full' => t('Full'),
    );
  }
  return $modes;
}

/**
 * Returns the title of the chado page
 *
 * @todo Change title to name (uniquename)
 */
function chado_page_title ($chado_entity) {
    
  return chado_get_title($chado_entity->chado_type,$chado_entity->chado_id);
  
}

/**
 * Prepares the entity for viewing
 * 
 * @todo Currently not building field content
 *
 * @param $chado_entity
 *   The chado entity to be viewed
 * @param $view_mode
 *   The mode for viewing selected (ie: full or teaser for nodes)
 * @return
 *   Rendered content
 */
function chado_page_view($entity, $view_mode = 'full', $langcode = NULL){

  // Remove previously built content, if exists
  $entity->content = array();
  
  // Use controller to build view
  //$controller = entity_get_controller('chado');
  //$chado_entity->content = $controller->view(array($chado_entity->chado_entity_id => $chado_entity));
  
  // Build the fields content
  field_attach_prepare_view(
    'entity',
    array($entity->chado_entity_id => $entity),
    $view_mode
  );
 
  entity_prepare_view('entity', array($entity->chado_entity_id => $entity));
 
  $entity->content += field_attach_view(
      'entity',
      $entity, $view_mode
  );
  
  // specify the theme to use and set the #entity
  $entity->content += array(
    '#theme'     => 'chado',
    '#element'   => $entity,
    '#entity'    => $entity, 
    '#view_mode' => 'full',
    '#language'  => NULL,
  );  
  
	return $entity->content;
}

function tripal_core_preprocess_chado(&$vars, $hook) {
  $vars['theme_hook_suggestions'][] = 'chado';
  $vars['theme_hook_suggestions'][] = 'chado__' . $vars['element']['#entity']->chado_type;
}

// Chado Form ////////////////////////////////////////////////////////////////////////////

/**
 * chado add form
 *
 * NOTE: To add chado type specific fields to this form,
 *  implement chado_<type>_form($entity) in your module.
 *
 * @param $form
 *   Any previous form array
 * @param $form_state
 *   The state of the form upon last submission
 * @param $chado_entity
 *   The current chado entity
 * @return
 *   An array describing the form as described by the Form API
 */
function chado_form($form, &$form_state, $mode, $chado_entity) {
  global $user;
  
  $form_state['storage']['chado_entity'] = $chado_entity;
  
  if (empty($chado_entity->chado_id)) {
    $form['#submission_type'] = 'insert';
  } else {
    $form['#submission_type'] = 'update';
  }

  // Type specific form
  $function = 'chado_'.$chado_entity->chado_type.'_form';
  if (function_exists($function)) {
    $type_form = $function($form, $form_state, $chado_entity);
    foreach ($type_form as $field_name => $field_info) {
      $form[$field_name] = $field_info;
    }
  }

  // Add any fields attached using the Field API
  field_attach_form('chado', $chado_entity, $form, $form_state);
  
  // Create an info vertical tab
  $form['additional_settings'] = array(
    '#type' => 'vertical_tabs',
    '#weight' => 99,
  );

  // Node author information for administrators
  $form['author'] = array(
    '#type' => 'fieldset',
    '#access' => user_access('administer nodes'),
    '#title' => t('Authoring information'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
    '#group' => 'additional_settings',
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'node') . '/node.js',
        array(
          'type' => 'setting',
          'data' => array('anonymous' => variable_get('anonymous', t('Anonymous'))),
        ),
      ),
    ),
    '#weight' => 90,
  );
  
  if (!empty($form_state['storage']['chado_entity']->created_by)) {
    $account = user_load($form_state['storage']['chado_entity']->created_by);
    $default_value = $account->name;
  } else {
    $default_value = $user->name;
  }
  $form['author']['created_by'] = array(
    '#type' => 'textfield',
    '#title' => t('Created by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => $default_value,
  );
  
  $form['author']['created_on'] = array(
    '#type' => 'textfield',
    '#title' => t('Created on'),
    '#maxlength' => 25,
    '#description' => t('The date format is YYYY-MM-DD. For example, the current date is %date. Leave blank to use the time of form submission.',array('%date'=>date('Y-m-d'))),
    '#default_value' => !empty($chado_entity->created_on) ? date('Y-m-d', $chado_entity->created_on) : '',
  );

  $form['author']['last_modified_by'] = array(
    '#type' => 'textfield',
    '#title' => t('Last Modified by'),
    '#maxlength' => 60,
    '#autocomplete_path' => 'user/autocomplete',
    '#default_value' => $user->name,
  );
  
  switch ($mode) {
    case 'create':
      $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'create',
        '#value' => t('Save'),
        '#weight' => 100,
      );
      break;
    case 'update':
      $form['submit'] = array(
        '#type' => 'submit',
        '#name' => 'update',
        '#value' => t('Save'),
        '#weight' => 100,
      );
      $form['submit-delete'] = array(
        '#type' => 'submit',
        '#name' => 'delete',
        '#value' => t('Delete'),
        '#weight' => 100,
      );
  }  
  return $form;
}

/**
 * Validate the chado add form input
 */
function chado_form_validate($form, &$form_state) {

  $chado_submission = array(
    'chado_type' => $form_state['storage']['chado_entity']->chado_type,
    'created_on' => ($form_state['values']['created_on']) ? $form_state['values']['created_on'] : time(),
    'created_by' => $form_state['values']['created_by'],
    'last_modified_by' => $form_state['values']['last_modified_by'],
  );
  $chado_submission = (object)$chado_submission;
  
  // Validation of any fields attached using the Field API
  field_attach_form_validate('chado', $chado_submission, $form, $form_state);

  if (!empty($form_state['values']['created_on'])) {
    if (!preg_match('/(\d+)-(\d+)-(\d+)/',$form_state['values']['created_on'])) {
      form_set_error('created_on','Invalid date entered for created on field. The date should be entered as year-month-day where the year is 4 four numbers and the month/day are 2 numbers.');
    }
  }
}

/**
 * Create a new chado entity based on validated user input
 */
function chado_form_submit($form, &$form_state) {
  global $user;
  
  if ($form_state['storage']['chado_entity']->chado_type == 'default') {
    drupal_set_message('Cannot create chado content of default type', 'error');
    return FALSE;
  }

  $chado_key = db_select('chado_type', 'c')
    ->fields('c', array('chado_table'))
    ->execute()
    ->fetchField(0);
  $chado_key .= '_id';
        
  switch ($form_state['clicked_button']['#name']) {
    case 'create':
      $function = 'chado_'.$form_state['storage']['chado_entity']->chado_type.'_insert';
      if (function_exists($function)) {
        $chado_submission = $function($form,$form_state);
        
        if (!empty($chado_submission[$chado_key])) {
          $chado_id = $chado_submission[$chado_key];
        } else {
          $chado_id = 0;
          drupal_set_message('No chado content created... '.$function.' needs to return an array including a key/value pair defining the new '.$chado_key,'error');
        }
      } else {
        $chado_id = 0;
        drupal_set_message('No chado content created... Implement '.$function,'error');
      }
      
      $update = FALSE;
      break;
    case 'update':
      $function = 'chado_'.$form_state['storage']['chado_entity']->chado_type.'_update';
      if (function_exists($function)) {
        $chado_submission = $function($form,$form_state);
        
        if (!empty($chado_submission[$chado_key])) {
          $chado_id = $chado_submission[$chado_key];
        } else {
          $chado_id = $form_state['values'][$chado_key];
        }
      } else {
        $chado_id = $form_state['values'][$chado_key];
      }
      
      $update = TRUE;
      break;
    case 'delete':
    
      break;
  }
  
  // get user uids
  if ($form_state['values']['created_by']) {
    $created_uid = db_select('users','u')
      ->fields('u', array('uid'))
      ->condition('name',$form_state['values']['created_by'])
      ->execute()
      ->fetchField(0);
  } else {
    $created_uid = $user->uid;
  }

  if ($form_state['values']['last_modified_by']) {
    $last_modified_uid = db_select('users','u')
      ->fields('u', array('uid'))
      ->condition('name',$form_state['values']['last_modified_by'])
      ->execute()
      ->fetchField(0);
  } else {
    $last_modified_uid = $user->uid;
  }
  
  // compile values for db_insert
  if ($form_state['values']['created_on']) {
    if (preg_match('/(\d+)-(\d+)-(\d+)/',$form_state['values']['created_on'],$matches)) {
      $created_timestamp = mktime(0,0,0,$matches[2],$matches[3],$matches[1]);
    } else {
      $created_timestamp = time();
    }
  } else {
    $created_timestamp = time();
  }
  $chado_submission = array(
    'chado_id' => $chado_id,
    'chado_type' => $form_state['storage']['chado_entity']->chado_type,
    'created_on' => $created_timestamp,
    'created_by' => $created_uid,
    'last_modified_by' => $last_modified_uid,
  );
  if ($form_state['storage']['chado_entity']->chado_entity_id) {
    $chado_submission['chado_entity_id'] = $form_state['storage']['chado_entity']->chado_entity_id;
  }
  $chado_submission = (object)$chado_submission;
  
  // Adds any fields attached via the Field API
  field_attach_submit('chado', $chado_submission, $form, $form_state);

  // Save the chado entity
  $chado_entity = chado_save($chado_submission, $update);

  // Redirect to the chado page view of the newly created chado entity
  $form_state['redirect'] = 'chado/'.$chado_entity->chado_entity_id;
}

/**
 * Save a chado entity
 *
 * @return
 *   Returns the save chado Entity
 */
function chado_save(&$chado_entity, $update = FALSE) {
  return entity_get_controller('chado')->save($chado_entity, $update);
}

// Creating Entities /////////////////////////////////////////////////////////////////////

/**
 * Prepares a default chado entity renders chado add form
 * 
 * @todo Add additional chado field defaults
 *
 * @return
 *   Rendered chado add form
 */
function chado_add($chado_type) {
  $chado_entity = (object) array (
    'chado_entity_id' => '',
    'type' => 'chado',
    'chado_type' => $chado_type,
  );
  
  return drupal_get_form('chado_form', 'create', $chado_entity);
}

//// Editing Entities ////////////////////////////////////////////////////////////////////

function chado_edit ($chado_entity) {

  return drupal_get_form('chado_form', 'update', $chado_entity);
  
}

//// Deleteing Entities /////////////////////////////////////////////////////////////////

function chado_delete_confirm_form($form, $form_state, $chado_entity) {
  $form = array();
  
  $form_state['storage']['entity'] = $chado_entity;
  
  $form['item'] = array(
    '#type' => 'item',
    '#markup' => t('Are you sure you want to delete '
      .l(chado_get_title($chado_entity->chado_type, $chado_entity->chado_id), 'chado/'.$chado_entity->chado_entity_id)
      .'? This action cannot be un-done.'),
  );
  
  $form['delete'] = array(
    '#type' => 'submit',
    '#name' => 'delete',
    '#value' => t('Delete'),
  );
  
  $form['cancel'] = array(
    '#type' => 'submit',
    '#name' => 'cancel',
    '#value' => t('Cancel'),
  );
  
  return $form;
}

/**
 *
 */
function chado_delete_confirm_form_submit ($form, &$form_state) {

  switch($form_state['clicked_button']['#name']) {
    case 'delete':
      chado_delete($form_state['storage']['entity']);
      $form_state['redirect'] = 'admin/chado_content';
    break;
    case 'cancel':
      $form_state['redirect'] = 'chado/'.$form_state['storage']['entity']->chado_entity_id;
    break;
  }
  
}

function chado_delete(&$chado_entity) {
  return entity_get_controller('chado')->delete($chado_entity);
}

function chado_delete_multiple($entities) {
  foreach ($entities as $entity) {
    chado_delete($entity);
  }
}