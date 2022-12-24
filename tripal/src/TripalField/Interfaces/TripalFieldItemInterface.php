<?php

namespace Drupal\tripal\TripalField\Interfaces;

use Drupal\Core\Field\FieldItemInterface;

interface TripalFieldItemInterface extends FieldItemInterface {

  /**
   * Returns the tripal storage plugin id for this field.
   *
   * @return string
   *   The tripal storage plugin id.
   */
  public function tripalStorageId();

  /**
   * Returns the property types required by this field.
   *
   * @param  object $field_definition
   *   The field configuration object. This can be an instance of:
   *   \Drupal\field\Entity\FieldStorageConfig or
   *   \Drupal\field\Entity\FieldConfig
   *
   * @return array
   *   Array of \Drupal\tripal\TripalStorage\StoragePropertyTypeBase property types.
   */
  public static function tripalTypes($field_definition);

  /**
   * Returns an empty template array of all property values this field uses for loading and saving.
   *
   * @param  object $field_definition
   *   The field configuration object. This can be an instance of:
   *   \Drupal\field\Entity\FieldStorageConfig or
   *   \Drupal\field\Entity\FieldConfig
   *
   * @return array
   *   Array of \Drupal\tripal\TripalStorage\StoragePropertyValue property value templates.
   */
  public function tripalValuesTemplate($field_definition);

  /**
   * Loads the values from the given array of properties to the given entity.
   *
   *
   * @param \Drupal\tripal\TripalField\Interfaces\TripalFieldItemInterface $field_item
   *   The field item for which properties should be saved.
   *
   * @param string $field_name
   *   The name of the field.
   *
   * @param array $prop_types
   *   Array of \Drupal\tripal\TripalStorage\\StoragePropertyType objects.
   *
   * @param array $prop_values
   *   Array of \Drupal\tripal\TripalStorage\\StoragePropertyValue objects.
   *
   * @param \Drupal\tripal\TripalStorage\TripalEntityBase $entity
   *   The entity.
   */
  public function tripalLoad($field_item, $field_name, $prop_types, $prop_values, $entity);

  /**
   * Saves the values to the given array of properties from the given entity.
   *
   * @param \Drupal\tripal\TripalField\Interfaces\TripalFieldItemInterface $field_item
   *   The field item for which properties should be saved.
   *
   * @param string $field_name
   *   The name of the field.
   *
   * @param array $prop_types
   *   Array of \Drupal\tripal\TripalStorage\\StoragePropertyType objects.
   *
   * @param array $prop_values
   *   Array of \Drupal\tripal\TripalStorage\\StoragePropertyValue objects.
   *
   * @param \Drupal\tripal\TripalStorage\TripalEntityBase $entity
   *   The entity.
   */
  public function tripalSave($field_item, $field_name, $prop_types, $prop_values, $entity);

  /**
   * Clears all field values from the given entity.
   *
   * This is to prevent Drupal from storing field values when they are
   * being stored in the Tripal field storage backend.
   *
   * @param \Drupal\tripal\TripalField\Interfaces\TripalFieldItemInterface $field_item
   *   The field item for which properties should be saved.
   *
   * @param string $field_name
   *   The name of the field.
   *
   * @param array $prop_types
   *   Array of \Drupal\tripal\TripalStorage\\StoragePropertyType objects.
   *
   * @param array $prop_values
   *   Array of \Drupal\tripal\TripalStorage\\StoragePropertyValue objects.
   *
   * @param \Drupal\tripal\TripalStorage\TripalEntityBase $entity
   *   The entity.
   */
  public function tripalClear($field_item, $field_name, $prop_types, $prop_values, $entity);
}
