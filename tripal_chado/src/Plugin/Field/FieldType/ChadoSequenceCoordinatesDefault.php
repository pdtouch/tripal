<?php

namespace Drupal\tripal_chado\Plugin\Field\FieldType;

use Drupal\tripal_chado\TripalField\ChadoFieldItemBase;
use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;

/**
 * Plugin implementation of Default Tripal field for sequence data.
 *
 * @FieldType(
 *   id = "chado_sequence_coordinates_default",
 *   label = @Translation("Chado Sequence Coordinates"),
 *   description = @Translation("Locations on reference sequences where the feature is located"),
 *   default_formatter = "chado_sequence_coordinates_formatter_default",
 *   cardinality = 1,
 * )
 */
class ChadoSequenceCoordinatesDefault extends ChadoFieldItemBase {

  public static $id = "chado_sequence_coordinates_default";

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'sequence_coordinates';
  }

  /**
  * {@inheritdoc}
  */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['termIdSpace'] = 'data';
    $settings['termAccession'] = '2012';
    return $settings;
  }

  /**  
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();
    $settings['storage_plugin_settings']['base_table'] = 'feature';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function tripalTypes($field_definition) {

    // Create variables for easy access to settings.
    $entity_type_id = $field_definition->getTargetEntityTypeId();

    $field_settings = $field_definition->getSetting('storage_plugin_settings');
    $base_table = $field_settings['base_table'];

    if (!$base_table) {
      $record_id_term = 'data:3002';
      return [
        new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', $record_id_term, [
          'action' => 'store_id',
          'drupal_store' => TRUE,
        ])
      ];
    }

    // Get the property terms by using the Chado table columns they map to.
    $storage = \Drupal::entityTypeManager()->getStorage('chado_term_mapping');
    $mapping = $storage->load( 'core_mapping' );

    $record_id_term = $mapping->getColumnTermId( 'feature', 'feature_id' );
    $ftloc_id_term = $mapping->getColumnTermId('featureloc', 'featureloc_id');

    $fmin_term = $mapping->getColumnTermId('featureloc', 'fmin');
    $fmax_term = $mapping->getColumnTermId('featureloc', 'fmax');
    $strand_term = $mapping->getColumnTermId('featureloc', 'strand');
    $phase_term = $mapping->getColumnTermId('featureloc', 'phase');

    // Get property terms using Chado table columns they map to. Return the properties for this field.
    $properties = [];

    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', $record_id_term, [
      'action' => 'store_id',
      'drupal_store' => TRUE,
      'chado_table' => 'feature',
      'chado_column' => 'feature_id',
    ]);
    
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'featureloc_id', $record_id_term, [
      'action' => 'store_id',
      'drupal_store' => TRUE,
      'chado_table' => 'featureloc',
      'chado_column' => 'featureloc_id',
    ]);
    
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'fmin', $fmin_term, [
        'action' => 'store',
        'path' => 'feature.feature_id>featureloc.feature_id',
        'chado_column' => 'fmin',
    ]);
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'fmax', $fmax_term, [
      'action' => 'store',
      'path' => 'feature.feature_id>featureloc.feature_id',
      'chado_column' => 'fmax',
    ]);
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'strand', $strand_term, [
      'action' => 'store',
      'path' => 'feature.feature_id>featureloc.feature_id',
      'chado_column' => 'strand',
    ]);
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'phase', $phase_term, [
      'action' => 'store',
      'path' => 'feature.feature_id>featureloc.feature_id',
      'chado_column' => 'phase',
    ]);
      
    return($properties);    
  }

}