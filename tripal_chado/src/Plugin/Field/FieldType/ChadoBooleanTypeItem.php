<?php

namespace Drupal\tripal_chado\Plugin\Field\FieldType;

use Drupal\tripal\TripalField\TripalFieldItemBase;
use Drupal\tripal\TripalStorage\BoolStoragePropertyType;
use Drupal\tripal\TripalStorage\StoragePropertyValue;
use Drupal\core\Form\FormStateInterface;
use Drupal\core\Field\FieldDefinitionInterface;
use Drupal\tripal_chado\TripalField\ChadoFieldItemBase;
use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoBoolStoragePropertyType;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;


/**
 * Plugin implementation of the 'boolean' field type for Chado.
 *
 * @FieldType(
 *   id = "chado_boolean_type",
 *   label = @Translation("Chado Boolean Field Type"),
 *   description = @Translation("A boolean field."),
 *   default_widget = "chado_boolean_type_widget",
 *   default_formatter = "chado_boolean_type_formatter",
 *   cardinality = 1
 * )
 */
class ChadoBooleanTypeItem extends ChadoFieldItemBase {

  public static $id = "chado_boolean_type";

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();
    $settings['storage_plugin_settings']['base_column'] = '';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function tripalTypes($field_definition) {
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $settings = $field_definition->getSetting('storage_plugin_settings');
    $base_table = $settings['base_table'];
    if (!$base_table) {
      return;
    }

    // Get the base table columns needed for this field.
    $base_column = $settings['base_column'];
    $chado = \Drupal::service('tripal_chado.database');
    $schema = $chado->schema();
    $base_schema_def = $schema->getTableDef($base_table, ['format' => 'Drupal']);
    $base_pkey_col = $base_schema_def['primary key'];

    // Get the property terms by using the Chado table columns they map to.
    $storage = \Drupal::entityTypeManager()->getStorage('chado_term_mapping');
    $mapping = $storage->load('core_mapping');
    $record_id_term = 'SIO:000729';
    $value_term = $mapping->getColumnTermId($base_table, $base_column);

    return [
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', $record_id_term, [
        'action' => 'store_id',
        'drupal_store' => TRUE,
        'chado_table' => $base_table,
        'chado_column' => $base_pkey_col
      ]),
      new ChadoBoolStoragePropertyType($entity_type_id, self::$id, 'value', $value_term, [
        'action' => 'store',
        'chado_table' => $base_table,
        'chado_column' => $base_column,
      ]),
    ];
  }

 /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    // Include a base column select element and associated ajax callback.
    $this->display_base_column(TRUE);

    $elements = parent::storageSettingsForm($form, $form_state, $has_data);
    $storage_settings = $this->getSetting('storage_plugin_settings');
    $base_table = $form_state->getValue(['settings', 'storage_plugin_settings', 'base_table']);

    // Base columns are limited to those appropriate for this field.
    $base_columns = $this->getTableColumns($base_table, ['boolean']);
    $elements['storage_plugin_settings']['base_column']['#options'] = $base_columns;

    return $elements;
  }

}
