<?php
/**
 * @link      https://github.com/weierophinney/phlyql_resource for the canonical source repository
 * @copyright Copyright (c) 2013 Matthew Weier O'Phinney
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 */

namespace Phly;

use PDO;
use Zend\Math\Rand;

class SqliteResource
{
    protected $pdo;

    protected $table;

    public function __construct(Pdo $pdo, $table)
    {
        $this->pdo   = $pdo;
        $this->table = $table;
    }

    public function create(array $data)
    {
        $id         = Rand::getString(32, 'abcdef0123456789');
        $data['id'] = $id;
        $json       = json_encode($data);

        $stmt = $this->pdo->prepare(sprintf(
            'INSERT INTO %s (id, data) VALUES (:id, :data)',
            $this->table
        ));
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':data', $json);

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception\InsertException($errorInfo[2], $errorInfo[1]);
        }

        return $data;
    }

    public function fetch($id)
    {
        $stmt = $this->pdo->prepare(sprintf(
            'SELECT data FROM %s WHERE id = :id',
            $this->table
        ));
        $stmt->bindParam(':id', $id);

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception\FetchException($errorInfo[2], $errorInfo[1]);
        }

        $json = $stmt->fetchColumn();
        if (!$json) {
            throw new Exception\FetchException(sprintf(
                'Resource identified by "%s" not found',
                $id
            ));
        }

        return json_decode($json, true);
    }

    public function fetchAll($limit = null, $offset = null)
    {
        $sql  = sprintf('SELECT data FROM %s', $this->table);
        $args = array();

        if (null !== $limit) {
            $sql           .= ' LIMIT :limit';
            $args[':limit'] = $limit;

            if (null !== $offset) {
                $sql            .= ' OFFSET :offset';
                $args[':offset'] = $offset;
            }
        }

        $stmt = $this->pdo->prepare($sql);

        if (!$stmt->execute($args)) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception\FetchException($errorInfo[2], $errorInfo[1]);
        }

        $data = $stmt->fetchAll(PDO::FETCH_COLUMN);
        array_walk($data, function (&$resource) {
            $resource = json_decode($resource, true);
        });

        return $data;
    }

    public function patch($id, array $data)
    {
        $resource      = $this->fetch($id);
        $patched       = array_merge($resource, $data);
        $patched['id'] = $id;
        $json          = json_encode($patched);

        $stmt = $this->pdo->prepare(sprintf(
            'UPDATE %s SET data = :data WHERE id = :id',
            $this->table
        ));

        $stmt->bindParam(':data', $json);
        $stmt->bindParam(':id', $id);

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception\PatchException($errorInfo[2], $errorInfo[1]);
        }

        return $patched;
    }

    public function update($id, array $data)
    {
        $data['id'] = $id;
        $json       = json_encode($data);

        $stmt = $this->pdo->prepare(sprintf(
            'UPDATE %s SET data = :data WHERE id = :id',
            $this->table
        ));

        $stmt->bindParam(':data', $json);
        $stmt->bindParam(':id', $id);

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception\UpdateException($errorInfo[2], $errorInfo[1]);
        }

        return $data;
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare(sprintf(
            'DELETE FROM %s WHERE id = :id',
            $this->table
        ));
        $stmt->bindParam(':id', $id);

        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception\UpdateException($errorInfo[2], $errorInfo[1]);
        }

        if ($stmt->rowCount() == 0) {
            return false;
        }

        return true;
    }
}
