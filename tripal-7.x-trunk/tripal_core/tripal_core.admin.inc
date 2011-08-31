<?php 

/**
 * Implements hook_admin_paths()
 *
 * This allows <drupal site>/chado/<entity type/<chado_id>/edit to exist
 *
 * @return
 *   An array of admin paths to create
 */
function tripal_core_admin_paths() {
  $paths = array(
    'chado/add' => TRUE,
    'chado/*/add' => TRUE,
    'chado/*/edit' => TRUE,
    'chado/*/delete' => TRUE,
  );
  return $paths;
}

/////////////////////////////////////////////////////////////////////////////////////////////
// Manage chado content types
/////////////////////////////////////////////////////////////////////////////////////////////

/**
 * Managing of chado content types
 *
 * @return
 *   HTML content
 */
function tripal_core_manage_structure () {
  $output = '';
  
  $output .= '<ul class="action-links">';
  $output .= '<li>'.l('Add Chado Content Type', 'admin/structure/chado/add').'</li>';
  $output .= '</ul>';
  
  $output .= theme('table', tripal_core_list_chado_types() );
  
  return $output;
}

/**
 * List all chado types with links to edit entity info and manage fields
 *
 * @return
 *   Array describing the chado types which will be rendered by theme_table()
 */
function tripal_core_list_chado_types () {

  $results = db_select('chado_type', 'ct')
    ->fields('ct')
    ->execute();
  
  $headers = array(t('Name'),array('data'=>t('Description'),'width'=>'50%'), array('data'=>t('Operations'),'colspan'=>2));
  $rows = array();
  foreach ($results as $r) {
    $links = array();
    $links[] =  l(t('edit'),'admin/structure/chado/edit/'.$r->type);
    $links[] = l(t('delete'),'admin/structure/chado/delete_confirm/'.$r->type);
	  $links[] = l(t('manage fields'), 'admin/structure/chado/manage/' . $r->type . '/fields');
    $rows[] = array($r->name, $r->description, implode(str_repeat('&nbsp',3),$links));
  }

  $output = array(
    '#theme' => 'table',
    '#header' => $headers,
    '#rows' => $rows,
  );
  return $output;
}

/**
 * Add content type form
 *
 * @param $form_state
 *   The state of the form on last submission
 * @return $form
 *   A form array describing the add chado content type form
 */
function tripal_core_add_content_types_form ($form_state) {
  $form = array();
  
  $form['type'] = array(
    '#type' => 'textfield',
    '#title' => t('Machine Name'),
    '#description' => 'Must be all lower-case and can include the underscore(_) character',
    '#required' => TRUE,
  );
  
  $form['name'] = array(
    '#type' => 'textfield',
    '#title' => t('Human-Readable Name'),
    '#description' => 'The name displayed to users',
    '#required' => TRUE,
  );
  
  $form['description'] = array(
    '#type' => 'textarea',
    '#title' => t('Description'),
    '#description' => t('Describe what type of data is stored in this content type including a definition of any terms.'),
    '#required' => TRUE,
  );
  
  $form['help'] = array(
    '#type' => 'textarea',
    '#title' => t('Help'),
    '#description' => t('This will be shown to the users on the add/edit/delete form and should be used to give overall instructions for this content type.'),
  );
  
  $form['save'] = array(
    '#type' => 'submit',
    '#name' => 'save',
    '#value' => 'Save Content Type'
  );
  
  return $form;
}

/**
 * Add content type form submit
 *
 * @param $form
 *   The form that was submitted
 * @param $form_state
 *   The state of the form when it was submitted. contains user input
 */
function tripal_core_add_content_types_form_submit ($form, &$form_state) {
  
  if ($form_state['clicked_button']['#name'] == 'save') {
    $info = array(
      'type' => $form_state['values']['type'],
      'name' => $form_state['values']['name'],
      'description' => $form_state['values']['description'],
      'help' => $form_state['values']['help'],  
    );
  
    chado_type_save($info);
    $form_state['redirect'] = 'admin/structure/chado';
  }
  
}

/**
 * Edit content type form
 *
 * @param $form_state
 *   The state of the form on last submission
 * @return $form
 *   A form array describing the add chado content type form
 */
function tripal_core_edit_content_types_form ($form, $form_state) {
  
  // SELECT * FROM chado_type ct WHERE type=$form_state['build_info']['args'][0]
  $result = db_select('chado_type', 'ct')
    ->fields('ct')
    ->condition('type',$form_state['build_info']['args'][0])
    ->execute()
    ->fetchAssoc();
    
  $form = tripal_core_add_content_types_form(array());
  
  $form['type']['#default_value'] = $result['type'];
  $form['name']['#default_value'] = $result['name'];
  $form['description']['#default_value'] = $result['description'];
  $form['help']['#default_value'] = $result['help'];
  
  return $form;
}

/**
 * Edit content type form submit
 *
 * @param $form
 *   The form that was submitted
 * @param $form_state
 *   The state of the form when it was submitted. contains user input
 */
function tripal_core_edit_content_types_form_submit ($form, &$form_state) {

  if ($form_state['clicked_button']['#name'] == 'save') {
    $info = array(
      'type' => $form_state['values']['type'],
      'name' => $form_state['values']['name'],
      'description' => $form_state['values']['description'],
      'help' => $form_state['values']['help'],  
    );
  
    chado_type_save($info);
    $form_state['redirect'] = 'admin/structure/chado';
  }
  
}

/**
 * Delete content type form
 *
 * @param $form
 *   The original form array
 * @param $form_state
 *   The state of the form on last submission
 * @return $form
 *   A form array describing the delete chado content type form
 */
function tripal_core_delete_content_types_form ($form, $form_state) {
  
  $content_type = $form_state['build_info']['args'][0];
  
  $form['type'] = array(
    '#type' => 'hidden',
    '#value' => $content_type,
  );
  
  $form['msg'] = array(
    '#type' => 'item',
    '#markup' => 'Are you sure you want to delete the '.$content_type.' chado content type? This action cannot be undone.',
  );
  
  $form['delete'] = array(
    '#type' => 'submit',
    '#name' => 'delete',
    '#value' => t('Delete Content Type'),
  );
  
  $form['cancel'] = array(
    '#type' => 'submit',
    '#name' => 'cancel',
    '#value' => t('Cancel')
  );
  
  return $form;
}

/**
 * Delete content type form submit
 *
 * @param $form
 *   The form that was submitted
 * @param $form_state
 *   The state of the form when it was submitted. contains user input
 */
function tripal_core_delete_content_types_form_submit ($form, &$form_state) {

  if ($form_state['clicked_button']['#name'] == 'delete') {
    if (!empty($form_state['values']['type'])) {
      chado_type_delete($form_state['values']['type']);
    }
  }
  
  $form_state['redirect'] = 'admin/structure/chado';
  
}

/////////////////////////////////////////////////////////////////////////////////////////////
// Manage chado content
/////////////////////////////////////////////////////////////////////////////////////////////

/**
 *
 */
function tripal_core_add_chado_content () {
  $output = '';
  
  $results = db_select('chado_type', 'c')
    ->fields('c', array('type', 'name', 'description'))
    ->execute();
  
  $num_rows = $results->rowCount();
  
  /**
  if ($num_rows == 1) {
    $record = $results->fetch();
    drupal_goto('chado/'.$record->type.'/add');
  }
  */
  
  $content = array();
  foreach ($results as $r) {
    $type = array();
    $type['title'] = $r->name;
    $type['href'] = 'chado/'.$r->type.'/add';
    $type['description'] = $r->description;
    $content[] = $type;
  }
  
  $output .= theme('chado_add_list',array('content' => $content));
  
  if (sizeof($content) == 1) {
    drupal_goto($content[0]['href']);
  }
  
  return $output;
}

/**
 *
 */
function theme_chado_add_list ($variables) {
  $content = $variables['content'];
  $output = '';

  if ($content) {
    $output = '<ul class="admin-list">';
    foreach ($content as $item) {
      $output .= '<li class="clearfix">';
      $output .= '<span class="label">'. l($item['title'], $item['href']) . '</span>';
      $output .= '<div class="description">' . filter_xss_admin($item['description']) . '</div>';
      $output .= '</li>';
    }
    $output .= '</dl>';
  }
  else {
    $output = '<p>' 
      .t('You have not created any content types yet. Either '
      .'<a href="@enable-module">enable a tripal module</a> or go to the <a href="@create-content">'
      .'content type creation page</a> to add a new content type.', 
      array(
        '@enable-module' => url('admin/modules'),
        '@create-content' => url('admin/structure/types/add')
      )) 
      .'</p>';
  }
  return $output;
}

/**
 * Manage all chado content
 *
 * @todo Add table header sorting
 */
function tripal_core_manage_chado_content () {
  $output = '';
  
  
  $output .= '<ul class="action-links">';
    $output .= '<li>'.l('Add Chado Content', 'chado/add').'</li>';
  $output .= '</ul>';
  
  $form_array = drupal_get_form('tripal_core_manage_chado_content_form');
  $output .= drupal_render($form_array);
  
	return $output;
}

/**
 * Form which facilitates managing of chado content
 *
 * @param $form
 *   Form array (structure as specified by Form API)
 * @param $form_state
 *   The last submitted state of the form
 * @return
 *   Form array (structure as specified by Form API)
 */
function tripal_core_manage_chado_content_form ($form, &$form_state) {
  $form = array();
  
  $entity_info = entity_get_info('chado');
  
  //// Filters //////////////////////////
  $form['filters'] = array(
    '#type' => 'fieldset',
    '#title' => 'SHOW ONLY ITEMS WHERE'
  );
  
  $type_options = array();
  $type_options[0] = '<ANY>';
  foreach ($entity_info['bundles'] as $key => $bundle) {
    $type_options[$key] = $bundle['label'];
  }
  $form['filters']['chado_type'] = array(
    '#type' => 'select',
    '#title' => t('type'),
    '#options' => $type_options,
  );
  
  $created_user_options = array();
  $created_user_options[0] = '<ANY>';
  $result = db_query('SELECT created_by FROM {chado_content} GROUP BY created_by');
  foreach ($result as $r) {
    $user = user_load($r->created_by);
    $created_user_options[$r->created_by] = $user->name;
  }
  $form['filters']['created_by'] = array(
    '#type' => 'select',
    '#title' => t('created by'),
    '#options' => $created_user_options,
  );  
  
  $last_modified_user_options = array();
  $last_modified_user_options[0] = '<ANY>';
  $result = db_query('SELECT last_modified_by FROM {chado_content} GROUP BY last_modified_by');
  foreach ($result as $r) {
    $user = user_load($r->last_modified_by);
    $last_modified_user_options[$r->last_modified_by] = $user->name;
  }
  $form['filters']['last_modified_by'] = array(
    '#type' => 'select',
    '#title' => t('last_modified by'),
    '#options' => $last_modified_user_options,
  );  
  
  $form['filters']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Filter'),
    '#name' => 'filter',
  );
  
  //// Update ///////////////////////////
  $form['update'] = array(
    '#type' => 'fielset',
    '#title' => t('UPDATE OPTIONS'),
  );
  
  $form['update']['update_action'] = array(
    '#type' => 'select',
    '#options' => array(
      'delete' => t('Delete selected content'),
    ),
  );
  
  $form['update']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Update'),
    '#name' => 'update',
  );  
  
  //// Table of Content /////////////////  
  $header = array(
    'title' => t('Title'), 
    'type' => t('Type'), 
    'created_on' => t('Created On'), 
    'created_by' => t('Created By'), 
    'last_modified_by' => t('Last Modified By'),
    'operations' => t('Operations'),
  );
  
  //SELECT chado_entity_id, chado_type, chado_id, created_on, created_by, last_modified_by FROM {chado_content}
  $query = db_select('chado_content','c')->extend('PagerDefault')->extend('TableSort')
    ->fields('c')
    ->limit(50)
    ->orderBy('chado_entity_id', 'DESC') // Sort by chado_entity_id first.
    ->orderByHeader($header); // Then by what has been selected in the table header
    
  // Add filtering
  if (!empty($form_state['storage']['filter'])) {
    if (isset($form_state['storage']['filter']['type']) && $form_state['storage']['filter']['type']) {
      $query->condition('chado_type', $form_state['storage']['filter']['type']);
    }
    if (isset($form_state['storage']['filter']['created_by']) && $form_state['storage']['filter']['created_by']) {
      $query->condition('created_by', $form_state['storage']['filter']['created_by']);
    }
    if (isset($form_state['storage']['filter']['last_modified_by']) && $form_state['storage']['filter']['last_modified_by']) {
      $query->condition('last_modified_by', $form_state['storage']['filter']['last_modified_by']);
    }
  }
  $result = $query->execute();
  
  $options = array();
  $users = array();
  while($entry = $result->fetchAssoc()) {
  $entry = (object)$entry;
    if (!isset($users[$entry->created_by])) {
      $users[$entry->created_by] = user_load($entry->created_by);
    }
    if (!isset($users[$entry->last_modified_by])) {
      $users[$entry->last_modified_by] = user_load($entry->last_modified_by);
    }    
    $options[$entry->chado_entity_id] = array(
      'title' => l(chado_get_title($entry->chado_type,$entry->chado_id), 'chado/'.$entry->chado_entity_id),
      'type' => $entry->chado_type, 
      'created_on' => date(DATE_RFC2822, $entry->created_on), 
      'created_by' => $users[$entry->created_by]->name, 
      'last_modified_by' => $users[$entry->last_modified_by]->name,      
      'operations' => l('view','chado/'.$entry->chado_entity_id) 
        . ' &nbsp ' . l('edit','chado/'.$entry->chado_entity_id.'/edit') 
        . ' &nbsp ' . l('delete','chado/'.$entry->chado_entity_id.'/delete')
    );
    $form_state['storage']['entities'][$entry->chado_entity_id] = $entry;
  }

  $form['content'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
  );
  $form['pager'] = array('#markup' => theme('pager'));
  
  return $form;
}

function tripal_core_manage_chado_content_form_submit ($form, &$form_state) {
  // needed so form_state is available in form
  $form_state['rebuild'] = TRUE;
  
  switch ($form_state['clicked_button']['#name']) {
    case 'update':
      switch ($form_state['values']['update_action']) {
        case 'delete':
          $form_state['redirect'] = 'admin/chado_content/delete/'.implode(',',array_filter($form_state['values']['content']));
        break;
      }
    break;
    case 'filter':
      $form_state['storage']['filter']['type'] = $form_state['values']['chado_type'];
      $form_state['storage']['filter']['created_uid'] = $form_state['values']['created_by'];
      $form_state['storage']['filter']['last_modified_uid'] = $form_state['values']['last_modified_by'];
    break;
  }
}

/**
 * Theme function for form: tripal_core_manage_chado_content_form() 
 *
 * @param $varaiables
 *   An array of variables available to be themed. In this case just the form array
 * @return
 *   Rendered HTML content
 */
function theme_tripal_core_manage_chado_content_form ($variables) {
  $form = $variables['form']; 
  $output = '';
  
  //// Filters fieldset /////////////////
  $output .= '<div><fieldset class="form-wrapper" id="edit-filters">';
  $output .= '<legend><span class="fieldset-legend">'.$form['filters']['#title'].'</span></legend>';
  $output .= '<div class="fieldset-wrapper">';
  $output .= '<div class="exposed-filters"> <div class="clearfix form-wrapper" id="edit-status">';
  $output .= '<div class="filters form-wrapper" id="edit-filters--3">';
  $output .= drupal_render($form['filters']['chado_type']);
  $output .= drupal_render($form['filters']['created_by']);
  $output .= drupal_render($form['filters']['last_modified_by']);
  $output .= '</div>';
  $output .= '<div class="container-inline form-actions form-wrapper" id="edit-actions">';
  $output .= drupal_render($form['filters']['submit']);
  $output .= '</div>';
  $output .= '</div></div>';
  $output .= '</div>';
  $output .= '</fieldset></div>';
  unset($form['filters']);
  
  
  //// Update fieldset //////////////////
  $output .= '<fieldset class="container-inline form-wrapper" id="edit-options">';
  $output .= '<legend><span class="fieldset-legend">'.$form['update']['#title'].'</span></legend>';
  $output .= '<div class="fieldset-wrapper">';
  $output .= drupal_render($form['update']['update_action']);
  $output .= drupal_render($form['update']['submit']);
  $output .= '</div></fieldset>';
  unset($form['update']);
  
  $output .= drupal_render_children($form);
  
  return $output;
}

function tripal_core_delete_multiple_confirm_form ($form, &$form_state, $entity_ids) {
  $form = array();
  
  $entity_ids = explode(',',$entity_ids);
  $form_state['storage']['entities'] = chado_load_multiple($entity_ids);
  
  $links = array();
  foreach ( $form_state['storage']['entities'] as $chado_entity) {
    $links[] = l(chado_get_title($chado_entity->chado_type, $chado_entity->chado_id), 'chado/'.$chado_entity->chado_entity_id);
  }
  $form['item'] = array(
    '#type' => 'item',
    '#markup' => t('Are you sure you want to delete the following chado content?<br>'
      .implode(",<br>",$links)
      .'<br><br>This action cannot be un-done.'),
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

function tripal_core_delete_multiple_confirm_form_submit ($form, &$form_state) {

  switch($form_state['clicked_button']['#name']) {
    case 'delete':
      chado_delete_multiple($form_state['storage']['entities']);
    break;
  }
  
  $form_state['redirect'] = 'admin/chado_content';

}