<?php

namespace Drupal\tripal_chado\Plugin\Field\FieldType;

use Drupal\tripal_chado\TripalField\ChadoFieldItemBase;
use Drupal\tripal_chado\TripalStorage\ChadoVarCharStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoTextStoragePropertyType;
use Drupal\core\Form\FormStateInterface;
use Drupal\core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of Default Tripal field for sequence data.
 *
 * @FieldType(
 *   id = "chado_local_source_data_default",
 *   label = @Translation("Chado analysis source of data"),
 *   description = @Translation("The local source data used for this analysis"),
 *   default_widget = "chado_local_source_data_default_widget",
 *   default_formatter = "chado_local_source_data_default_formatter"
 * )
 */
class ChadoLocalSourceDataDefault extends ChadoFieldItemBase {

  public static $id = "chado_local_source_data_default";

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'sourcevals';
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['termIdSpace'] = 'operation';
    $settings['termAccession'] = '2945';
    $settings['fixed_value'] = TRUE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function tripalTypes($field_definition) {
    $entity_type_id = $field_definition->getTargetEntityTypeId();

    // Get the base table columns needed for this field.
    $settings = $field_definition->getSetting('storage_plugin_settings');

    // Get the property terms by using the Chado table columns they map to.
    $storage = \Drupal::entityTypeManager()->getStorage('chado_term_mapping');
    $mapping = $storage->load('core_mapping');
    $record_id_term = 'SIO:000729';

    $src_name_term = $mapping->getColumnTermId('analysis', 'sourcename');
    $src_uri_term = $mapping->getColumnTermId('analysis', 'sourceuri');
    $src_vers_term = $mapping->getColumnTermId('analysis', 'sourceversion');

    // Get the length of the database fields so we don't go over the size limit.
    // $src_name_len = $analysis_def['fields']['sourcename']['size'];
    // $src_uri_len = $analysis_def['fields']['sourceuri']['size'];
    // $src_vers_len = $analysis_def['fields']['sourceversion']['size'];
    $src_name_len = 10;
    $src_uri_len = 10;
    $src_vers_len = 10;

    // dpm($settings);    dpm($analys_def . ' ' . $src_name_term);
    // dpm($src_uri_term . ' ' . $src_vers_term);

    // Get property terms using Chado table columns they map to. Return the properties for this field.
    $properties = [];
    $properties[] = new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', $record_id_term, [
        'action' => 'store_id',
        'drupal_store' => TRUE,
        'chado_table' => 'analysis',
        'chado_column' => 'analys_id'
    ]);
    $properties[] =  new ChadoVarCharStoragePropertyType($entity_type_id, self::$id, 'sourcename', $src_name_term, $src_name_len, [
      'action' => 'store',
      'chado_table' => 'analysis',
      'chado_column' => 'sourcename'
    ]);
    $properties[] =  new ChadoVarCharStoragePropertyType($entity_type_id, self::$id, 'sourceuri', $src_uri_term, $src_uri_len, [
        'action' => 'store',
        'chado_table' => 'analysis',
        'chado_column' => 'sourceuri'
      ]);
      $properties[] =  new ChadoTextStoragePropertyType($entity_type_id, self::$id, 'sourceversion', $src_vers_term, $src_vers_len, [
        'action' => 'store',
        'chado_table' => 'analysis',
        'chado_column' => 'sourceversion'
      ]);
      return $properties;
  }
}

/*
    ------------------------------------------------------------------------------
    Sukha 3/14/2023 : The above code merged/in T4D8 code for Chado Sequence Default 
    ------------------------------------------------------------------------------
    Need to develop : local__source_data on these lines
    local__source_data	
        Source of Data (module: Tripal)	
            Analysis, Genome Assembly, Genome Annotation, InterPro Results, BLAST Results
            DEFAULT VALUE
    Schema:chado; Table:analysis ; Columns: (sourcename ; sourceversion ; sourceuri)
    --------------------------------------------------
    they will be properties in the same field. As far as I understood from what Lacey said
    When you display source, you also want to see the version and the uri
    -----------------------------------------------------------
    Widget/Formatter needs to have these interface/inputs :
        The default value for this field, used when creating new content.
        Data Source Name : The name of the source where data was obtained for this analysis.
        Data Source Version : The version number of the data source (if applicable).
        Data Source URI : The URI (e.g. web URL) where the source data can be retrieved.
    ------------------------------------------------------------------------
    name: 'analysis'
        columns:
            -   name: 'analysis_id'
                term_id: 'operation:2945'
                term_name: 'Analysis'
            -   name: 'sourcename'
                term_id: 'schema:name'
                term_name: 'name'
            -   name: 'sourceuri'
                term_id: 'data:1047'
                term_name: 'URI'
            -   name: 'sourceversion'
                term_id: 'IAO:0000129'
                term_name: 'version number'

    Each property is of a specific type such as string, text, integer, etc. 
    The function tripalTypes is used to define the property types. 
    A property type is actually an object, thus, this function returns an array of 
    property type objects.
  */
