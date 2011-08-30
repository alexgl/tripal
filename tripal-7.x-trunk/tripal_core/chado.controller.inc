<?php

class ChadoController extends DrupalDefaultEntityController{

  /**
   * Saves a chado entity
   * 
   * @param $chado_entity
   *   The chado_entity to be saved
   * @param $update
   *   Update an already existing entity?
   * @return
   *   The saved chado entity
   */
  public function save($chado_entity, $update = FALSE) {
    
    $entity_info = entity_get_info('chado');
    
    // insert new entity
    if (!$update) {
      // Save any fields attached through the Field API
      field_attach_insert('chado', $chado_entity);
      
      // Save the default chado fields
      drupal_write_record($entity_info['base table'], $chado_entity);
      
      // Tell Drupal that we have finished saving the chado entity
      module_invoke_all('entity_insert', 'chado', $chado_entity);
    } else {
      // Save any fields attached through the Field API
      field_attach_update('chado', $chado_entity);
      
      // Save the default chado fields
      drupal_write_record($entity_info['base table'], $chado_entity, 'chado_entity_id');
      
      // Tell Drupal that we have finished saving the chado entity
      module_invoke_all('entity_update', 'chado', $chado_entity);    
    }
    
    return $chado_entity;
  }
  
  /**
   * Loads multiple chado entities
   *
   * @param $ids 
   *   An array of entity IDs, or FALSE to load all entities.
   * @param $conditions 
   *   An array of conditions in the form 'field' => $value.
   * @return 
   *   An array of entity objects indexed by their ids. When no results are found, an empty array is returned.
   */
  public function load($ids = array(), $conditions = array()) {

    //Query the chado_content table
    if ($ids) {
      $entities = db_select('chado_content', 'c')
        ->fields('c')
        ->condition('chado_entity_id', $ids, 'IN')
        ->execute()
        ->fetchAllAssoc('chado_entity_id');
    }
    
    // Pass all entities loaded from the database through $this->attachLoad(),
    // which attaches fields (if supported by the entity type) and calls the
    // entity type specific load callback, for example hook_node_load().
    if (!empty($entities)) {
      $this->attachLoad($entities);
    }
    
    return $entities;
  
  }

  protected function attachLoad(&$entities) {
    
    // Create an array of entities for each content type
    $typed_entities = array();
    foreach ($entities as $id => $entity) {
      $typed_entities[$entity->chado_type][$id] = $entity;
    }
    
    // Call object type specific callbacks for each content type
    foreach ($typed_entities as $type => $entities_of_type) {
      $function = 'chado_' . $type . '_load';
      if (function_exists($function)) {
        foreach ($entities_of_type as $entity) {
          $entity->chado = $function($entity);
        }
      }
    }
  }

  /**
   * Deletes a chado entity
   * 
   * @param $chado_entity
   *   The chado_entity to be saved
   * @return
   *   The saved chado entity
   */
  public function delete($chado_entity) {
    
    $entity_info = entity_get_info('chado');
    
    // Delete any fields attached through the Field API
    field_attach_delete('chado', $chado_entity);
    
    // Save the default chado fields
    db_delete($entity_info['base table'])
      ->condition('chado_entity_id',$chado_entity->chado_entity_id)
      ->execute();
    
    // use content type-specific hooks to delete chado content
    $function = 'chado_'.$chado_entity->chado_type.'_delete';
    if (function_exists($function)) {
      $function($chado_entity);
    }
    
    // Tell Drupal that we have finished deleting the chado entity
    module_invoke_all('entity_delete', 'chado', $chado_entity);
    
    return $chado_entity;
  }  
}