<?php

namespace Drupal\tripal_chado\Plugin\Field\FieldType;

use Drupal\tripal_chado\TripalField\ChadoFieldItemBase;
use Drupal\tripal\TripalField\TripalFieldItemBase;
use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoTextStoragePropertyType;
use Drupal\core\Field\FieldStorageDefinitionInterface;
use Drupal\tripal\TripalStorage\StoragePropertyValue;
use Drupal\Core\Form\FormStateInterface;
use Drupal\core\Field\FieldDefinitionInterface;


/**
 * Plugin implementation of Tripal string field type.
 *
 * @FieldType(
 *   id = "chado_linker__prop",
 *   label = @Translation("Chado Property"),
 *   description = @Translation("Add a property or attribute to the content type."),
 *   default_widget = "chado_linker__prop_widget",
 *   default_formatter = "chado_linker__prop_formatter"
 * )
 */
class chado_linker__prop extends ChadoFieldItemBase {

  public static $id = "chado_linker__prop";

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();
    $settings['storage_plugin_settings']['base_table'] = '';
    $settings['storage_plugin_settings']['prop_table'] = '';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function tripalTypes($field_definition) {

    // Create variables for easy access to settings.
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $settings = $field_definition->getSetting('storage_plugin_settings');
    $base_table = $settings['base_table'];
    $prop_table = $settings['prop_table'];

    // If we don't have a base table then we're not ready to specify the
    // properties for this field.
    if (!$base_table) {
      return [
        new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', [
          'action' => 'store_id',
          'drupal_store' => TRUE,
        ])
      ];
    }

    // Get the base table columns needed for this field.
    $chado = \Drupal::service('tripal_chado.database');
    $schema = $chado->schema();
    $base_schema_def = $schema->getTableDef($base_table, ['format' => 'Drupal']);
    $base_pkey_col = $base_schema_def['primary key'];

    $prop_schema_def = $schema->getTableDef($prop_table, ['format' => 'Drupal']);
    $prop_fk_col = array_keys($prop_schema_def['foreign keys'][$base_table]['columns'])[0];

    // Create the property types.
    return [
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', [
        'action' => 'store_id',
        'drupal_store' => TRUE,
        'chado_table' => $base_table,
        'chado_column' => $base_pkey_col
      ]),
      new ChadoTextStoragePropertyType($entity_type_id, self::$id, 'value', [
        'action' => 'store',
        'chado_table' => $prop_table,
        'chado_column' => 'value',
        'delete_if_empty' => TRUE,
        'empty_value' => ''
      ]),
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'rank',  [
        'action' => 'store',
        'chado_table' => $prop_table,
        'chado_column' => 'rank'
      ]),
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'type_id',  [
        'action' => 'store',
        'chado_table' => $prop_table,
        'chado_column' => 'type_id'
      ]),
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'linker_id',  [
        'action' => 'store',
        'chado_table' => $prop_table,
        'chado_column' => $prop_fk_col,
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function tripalValuesTemplate($field_definition) {

    $entity = $this->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_id = $entity->id();

    return [
      new StoragePropertyValue($entity_type_id, self::$id, 'record_id', $entity_id),
      new StoragePropertyValue($entity_type_id, self::$id, 'value', $entity_id),
      new StoragePropertyValue($entity_type_id, self::$id, 'rank', $entity_id),
      new StoragePropertyValue($entity_type_id, self::$id, 'type_id', $entity_id),
      new StoragePropertyValue($entity_type_id, self::$id, 'linker_id', $entity_id),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    // We need to set the prop table for this field but we need to know
    // the base table to do that. So we'll add a new validation function so
    // we can get it and set the proper storage settings.
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);
    $elements['storage_plugin_settings']['base_table']['#element_validate'] = [[static::class, 'storageSettingsFormValidate']];
    return $elements;
  }

  /**
   * Form element validation handler
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function storageSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('settings');
    if (!array_key_exists('storage_plugin_settings', $settings)) {
      return;
    }
    $base_table = $settings['storage_plugin_settings']['base_table'];
    $prop_table = $base_table . 'prop';

    $chado = \Drupal::service('tripal_chado.database');
    $schema = $chado->schema();
    if ($schema->tableExists($prop_table)) {
      $form_state->setValue(['settings', 'storage_plugin_settings', 'prop_table'], $prop_table);
    }
    else {
      $form_state->setErrorByName('storage_plugin_settings][base_table',
          'The selected base table does not have an associated property table.');
    }
  }
}
