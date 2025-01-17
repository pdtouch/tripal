<?php

namespace Drupal\tripal_chado\Plugin\Field\FieldType;

use Drupal\tripal_chado\TripalField\ChadoFieldItemBase;
use Drupal\tripal_chado\TripalStorage\ChadoVarCharStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoBoolStoragePropertyType;
use Drupal\tripal\Entity\TripalEntityType;
use Drupal\core\Form\FormStateInterface;

/**
 * Plugin implementation of Tripal string field type.
 *
 * @FieldType(
 *   id = "chado_synonym_type_default",
 *   category = "tripal_chado",
 *   label = @Translation("Chado Synonym"),
 *   description = @Translation("A chado syonym"),
 *   default_widget = "chado_synonym_widget_default",
 *   default_formatter = "chado_synonym_formatter_default"
 * )
 */
class ChadoSynonymTypeDefault extends ChadoFieldItemBase {

  public static $id = "chado_synonym_type_default";

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'name';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();
    $settings['storage_plugin_settings']['linker_table'] = '';
    $settings['storage_plugin_settings']['linker_fkey_column'] = '';
    return $settings;
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['termIdSpace'] = 'schema';
    $settings['termAccession'] = 'alternateName';
    return $settings;
  }


  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
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
    // For Drupal ≥10.2 our values are now in the subform
    $drupal_10_2 = $form_state->getValue(['field_storage']);
    if ($drupal_10_2) {
      $settings = $form_state->getValue(['field_storage', 'subform', 'settings']);
    }
    else {
      $settings = $form_state->getValue(['settings']);
    }
    if (!array_key_exists('storage_plugin_settings', $settings)) {
      return;
    }

    // Check if a corresponding synonym table exists for the base table.
    $base_table = $settings['storage_plugin_settings']['base_table'];
    $linker_table = $base_table . '_synonym';
    $chado = \Drupal::service('tripal_chado.database');
    $schema = $chado->schema();
    $linker_table_def = $schema->getTableDef($linker_table, ['format' => 'Drupal']);
    if (!$linker_table_def) {
      $form_state->setErrorByName('storage_plugin_settings][linker_table',
          'The selected base table cannot support synonyms.');
    }
    else {
      $linker_fkey_column = array_keys($linker_table_def['foreign keys'][$base_table]['columns'])[0];
      if ($drupal_10_2) {
        $form_state->setvalue(['field_storage', 'subform', 'settings', 'storage_plugin_settings', 'linker_table'], $linker_table);
        $form_state->setvalue(['field_storage', 'subform', 'settings', 'storage_plugin_settings', 'linker_fkey_column'], $linker_fkey_column);
      }
      else {
        $form_state->setvalue(['settings', 'storage_plugin_settings', 'linker_table'], $linker_table);
        $form_state->setvalue(['settings', 'storage_plugin_settings', 'linker_fkey_column'], $linker_fkey_column);
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public static function tripalTypes($field_definition) {
    $entity_type_id = $field_definition->getTargetEntityTypeId();

    // Get the settings for this field.
    $storage_settings = $field_definition->getSetting('storage_plugin_settings');
    $base_table = $storage_settings['base_table'];
    $linker_table = $storage_settings['linker_table'] ?? '';
    $linker_fkey_column = $storage_settings['linker_fkey_column'] ?? '';

    // If we don't have a base table then we're not ready to specify the
    // properties for this field.
    if (!$base_table or !$linker_table) {
      return;
    }

    // Determine the primary key of the base table.
    $chado = \Drupal::service('tripal_chado.database');
    $schema = $chado->schema();
    $base_table_def = $schema->getTableDef($base_table, ['format' => 'Drupal']);
    $base_pkey_col = $base_table_def['primary key'];
    $synonym_table_def = $schema->getTableDef('synonym', ['format' => 'Drupal']);
    $linker_table_def = $schema->getTableDef($linker_table, ['format' => 'Drupal']);
    $linker_table_pkey = $linker_table_def['primary key'];
    $cvterm_table_def = $schema->getTableDef('cvterm', ['format' => 'Drupal']);

    // Create variables to store the terms for the properties. We can use terms
    // from Chado tables if appropriate.
    $storage = \Drupal::entityTypeManager()->getStorage('chado_term_mapping');
    $mapping = $storage->load('core_mapping');

    // Synonym table fields
    $syn_name_term = $mapping->getColumnTermId('synonym', 'name') ?: 'schema:name';
    $syn_name_len = $synonym_table_def['fields']['name']['size'];
    $syn_type_id_term = $mapping->getColumnTermId('synonym', 'type_id') ?: 'schema:additionalType';
    $syn_type_name_len = $cvterm_table_def['fields']['name']['size'];

    // Synonym linker table fields
    $linker_fkey_id_term = $mapping->getColumnTermId($linker_table, $linker_fkey_column) ?: self::$record_id_term;
    $linker_synonym_id_term = $mapping->getColumnTermId($linker_table, 'synonym_id') ?: 'schema:alternateName';
    $linker_is_current_term = $mapping->getColumnTermId($linker_table, 'is_current') ?: 'local:is_current';
    $linker_is_internal_term = $mapping->getColumnTermId($linker_table, 'is_internal') ?: 'local:is_internal';
    $linker_pub_id_term = $mapping->getColumnTermId($linker_table, 'pub_id') ?: 'schema:publication';

    // Always store the record id of the base record that this field is
    // associated with in Chado.
    $properties = [];
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', self::$record_id_term, [
      'action' => 'store_id',
      'drupal_store' => TRUE,
      'path' => $base_table . '.' . $base_pkey_col,
    ]);

    //
    // Properties corresponding to the synonym linker table.
    //
    // E.g. feature_synonym.feature_synonym_id
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'linker_pkey_id', $linker_synonym_id_term, [
      'action' => 'store_pkey',
      'drupal_store' => TRUE,
      'path' => $base_table . '.' . $base_pkey_col . '>' . $linker_table . '.' . $linker_table_pkey,
    ]);
    // E.g. feature.feature_id => feature_synonym.feature_id
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'linker_base_fkey_id' , $linker_fkey_id_term, [
      'action' => 'store_link',
      'drupal_store' => TRUE,
      'path' => $base_table . '.' . $base_pkey_col . '>' . $linker_table . '.' . $linker_fkey_column,
    ]);
    // E.g. feature_synonym.synonym_id
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'linker_synonym_fkey_id' , $linker_fkey_id_term, [
      'action' => 'store',
      'drupal_store' => TRUE,
      'path' => $linker_table . '.synonym_id',
    ]);
    // E.g. feature_synonym.is_current
    $properties[] = new ChadoBoolStoragePropertyType($entity_type_id, self::$id, 'is_current', $linker_is_current_term, [
      'action' => 'store',
      'path' => $linker_table . '.is_current',
      'drupal_store' => FALSE,
      'empty_value' => TRUE
    ]);
    // E.g. feature_synonym.is_internal
    $properties[] = new ChadoBoolStoragePropertyType($entity_type_id, self::$id, 'is_internal', $linker_is_internal_term, [
      'action' => 'store',
      'path' => $linker_table . '.is_internal',
      'drupal_store' => FALSE,
      'empty_value' => FALSE
    ]);
    // E.g. feature_synonym.pub_id
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'linker_pub_id' , $linker_pub_id_term, [
      'action' => 'store',
      'path' => $linker_table . '.pub_id',
      'drupal_store' => FALSE,
    ]);

    //
    // Properties corresponding to the synonym table.
    //
    // E.g. feature_synonym.synonym_id>synonym.synonym_id : synonym.name as synonym_name
    $properties[] = new ChadoVarCharStoragePropertyType($entity_type_id, self::$id, 'name', $syn_name_term, $syn_name_len, [
      'action' => 'read_value',
      'path' => $linker_table . '.synonym_id>synonym.synonym_id;name',
      'as' => 'synonym_name',
      'drupal_store' => FALSE,
    ]);
    // E.g. feature_synonym.synonym_id>synonym.synonym_id;synonym.type_id>cvterm.cvterm_id : cvterm.name as synonym_type
    $properties[] = new ChadoVarCharStoragePropertyType($entity_type_id, self::$id, 'synonym_type', $syn_type_id_term, $syn_type_name_len, [
      'action' => 'read_value',
      'path' => $linker_table . '.synonym_id>synonym.synonym_id;synonym.type_id>cvterm.cvterm_id;name',
      'as' => 'synonym_type',
      'drupal_store' => FALSE,
    ]);

    return $properties;
  }

  /**
   * {@inheritDoc}
   * @see \Drupal\tripal_chado\TripalField\ChadoFieldItemBase::isCompatible()
   */
  public function isCompatible(TripalEntityType $entity_type) : bool {
    $compatible = TRUE;

    // Get the base table for the content type.
    $base_table = $entity_type->getThirdPartySetting('tripal', 'chado_base_table');
    $linker_tables = $this->getLinkerTables('synonym', $base_table);
    if (count($linker_tables) < 1) {
      $compatible = FALSE;
    }
    return $compatible;
  }

}
