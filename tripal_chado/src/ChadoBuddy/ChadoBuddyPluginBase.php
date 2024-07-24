<?php

namespace Drupal\tripal_chado\ChadoBuddy;

use Drupal\Component\Plugin\PluginBase;
use Drupal\tripal_chado\Database\ChadoConnection;
use Drupal\tripal_chado\ChadoBuddy\Interfaces\ChadoBuddyInterface;
use Drupal\tripal_chado\ChadoBuddy\Exceptions\ChadoBuddyException;
use Drupal\tripal_chado\ChadoBuddy\ChadoBuddyRecord;

/**
 * Base class for chado_buddy plugins.
 */
abstract class ChadoBuddyPluginBase extends PluginBase implements ChadoBuddyInterface {

  /**
   * Provides the TripalDBX connection to chado that this ChadoBuddy should act upon.
   * @var Drupal\tripal_chado\Database\ChadoConnection
   *
   */
  public ChadoConnection $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ChadoConnection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description(): string {
    // Cast the description to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * Used to validate input arrays to various buddy functions,
   * or to generate a valid array subset.
   * The output has the column aliases de-aliased to the
   * actual chado column names, e.g. ['db_name' => 'db.name']
   * indicates that the alias 'db_name' corresponds to the
   * column 'name' in the 'db' table.
   *
   * @param array $user_values
   *   An associative array to be validated.
   * @param array $valid_values
   *   An associative array listing all valid keys, and the
   *   values are un-aliased database table alias and table
   *   column as described above.
   * @param bool $filter
   *   Set to TRUE if we want to return a subset of the passed
   *   $uservalues containing only keys from $validvalues.
   *   If FALSE, then a ChadoBuddyException is thrown for invalid keys.
   *
   * @return array
   *   A filtered subset of $user_values
   */
  protected function validateInput(array $user_values, array $valid_values, bool $filter = FALSE) {
    $subset = [];
    foreach ($user_values as $key => $value) {
      if (!array_key_exists($key, $valid_values)) {
        if (!$filter) {
          $calling_function = debug_backtrace()[1]['function'];
          throw new ChadoBuddyException("ChadoBuddy $calling_function error, value \"$key\" is not valid for for this function.");
        }
      }
      else {
        $mapping = $valid_values[$key];  // e.g. 'db_name' => 'db.name'
        $parts = explode('.', $mapping);  // Remove table name or alias before period
        $subset[$parts[1]] = $value;
      }
    }
    if (!$subset) {
      $calling_function = debug_backtrace()[1]['function'];
      throw new ChadoBuddyException("ChadoBuddy $calling_function error, no valid values were specified.");
    }
    return $subset;
  }

  /**
   * Used to validate results from a buddy function,
   * to ensure there is exactly one record present.
   *
   * @param mixed $output_records
   *   Might be FALSE, a ChadoBuddyRecord, or an array with multiple records.
   *   To be valid, must be exactly one ChadoBuddyRecord.
   * @param array $values
   *   Pass query values to print if exception is thrown.
   *
   * @throws ChadoBuddyException if not exactly one record.
   */
  protected function validateOutput($output_records, array $values) {
    // These are unlikely cases, but you never know.
    if (!$output_records) {
      $calling_function = debug_backtrace()[1]['function'];
      throw new ChadoBuddyException("ChadoBuddy $calling_function error, did not retrieve the expected record\n"
                                   . print_r($values, TRUE));
    }
    if (is_array($output_records)) {
      $calling_function = debug_backtrace()[1]['function'];
      $n = count($output_records);
      throw new ChadoBuddyException("ChadoBuddy $calling_function error, more than one record ($n) was retrieved, only one was expected\n"
                                   . print_r($values, TRUE));
    }
  }

}
