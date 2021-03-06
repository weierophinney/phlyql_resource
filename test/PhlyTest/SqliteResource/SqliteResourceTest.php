<?php
/**
 * @link      https://github.com/weierophinney/phlyql_resource for the canonical source repository
 * @copyright Copyright (c) 2013 Matthew Weier O'Phinney
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace PhlyTest\SqliteResource;

use PDO;
use Phly\SqliteResource\SqliteResource;
use PHPUnit_Framework_TestCase as TestCase;

class SqliteResourceTest extends TestCase
{
    public function setUp()
    {
        $this->pdo = $pdo = new PDO('sqlite::memory:');
        $sql = file_get_contents(__DIR__ . '/../../../schema.sqlite.sql');
        $pdo->exec($sql);

        $this->resource = new SqliteResource($pdo, 'collection');
    }

    public function testCreateWillInsertAndReturnTheResourceProvided()
    {
        $resource = array(
            'foo' => 'bar',
            'bar' => 'baz',
        );

        $test = $this->resource->create($resource);
        $this->assertInternalType('array', $test);
        foreach ($resource as $key => $value) {
            $this->assertArrayHasKey($key, $test);
            $this->assertEquals($value, $test[$key]);
        }
        $this->assertArrayHasKey('id', $test);
        $this->assertRegExp('/^[a-z0-9]{32}$/', $test['id']);

        return $test;
    }

    public function testFetchWillReturnTheResource()
    {
        $resource = array(
            'foo' => 'bar',
            'bar' => 'baz',
        );

        $test = $this->resource->create($resource);
        $this->assertInternalType('array', $test);
        $this->assertArrayHasKey('id', $test);

        $id = $test['id'];
        $fetch = $this->resource->fetch($id);
        $this->assertEquals($test, $fetch);
    }

    public function testFetchWillRaiseAnExceptionIfResourceNotFound()
    {
        $this->setExpectedException('Phly\SqliteResource\Exception\FetchException', 'not found');
        $fetch = $this->resource->fetch('foo');
    }

    public function testCanPatchResources()
    {
        $resource = array(
            'foo' => 'bar',
            'bar' => 'baz',
        );

        $created = $this->resource->create($resource);
        $this->assertInternalType('array', $created);
        $this->assertArrayHasKey('id', $created);

        $patched = $this->resource->patch($created['id'], array('foo' => 'FOO'));

        $this->assertInternalType('array', $patched);
        $this->assertArrayHasKey('foo', $patched);
        $this->assertEquals('FOO', $patched['foo']);
        $this->assertArrayHasKey('bar', $patched);
        $this->assertEquals('baz', $patched['bar']);

        $this->assertEquals($created['id'], $patched['id']);
    }

    public function testCanUpdateResources()
    {
        $resource = array(
            'foo' => 'bar',
            'bar' => 'baz',
        );

        $created = $this->resource->create($resource);
        $this->assertInternalType('array', $created);
        $this->assertArrayHasKey('id', $created);

        $update  = array(
            'this' => 'is',
            'only' => 'in',
            'the'  => 'update',
        );
        $updated = $this->resource->update($created['id'], $update);

        $this->assertInternalType('array', $updated);
        foreach ($update as $key => $value) {
            $this->assertArrayHasKey($key, $updated);
            $this->assertEquals($value, $updated[$key]);
        }
        foreach (array_keys($resource) as $key) {
            $this->assertArrayNotHasKey($key, $updated);
        }
        $this->assertEquals($created['id'], $updated['id']);
    }

    public function testCanDeleteResources()
    {
        $resource = array(
            'foo' => 'bar',
            'bar' => 'baz',
        );

        $created = $this->resource->create($resource);
        $this->assertInternalType('array', $created);
        $this->assertArrayHasKey('id', $created);

        $status = $this->resource->delete($created['id']);
        $this->assertTrue($status);

        $this->setExpectedException('Phly\SqliteResource\Exception\FetchException', 'not found');
        $fetch = $this->resource->fetch($created['id']);
    }

    public function seedResources()
    {
        $template = array(
            'foo' => 'bar',
            'bar' => 'baz',
        );

        $resources = array();
        for ($i = 0; $i < 100 ; $i += 1) {
            $resources[] = $this->resource->create($template);
        }

        return $resources;
    }

    public function testCanFetchAllResources()
    {
        $expected  = $this->seedResources();
        $resources = $this->resource->fetchAll();
        $this->assertEquals($expected, $resources);
    }

    public function testCanFetchResourcesUsingLimit()
    {
        $allResources = $this->seedResources();
        $expected     = array_slice($allResources, 0, 10);

        $resources = $this->resource->fetchAll(10);
        $this->assertEquals($expected, $resources);
    }

    public function testCanFetchResourcesUsingLimitAndOffset()
    {
        $allResources = $this->seedResources();
        $expected     = array_slice($allResources, 10, 10);

        $resources = $this->resource->fetchAll(10, 10);
        $this->assertEquals($expected, $resources);
    }

    public function testCanRetrieveResourceCount()
    {
        $expected = $this->seedResources();
        $this->assertEquals(count($expected), count($this->resource));
    }
}
