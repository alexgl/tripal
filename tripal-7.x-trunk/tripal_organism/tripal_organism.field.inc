<?php

/**
 * @file
 * Create a new field type: tripal_organism which contains all of the columns from
 * the chado organism table
 *
 * Currently not included in the organism module
 * To re-implement this functionality: 
 *  - uncomment the rquire for this file in tripal_organism.module
 *  - Move the function inbetween the --- START/END Move into tripal_organism.install --- into 
 *    tripal_organism.install. You might need to copy the code from the 
 *    tripal_organism_install/uninstall functions into pre-existing functions with the same name 
 *    if they're already implemented
 *
 * Known Problems:
 *  - Currently the field insert/update/delete are not working.
 *     + insert isn't working because it wants to set the first organism_id to 0 but the range
 *       for a postgresql serial starts at 1 thus 0 isn't valid
 *  - Also fields are not being correctly deleted on uninstall causing errors when you try to 
 *    re-install this module -need to manually drop field_data_field_organism & 
 *    field_revision_field_organism tables frm the drupal database.
 *     + See http://drupal.org/node/943772 for why this might be the case
 *  - Not yet tested with views or panels since I can't get any content inserted
 *
 * Reason's this was abandoned:
 *  1. Fields are inserted independently and the state of one can't be used to define another
 *     (ie: if you have a feature field and a featureprop field you can't get the feature_id
 *      from the feature field to be used in the featureprop field)
 *  2. Fields must insert a full row in their database table (if the chado feature table is used
 *      as the storage table for the feature field then it must have "columns" dbxref_id, name,
 *      uniquename, etc. (each of these columns can't be thought of as it's own field)
 *  3. We need to define our own storage backend (appears quite a bit of coding and there currently
 *      isn't very good documentation) in order to allow fields to be stored in chado rather then
 *      the drupal database and to stop the field API from trying to create this table.
 *  4. There currently doesn't seem to be a way to relate fields to each other. Although you can 
 *      define which bundles/content tpyes a field is available for (ensure you can't add a 
 *      featureprop to a stock entity), there isn't a way to indicate that a featureprop field
 *      is related to a feature field through the feature_id and that you can't have a featureprop
 *      without a feature
 *  5. Unsure how much integration with other Drupal functionality (views, panels) we'll get for
 *      using fields when we arn't using them as designed (as designed each column in a chado table 
 *      should be stored in it's own table)
 */

// --- START Move into tripal_organism.install ---

/** 
 * Implementation of hook_install() 
 */
function tripal_organism_install() {

  // Add Chado Content Type
  $info = array(
    'type' => 'organism',
    'name' => 'Organism',
    'chado_table' => 'organism',
    'title_field' => 'abbreviation',
    'description' => 'The organismal taxonomic classification. Note that phylogenies are represented using the phylogeny module, and taxonomies can be represented using the cvterm module or the phylogeny module.',
    'help' => '',
    'custom' => 0,
    'module' => 'tripal_organism',  
  );
  chado_type_save($info);

  // Create all the fields we are adding to our content type.
  // http://api.drupal.org/api/function/field_create_field/7
  foreach (_tripal_organism_installed_fields() as $field) {
    field_create_field($field);
  }

  // Create all the instances for our fields.
  // http://api.drupal.org/api/function/field_create_instance/7
  foreach (_tripal_organism_installed_instances() as $instance) {
    $instance['entity_type'] = 'chado';
    $instance['bundle'] = $info['type'];
    field_create_instance($instance);
  }  
}

/** 
 * Implementation of hook_uninstall() 
 */
function tripal_organism_uninstall() {

  // Gather all the example content that might have been created while this
  // module was enabled.  Simple selects still use db_query().
  // http://api.drupal.org/api/function/db_query/7
  $sql = 'SELECT chado_entity_id FROM {chado_content} c WHERE c.chado_type = :type';
  $result = db_query($sql, array(':type' => 'organism'));
  $ids = array();
  foreach ($result as $row) {
    $ids[] = $row->chado_entity_id;
  }

  // Delete all the nodes at once
  // http://api.drupal.org/api/function/node_delete_multiple/7
  chado_delete_multiple($ids);
  
  // Loop over each of the fields defined by this module and delete
  // all instances of the field, their data, and the field itself.
  // http://api.drupal.org/api/function/field_delete_field/7
  foreach (array_keys(_tripal_organism_installed_fields()) as $field) {
    field_delete_field($field);
  }

  // Loop over any remaining field instances attached to the node_example
  // content type (such as the body field) and delete them individually.
  // http://api.drupal.org/api/function/field_delete_field/7
  $instances = field_info_instances('chado', 'organism');
  foreach ($instances as $instance_name => $instance) {
    field_delete_instance($instance);
  }  
  
  // Purge all field information
  // http://api.drupal.org/api/function/field_purge_batch/7
  field_purge_batch(1000);

	chado_type_delete('organism');
	
}

/**
 * Returns a structured array defining the fields created by this content type.
 * 
 * NOTE: Each element in this array will be passed to field_create_field($field)
 * where $field is an array with any of the following keys (* required)
 *  - field_name*: of the form field_<uniquename> with max length of 32 characters
 *  - type*:  machine name of the field
 *  - cardinality: the number of values the field can hold
 *  - locked: whether or not the fields is available for editing
 *  - indexes: An array of indexes on data columns, using the same definition format as Schema API index specifications
 *  - settings: A sub-array of key/value pairs of field-type-specific settings. 
 *       Each field type module defines and documents its own field settings
 *  - storage: A sub-array of key/value pairs identifying the storage backend to use for the for the field.
 *
 * This is packaged in a function so it can be used in both
 * node_example_install() and node_example_uninstall().
 */
function _tripal_organism_installed_fields() {
  $t = get_t();
  return array(
    'tripal_organism_organism' => array(
      'field_name'  => 'field_organism',
      'type'        => 'tripal_organism_organism',
      'cardinality' => 1,
      'locked'      => TRUE,
    ),
  );
}

/**
 * Returns a structured array defining the instances for this content type.
 *
 * The instance lets Drupal know which widget to use to allow the user to enter
 * data and how to react in different view modes.  
 *
 * NOTE: Each element in this array will be passed to field_create_instance($instance)
 * where $instance is an array with any of the following keys (* required)
 *  - field_name*: the field_name of the field to use
 *  - label: A human-readable label for the field when used with this bundle. For example, the 
 *      label will be the title of Form API elements for this instance.
 *  - description: A human-readable description for the field when used with this bundle. 
 *  - required: whether or not the field is required
 *  - default_value: An array of default values keyed by field column name
 *  - settings: A sub-array of key/value pairs of field-type-specific instance settings. Each 
 *      field type module defines and documents its own instance settings.
 *  - widget: A sub-array of key/value pairs identifying the Form API input widget for the 
 *      field when used by this bundle.
 *  - display: A sub-array of key/value pairs identifying the way field values should be 
 *      displayed in each of the entity type's view modes, plus the 'default' mode.
 *
 * This is provided as a function so that it can be used in both hook_install()
 * and hook_uninstall().
 */
function _tripal_organism_installed_instances() {
  $t = get_t();
  return array(
    'field_organism' => array(
      'field_name' => 'field_organism',
      'label'       => $t('Organism'),
      'widget'      => array(
        'type'    => 'default',
      ),
      'display' => array(
        'default' => array(
          'label' => 'inline',
        ),
      ),
    ),
  );
}

/**
 * Implements hook_field_schema
 *
 * Tells drupal how the tripal_organism_organism field should be stored
 */
function tripal_organism_field_schema($field) {

  $columns = array();
  
  $columns['organism_id'] = array(
    'type' => 'serial',
    'not null' => TRUE,
    'unsigned' => TRUE,
  );
  
  $columns['abbreviation'] = array(
    'type' => 'varchar',
    'length' => 255,
  );
  
  $columns['genus'] = array(
    'type' => 'varchar',
    'length' => 255,
    'not null' => TRUE,
  );
  
  $columns['species'] = array(
    'type' => 'varchar',
    'length' => 255,
    'not null' => TRUE,
  );

  $columns['common_name'] = array(
    'type' => 'text',
  );
  
  return array(
    'columns' => $columns,
    'indexes' => array( 
      'organism_id' => array('organism_id'),
      'scientific_name' => array('genus', 'species'),
    ),
  );
}

// --- END Move into tripal_organism.install ---

/**
 * Implements hook_field_info
 *
 * Tells drupal about the tripal organism field type
 */
function tripal_organism_field_info() {
  return array(
    'tripal_organism_organism' => array(
      'label' => t('Chado Organism'),
      'description' => t('The organismal taxonomic classification.'),
      'default_widget' => 'tripal_organism_organism_widget',
      'default_formatter' => 'tripal_organism_organism_formatter',
    ),
  );
}

/**
 * Implements hook_field_widget_info
 *
 * Tells field API about your new widget type which makes this widget appear in 
 * the list of field types on any manage fields page
 */
function tripal_organism_field_widget_info() {
  return array(
    'tripal_organism_organism_widget' => array(
      'label' => t('Default'),
      'field types' => array('tripal_organism_organism'),
    ),
  );
}

/**
 * Implements hook_field_widget_form
 *
 * Describes the form that should be used when users are entering data for this field
 *
 * @param $form 
 *   The form structure where widgets are being attached to. This might be a full form structure, or a sub-element of a larger form.
 * @param $form_state 
 *   An associative array containing the current state of the form.
 * @param $field 
 *   The field structure.
 * @param $instance 
 *   The field instance.
 * @param $langcode 
 *   The language associated with $items.
 * @param $items 
 *   Array of default values for this field.
 * @param $delta 
 *   The order of this item in the array of subelements (0, 1, 2, etc).
 * @param $element 
 *   A form element array containing basic properties for the widget:
 *   - #entity_type: The name of the entity the field is attached to.
 *   - #bundle: The name of the field bundle the field is contained in.
 *   - #field_name: The name of the field.
 *   - #language: The language the field is being edited in.
 *   - #field_parents: The 'parents' space for the field in the form. Most widgets can simply overlook this property. This identifies the location where the field values are placed within $form_state['values'], and is used to access processing information for the field through the field_form_get_state() and field_form_set_state() functions.
 *   - #columns: A list of field storage columns of the field.
 *   - #title: The sanitized element label for the field instance, ready for output.
 *   - #description: The sanitized element description for the field instance, ready for output.
 *   - #required: A Boolean indicating whether the element value is required; for required multiple value fields, only the first widget's values are required.
 *   - #delta: The order of this item in the array of subelements; see $delta above.
 *
 * @return
 *   The form elements for a single widget for this field.
 */
function tripal_organism_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {

  $element['#type'] = 'fieldset';
  $required = $element['#required'];
  $item =& $items[$delta];
  
  $element['abbreviation'] = array(
    '#type' => 'textfield',
    '#title' => t('Abbreviation'),
    '#description' => t('The scientific abbreviation for this organism'),
    '#default_value' => isset($item['abbreviation']) ? $item['abbreviation'] : '',
  );
  
  $element['genus'] = array(
    '#type' => 'textfield',
    '#title' => t('Genus'),
    '#required' => $required,
    '#description' => t('The genus part of this organism scientific name'),
    '#default_value' => isset($item['genus']) ? $item['genus'] : '',
  );

  $element['species'] = array(
    '#type' => 'textfield',
    '#title' => t('Species'),
    '#required' => $required,
    '#description' => t('The species part of this organisms scientific name'),
    '#default_value' => isset($item['species']) ? $item['species'] : '',
  );

  $element['common_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Common Name'),
    '#description' => t('An accepted common name for this organism'),
    '#default_value' => isset($item['common_name']) ? $item['common_name'] : '',
  );

  $element['comment'] = array(
    '#type' => 'textarea',
    '#title' => t('Comment'),
    '#description' => t('Any notes or further information about this organism'),
    '#default_value' => isset($item['comment']) ? $item['comment'] : '',
  );
  
  return $element;
}

/**
 * Implements hook_field_is_empty
 *
 * Tells the Field API when your field is empty.
 * In our case, an organism field is empty if the required fields 
 * (genus, species) are not filled in
 *
 * @param $item 
 *   An item that may or may not be empty.
 * @param $field 
 *   The field to which $item belongs.
 * 
 * @return
 * TRUE if the field is empty and FALSE otherwise
 */
function tripal_organism_field_is_empty ($item, $field) {
  $is_empty = TRUE;
  
  if (!empty($item['genus']) && !empty($item['species'])) {
    $is_empty = FALSE;
  }
  
  return $is_empty;
}

/**
 * hook_field_schema is implemented in tripal_organism.install
 * to tell Drupal to create the table needed to store this field
 */