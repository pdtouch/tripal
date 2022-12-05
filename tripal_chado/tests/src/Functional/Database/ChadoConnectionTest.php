<?php

namespace Drupal\Tests\tripal_chado\Functional;

/**
 * Tests for the Chado Connection implementation of Tripal DBX Connection.
 *
 * @group Tripal
 * @group Tripal TripalDBX
 * @group Tripal TripalDBX Connection
 * @group TripalDBX Chado
 */
class ChadoConnectionTest extends ChadoTestBrowserBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tripal', 'tripal_chado'];

  /**
   * Tests table prefixing by the ChadoConnection + TripalDbxConnection classes.
   *
   * NOTE:
   * In Drupal you can execute queries directly using CONNECTION->query()
   * or you can use the various query builders: CONNECTION->select(),
   * CONNECTION->update(), CONNECTION->merge(), CONNECTION->upsert(), CONNECTION->delete().
   *
   * This test is focusing on CONNECTION->query() since a code analysis shows
   * that the other options are simply preparing a query and then executing it
   * using CONNECTION->query().
   *
   * That said, at some point we may want to add additional tests to show that
   * the query builders are building queries appropriately but because these
   * are Drupal functionalities and our differences come during execution
   * and not at the query building stage, we are currently going to assume that
   * the Drupal testing is sufficient for the query builders.
   */
  public function testDefaultTablePrefixing() {

    // Open a Connection to the default Tripal DBX managed Chado schema.
    $connection = \Drupal::service('tripal_chado.database');
    $chado_1_prefix = $connection->getSchemaName();

    // Create a situation where we should be using the core chado schema for our query.
    $query = $connection->query("SELECT name, uniquename FROM {1:feature} LIMIT 1");
    $sqlStatement = $query->getQueryString();
    // We expect: "SCHEMAPREFIX"."feature" but since the quotes are not
    // necessary and could be interchanged by Drupal, we use the following pattern.
    $we_expect_pattern = str_replace('SCHEMAPREFIX', $chado_1_prefix, '/["\']+SCHEMAPREFIX["\']+\.["\']+feature["\']+/');
    $this->assertMatchesRegularExpression($we_expect_pattern, $sqlStatement,
      "The sql statement does not have the table prefix we expect.");

    // Test the API realizes that chado is the default schema for this query.
    // We expect this to fail as the default database is chado unless Tripal DBX
    // is told otherwise.
    // NOTE: we use try/catch here so we can continue with our testing.
    // When using expectException the execution of all other assertions is skipped.
    try {
      $query = $connection->query("SELECT name, uniquename FROM {feature} LIMIT 1");
    } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $e) {
      $this->assertTrue(TRUE, "We expect to have an exception thrown when TripalDBX incorrectly assumes the feature table is in Drupal, which it's not.");
    }

    // Now we want to tell Tripal DBX that the default schema for this query should be chado.
    // Using useTripalDbxSchemaFor():
    //---------------------------------
    // PARENT CLASS: Let's check if it works when a parent class is white listed.
    $connection = \Drupal::service('tripal_chado.database');
    $connection->useTripalDbxSchemaFor(\Drupal\Tests\tripal_chado\Functional\ChadoTestBrowserBase::class);
    try {
      $query = $connection->query("SELECT name, uniquename FROM {feature} LIMIT 1");
    } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $e) {
      $this->assertTrue(FALSE, "Now TripalDBX should know that chado is the default schema for this test class and it should not throw an exception.");
    }
    // We expect: "SCHEMAPREFIX"."feature" but since the quotes are not
    // necessary and could be interchanged by Drupal, we use the following pattern.
    $sqlStatement = $query->getQueryString();
    $we_expect_pattern = str_replace('SCHEMAPREFIX', $chado_1_prefix, '/["\']+SCHEMAPREFIX["\']+\.["\']+feature["\']+/');
    $this->assertMatchesRegularExpression($we_expect_pattern, $sqlStatement,
      "The sql statement does not have the table prefix we expect.");

    // CURRENT CLASS: Let's test it works when the current class is whitelisted
    $connection = \Drupal::service('tripal_chado.database');
    $connection->useTripalDbxSchemaFor(\Drupal\Tests\tripal_chado\Functional\ChadoConnectionTest::class);
    try {
      $query = $connection->query("SELECT name, uniquename FROM {feature} LIMIT 1");
    } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $e) {
      $this->assertTrue(FALSE, "Now TripalDBX should know that chado is the default schema for this test class and it should not throw an exception.");
    }
    // We expect: "SCHEMAPREFIX"."feature" but since the quotes are not
    // necessary and could be interchanged by Drupal, we use the following pattern.
    $sqlStatement = $query->getQueryString();
    $we_expect_pattern = str_replace('SCHEMAPREFIX', $chado_1_prefix, '/["\']+SCHEMAPREFIX["\']+\.["\']+feature["\']+/');
    $this->assertMatchesRegularExpression($we_expect_pattern, $sqlStatement,
      "The sql statement does not have the table prefix we expect.");

    // CURRENT OBJECT: Let's test it works when the current class is whitelisted
    $connection = \Drupal::service('tripal_chado.database');
    $connection->useTripalDbxSchemaFor($this);
    try {
      $query = $connection->query("SELECT name, uniquename FROM {feature} LIMIT 1");
    } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $e) {
      $this->assertTrue(FALSE, "Now TripalDBX should know that chado is the default schema for this test class and it should not throw an exception.");
    }
    // We expect: "SCHEMAPREFIX"."feature" but since the quotes are not
    // necessary and could be interchanged by Drupal, we use the following pattern.
    $sqlStatement = $query->getQueryString();
    $we_expect_pattern = str_replace('SCHEMAPREFIX', $chado_1_prefix, '/["\']+SCHEMAPREFIX["\']+\.["\']+feature["\']+/');
    $this->assertMatchesRegularExpression($we_expect_pattern, $sqlStatement,
      "The sql statement does not have the table prefix we expect.");
  }

  /**
   * This tests the ChadoConnection::findVersion() method.
   *
   * We will test that the version can be obtained from the test schema when it
   * is generated in the following ways:
   * 1. INIT_CHADO_EMPTY
   * 2. INIT_CHADO_DUMMY
   * 3. PREPARE_TEST_CHADO
   *
   * Furthermore, we will test both when the findVersion() method is called with
   * A. no parameters
   * B. schema name supplied
   * C. exact version requested.
   */
  public function testFindVersion() {
    $expected_version = '1.3';

    // Test 1A
    $connection = $this->chado;
    $version = $connection->findVersion();
    $this->assertEquals($version, $expected_version,
      "Unable to extract the version from INIT_CHADO_EMPTY test schema with no parameters provided.");
      $version = $connection->findVersion();
    // Test 1B
    $schema_name = $connection->getSchemaName();
    $version = $connection->findVersion($schema_name);
    $this->assertEquals($version, $expected_version,
      "Unable to extract the version from INIT_CHADO_EMPTY test schema with the schema name provided.");
    // Test 1C
    $version = $connection->findVersion($schema_name, TRUE);
    $this->assertEquals($version, $expected_version,
      "Unable to extract the Exact Version from INIT_CHADO_EMPTY test schema with the schema name provided.");

    // Test 2A
    $connection = $this->getTestSchema(ChadoTestBrowserBase::INIT_CHADO_DUMMY);
    $version = $connection->findVersion();
    $this->assertEquals($version, $expected_version,
      "Unable to extract the version from INIT_CHADO_DUMMY test schema with no parameters provided.");
      $version = $connection->findVersion();
    // Test 2B
    $schema_name = $connection->getSchemaName();
    $version = $connection->findVersion($schema_name);
    $this->assertEquals($version, $expected_version,
      "Unable to extract the version from INIT_CHADO_DUMMY test schema with the schema name provided.");
    // Test 2C
    $version = $connection->findVersion($schema_name, TRUE);
    $this->assertEquals($version, $expected_version,
      "Unable to extract the Exact Version from INIT_CHADO_DUMMY test schema with the schema name provided.");

    // Test 3A
    $connection = $this->getTestSchema(ChadoTestBrowserBase::PREPARE_TEST_CHADO);
    $version = $connection->findVersion();
    $this->assertEquals($version, $expected_version,
      "Unable to extract the version from PREPARE_TEST_CHADO test schema with no parameters provided.");
      $version = $connection->findVersion();
    // Test 3B
    $schema_name = $connection->getSchemaName();
    $version = $connection->findVersion($schema_name);
    $this->assertEquals($version, $expected_version,
      "Unable to extract the version from PREPARE_TEST_CHADO test schema with the schema name provided.");
    // Test 3C
    $version = $connection->findVersion($schema_name, TRUE);
    $this->assertEquals($version, $expected_version,
      "Unable to extract the Exact Version from PREPARE_TEST_CHADO test schema with the schema name provided.");

  }
}