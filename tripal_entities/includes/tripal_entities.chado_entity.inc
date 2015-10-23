<?php

/**
 *
 * Implements hook_entity_load().
 */
function tripal_entities_entity_presave($entity, $type) {

}
/**
 *
 * @param $entity
 * @param $type
 */
function tripal_entities_entity_postsave($entity, $type) {
  // Set the title for this entity using the chado data.
  $title = chado_get_entity_title($entity);
  $ec = new TripalEntityController($entity->type);
  $ec->setTitle($entity, $title);
}
/**
 *
 * Implements hook_entity_load().
 */
function tripal_entities_entity_load($entities, $type) {

}

/**
 *
 * Implements hook_entity_insert().
 */
function tripal_entities_entity_insert($entity, $type) {

}

/**
 *
 * Implements hook_entity_update().
 */
function tripal_entities_entity_update($entity, $type) {

}

/**
 *
 * Implements hook_entity_delete().
 */
function tripal_entities_entity_delete($entity, $type) {
  $record = db_select('chado_entity', 'ce')
   ->fields('ce', array('chado_entity_id', 'data_table', 'record_id'))
   ->condition('entity_id', $entity->id)
   ->execute()
   ->fetchObject();

  if ($record && property_exists($record, 'chado_entity_id')) {
    // Delete the corresponding record in Chado
    $table = $record->data_table;
    $record_id = $record->record_id;
    chado_delete_record($table, array($table . '_id' => $record_id));

    //Delete the record in the public.chado_entity table
    $sql = "DELETE FROM {chado_entity} WHERE chado_entity_id = :id";
    db_query($sql, array(':id' => $record->chado_entity_id));
  }
}

/**
 *
 * @param unknown $display
 * @param unknown $context
 */
function tripal_entities_field_widget_form_alter(&$element, &$form_state, $context) {
  // The timelastmodified field exists in many Chado tables.  We want
  // the form element to update to the most recent time rather than the time
  // in the database.
  if (array_key_exists('#field_name', $element)) {
    $field_name = $element['#field_name'];
    $matches = array();
    if (preg_match('/(.+?)__(.+?)$/', $field_name, $matches)) {
      $tablename = $matches[1];
      $colname = $matches[2];
      $schema = chado_get_schema($tablename);
      if ($colname == 'timelastmodified' and
        $schema['fields'][$colname]['type'] == 'datetime') {
        $element['#default_value']['value'] = format_date(time(), 'custom', "Y-m-d H:i:s");
        $element['#date_items']['value'] = $element['#default_value']['value'];
      }
    }
  }
}
/**
 * Implements hook_chado_field_alter().
 *
 */
function tripal_entities_chado_field_alter(&$field) {

  // Here we provide new field types and widgets for FK fields
  // and fields that need special attention.
  if ($field['chado_column'] =='organism_id') {
    $field['field_type'] = 'organism_id';
    $field['widget_type'] = 'tripal_fields_organism_select_widget';
    $field['label'] = 'Organism';
    $field['description'] = 'Select an organism.';
  }
  else if ($field['chado_column'] =='dbxref_id') {
    $field['field_type'] = 'dbxref_id';
    $field['widget_type'] = 'tripal_fields_primary_dbxref_widget';
    $field['label'] = 'Primary Cross Reference';;
    $field['description'] = 'This record can be cross-referenced with a record in another online database. This field is intended for the most prominent reference.  At a minimum, the database and accession must be provided.';
  }
  else if ($field['chado_table'] == 'feature' and
    $field['chado_column'] == 'md5checksum') {
    $field['field_type'] = 'md5checksum';
    $field['widget_type'] = 'tripal_fields_md5checksum_checkbox_widget';
    $field['label'] = 'MD5 Checksum';
    $field['description'] = 'Generating MD5 checksum for the sequence.';
  }
  else if ($field['chado_table'] == 'feature' and $field['chado_column'] == 'seqlen') {
    $field['field_type'] = 'seqlen';
    $field['widget_type'] = 'tripal_fields_seqlen_hidden_widget';
    $field['label'] = 'Seqlen';
    $field['description'] = 'The length of the residues.';

  }
  else if ($field['chado_table'] == 'feature' and $field['chado_column'] == 'residues') {
    $field['field_type'] = 'residues';
    $field['widget_type'] = 'tripal_fields_residues_textarea_widget';
    $field['label'] = 'Residues';
    $field['description'] = 'Please provide an IUPAC compatible residues for this feature. Spaces and new lines are allowed.';
  }
}

/**
 * Determines whether the given user has access to a tripal data entity.
 *
 * @param $op
 *   The operation being performed. One of 'view', 'update', 'create', 'delete'
 *   or just 'edit' (being the same as 'create' or 'update').
 * @param $entity
 *   Optionally a tripal data entity or a tripal data type to check access for.
 *   If nothing is given, access for all types is determined.
 * @param $account
 *   The user to check for. Leave it to NULL to check for the global user.
 * @return boolean
 *   Whether access is allowed or not.
 */
function tripal_entities_entity_access($op, $entity = NULL, $account = NULL) {
  if (user_access('administer tripal data', $account)) {
    return TRUE;
  }
  if (isset($entity) && $type_name = $entity->type) {
    $op = ($op == 'view') ? 'view' : 'edit';
    if (user_access("$op any $type_name data", $account)) {
      return TRUE;
    }
  }
  return FALSE;
}

/**
 * Menu callback to display an entity.
 *
 * As we load the entity for display, we're responsible for invoking a number
 * of hooks in their proper order.
 *
 * @see hook_entity_prepare_view()
 * @see hook_entity_view()
 * @see hook_entity_view_alter()
 */
function tripal_entities_view_entity($entity, $view_mode = 'full') {
  $content = '';
  $controller = entity_get_controller($entity->type);
  $content = $controller->view(array($entity->id => $entity));
  drupal_set_title($entity->title);
  return $content;
}

/**
 * Menu title callback for showing individual entities
 */
function tripal_entities_entity_title($entity){
  if ($entity) {
    return $entity->title;
  }
}