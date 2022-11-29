<?php
namespace Drupal\Tests\tripal_chado\Functional;

use Drupal\KernelTests\KernelTestBase;
use Drupal\tripal\TripalDBX\TripalDbx;
use Drupal\tripal_chado\Database\ChadoConnection;

/**
 * This is a base class for Chado tests.
 *
 * It enables Chado tests schemas and helper functions to efficiently perform
 * tests.
 *
 * Example:
 * @code
 * // Gets a Chado test schema with dummy data:
 * $biodb = $this->getTestSchema(ChadoTestKernelBase::INIT_CHADO_DUMMY);
 * //... do some tests
 * // After all is done, remove the schema properly:
 * $this->freeTestSchema($biodb);
 * // Note: if a test fails, the tearDownAfterClass will remove unremoved
 * // schemas.
 * @endcode
 *
 * @group Tripal
 * @group Tripal Chado
 */
abstract class ChadoTestKernelBase extends KernelTestBase {

  use ChadoTestTrait;

  protected static $modules = ['tripal', 'tripal_biodb', 'tripal_chado'];


  /**
   * {@inheritdoc}
   */

   /**
    * Just get a free test schema name.
    */
   public const SCHEMA_NAME_ONLY = 0;

   /**
    * Create an empty schema.
    */
   public const CREATE_SCHEMA = 1;

   /**
    * Create a schema and initialize it with dummy data.
    */
   public const INIT_DUMMY = 2;

   /**
    * Create a Chado schema with default data.
    */
   public const INIT_CHADO_EMPTY = 3;

   /**
    * Create a Chado schema and initialize it with dummy data.
    */
   public const INIT_CHADO_DUMMY = 4;

   /**
    * Create a Chado schema and prepare both it and the associated drupal schema.
    */
   public const PREPARE_TEST_CHADO = 5;

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {

    parent::setUp();

    // Only initialize the connection to Chado once.
    if (!$this->tripal_dbx) {
      $this->createChadoInstallationsTable();
      $this->getRealConfig();
      $this->initTripalDbx();
      $this->allowTestSchemas();
    }
  }

}
