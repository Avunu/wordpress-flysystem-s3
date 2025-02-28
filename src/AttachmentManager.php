<?php

namespace Abedi\WPFlysystemS3;

use Doctrine\DBAL\Connection;

class AttachmentManager
{
	public static function getInstance(): self
	{
		return self::$instance ?: self::$instance = new self();
	}

	private static ?self $instance = null;

	private DBManager $dbManager;
	private S3Manager $s3Manager;

	public function __construct()
	{
		$this->dbManager = DBManager::getInstance();
		$this->s3Manager = S3Manager::getInstance();
	}

	/**
	 * @param array{name:string,tmp_name:string}
	 */
	public function add(string $name, string $localFile, string $localPath): int
	{
		$result = $this->getByLocalFile($localPath);
        if ($result) {
			$this->dbManager->getConnection()->createQueryBuilder()
                ->update($this->dbManager->getTable())
                ->set('count', 'count + 1')
                ->where('id = :id')
                ->setParameter('id', $result['id'])
                ->executeStatement();

            return $result['id'];
        }

		$storage = $this->s3Manager->getStorage();
		
		$dotPosition = strrpos($name, '.');
		$extention = $dotPosition !== false ? substr($name, $dotPosition) : '';
		$name = md5_file($localFile);

		if (!preg_match("/^([0-9a-f]+)(?:\.[a-zA-Z0-9]+)?$/", $name, $matches)) {
            throw new \Exception('your file name is not a valid hash');
        }

		$parts = [];
        for ($x = 0; $x < 2; ++$x) {
            $parts[] = substr($matches[1], $x * 2, 2);
        }

		$stream = fopen($localFile, 'r');

		$storage->writeStream(implode('/', $parts).'/'.$name.$extention, $stream, []);

        if (is_resource($stream)) {
            fclose($stream);
        }

		$this->dbManager->getConnection()->createQueryBuilder()
            ->insert($this->dbManager->getTable())
            ->values([
                'md5' => ':md5',
                'local_file' => ':local_file',
                'remote_file' => ':remote_file',
                'count' => ':count'
            ])
            ->setParameters([
                'md5' => $name,
                'local_file' => $localPath,
                'remote_file' => $this->s3Manager->getUrl(implode('/', $parts).'/'.$name.$extention),
                'count' => 1
            ])
            ->executeStatement();

		return $this->dbManager->getConnection()->lastInsertId();
	}

	public function delete(string $remoteFile): void
	{
		$result = $this->getByRemoteFile($remoteFile);
        if ($result) {
            if ($result['count'] > 1) {
				$this->dbManager->getConnection()->createQueryBuilder()
                    ->update($this->dbManager->getTable())
                    ->set('count', 'count - 1')
                    ->where('id = :id')
                    ->setParameter('id', $result['id'])
                    ->executeStatement();
            } else {
                $url = parse_url($remoteFile);
                $url['path'] = substr($url['path'], strlen('/public'));

                $storage = $this->s3Manager->getStorage();
				$storage->delete($url['path']);

				$this->dbManager->getConnection()->createQueryBuilder()
                    ->delete($this->dbManager->getTable())
                    ->where('id = :id')
                    ->setParameter('id', $result['id'])
                    ->executeStatement();
            }
        }
	}

	public function getByID(int $id): ?array
	{
		return $this->dbManager->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->dbManager->getTable())
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative() ?: null;
	}

	public function getByLocalFile(string $localFile): ?array
	{
		return $this->dbManager->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->dbManager->getTable())
            ->where('local_file = :local_file')
            ->setParameter('local_file', $localFile)
            ->executeQuery()
            ->fetchAssociative() ?: null;
	}

	public function getByRemoteFile(string $remoteFile): ?array
	{
		return $this->dbManager->getConnection()->createQueryBuilder()
            ->select('*')
            ->from($this->dbManager->getTable())
            ->where('remote_file = :remote_file')
            ->setParameter('remote_file', $remoteFile)
            ->executeQuery()
            ->fetchAssociative() ?: null;
	}
}
