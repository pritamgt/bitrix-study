<?php
namespace Bitrix\Replica\Client;

class FileLoader
{
	const PART_SIZE = 1000000; // ~1m

	/** @var \Bitrix\Replica\Client\FileLoader */
	protected static $instance = null;

	/**
	 * Singleton method.
	 *
	 * @return \Bitrix\Replica\Client\FileLoader
	 */
	public static function getInstance()
	{
		if (!self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Returns maximum file part size.
	 * If clouds module installed then finds suitable bucket based on file name and size
	 * otherwise returns \Bitrix\Replica\Client\FileLoader::PART_SIZE constant.
	 *
	 * @param string $fileName File name.
	 * @param integer $fileSize File size.
	 *
	 * @return integer
	 */
	public function getPartSize($fileName, $fileSize)
	{
		if (\Bitrix\Main\Loader::includeModule('clouds'))
		{
			$fileInfo = array(
				"name" => $fileName,
				"size" => $fileSize,
				"module" => "replica",
			);
			$bucket = \CCloudStorage::findBucketForFile($fileInfo, $fileInfo['name']);
			if ($bucket && $bucket->Init())
			{
				return $bucket->getService()->GetMinUploadPartSize();
			}
		}

		return self::PART_SIZE;
	}

	/**
	 * Puts download task into queue.
	 *
	 * @param string $fileSource URI to the file.
	 * @param integer $fileSize File size.
	 * @param integer $fileId File identifier in the b_file table.
	 * @param integer $partSize Download(Upload) chunk size.
	 * @param array $fileUpdate Array of fields of b_file to be updated after download complete.
	 *
	 * @return \Bitrix\Main\Entity\AddResult
	 * @throws \Exception
	 */
	public function registerDownload($fileSource, $fileSize, $fileId, $partSize, array $fileUpdate)
	{
		$addResult = \Bitrix\Replica\FileDlTable::add(array(
			"FILE_SRC" => $fileSource,
			"FILE_ID" => $fileId,
			"FILE_SIZE" => $fileSize,
			"FILE_POS" => 0,
			"PART_SIZE" => $partSize,
			"FILE_UPDATE" => serialize($fileUpdate),
		));

		$manageCache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		if (
			CACHED_b_replica_file_dl !== false
			&& $manageCache->read(CACHED_b_replica_file_dl, "replica_file_dl")
		)
		{
			$manageCache->clean("replica_file_dl");
		}

		return $addResult;
	}

	/**
	 * Registers $this->execute() function to be called in the end of the hit.
	 *
	 * @return void
	 * @see \Bitrix\Replica\Client\FileLoader::execute
	 */
	public function checkDownloads()
	{
		$manageCache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		if (
			CACHED_b_replica_file_dl !== false
			&& $manageCache->read(CACHED_b_replica_file_dl, "replica_file_dl")
		)
		{
			//No actions required
		}
		elseif (
			defined("BX_FORK_AGENTS_AND_EVENTS_FUNCTION")
			&& function_exists(BX_FORK_AGENTS_AND_EVENTS_FUNCTION)
			&& function_exists("getmypid")
			&& function_exists("posix_kill")
		)
		{
			\CMain::forkActions(array($this, "execute"), array());
		}
		else
		{
			addEventHandler("main", "OnAfterEpilog", array($this, "execute"));
			addEventHandler("main", "OnLocalRedirect", array($this, "execute"));
		}
	}

	/**
	 * Locks download queue so only one hit actually processes the queue.
	 * Then calls $this->process() once for least recent download and unlocks the queue.
	 *
	 * @return void
	 *
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Exception
	 * @see \Bitrix\Replica\Client\FileLoader::execute
	 */
	public function execute()
	{
		$manageCache = \Bitrix\Main\Application::getInstance()->getManagedCache();
		$downloadList = \Bitrix\Replica\FileDlTable::getList(array(
			"filter" => array("=STATUS" => "Y"),
			"order" => array("ID" => "ASC"),
			"limit" => 1,
		));
		$download = $downloadList->fetch();
		if ($download)
		{
			if (\Bitrix\Replica\FileDlTable::lock())
			{
				try
				{
					$this->process($download);
				}
				catch (\Bitrix\Replica\ServerException $e)
				{
					\Bitrix\Replica\FileDlTable::update($download["ID"], array(
						"STATUS" => "N",
						"ERROR_MESSAGE" => substr((string)$e, 0, 500),
					));
				}
				\Bitrix\Replica\FileDlTable::unlock();
			}
		}
		else
		{
			if (CACHED_b_replica_file_dl !== false)
				$manageCache->set("replica_file_dl", true);
		}
	}

	/**
	 * Downloads one part, then uploads or writes it to the target file.
	 * When download finishes updates b_file table with size, with, height and content_type.
	 * 
	 * @param array $download Download queue item.
	 * @return boolean
	 *
	 * @throws \Bitrix\Replica\ServerException
	 * @see \Bitrix\Replica\FileDlTable
	 */
	protected function process($download)
	{
		$fileInfo = unserialize($download["FILE_UPDATE"]);

		$file = \CFile::GetFileArray($download["FILE_ID"]);
		if (!$file)
		{
			throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): b_file not found.");
		}

		$bucket = false;
		if ($file["HANDLER_ID"] > 0)
		{
			if (!\Bitrix\Main\Loader::includeModule('clouds'))
			{
				throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): failed to include clouds module.");
			}

			$bucket = new \CCloudStorageBucket($file["HANDLER_ID"]);
			if (!$bucket->Init())
			{
				throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): failed to init bucket.");
			}
		}

		$io = \CBXVirtualIo::GetInstance();
		if ($bucket)
			$filePath = substr($file["SRC"], strlen($bucket->GetFileSRC("/")) - 1);
		else
			$filePath = $file["SRC"];

		$tempPath = $filePath.".part";

		//Start

		$upload = false;
		if ($bucket)
		{
			$upload = new \CCloudStorageUpload($tempPath);
			if ($download["FILE_POS"] == 0)
			{
				if (!$upload->isStarted())
				{
					if (!$upload->Start($bucket, $download["FILE_SIZE"], $fileInfo["CONTENT_TYPE"]))
					{
						throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): failed to start upload.");
					}
				}
				else
				{
					throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): upload has been started.");
				}
			}
		}

		//Download part

		$http = new \Bitrix\Main\Web\HttpClient();
		$rangeStart = $download["FILE_POS"];
		$rangeEnd = min($download["FILE_POS"] + $download["PART_SIZE"], $download["FILE_SIZE"]) - 1;
		$http->setHeader("Range", "bytes=".$rangeStart."-".$rangeEnd);
		$data = $http->get($download["FILE_SRC"]);

		if ($http->getStatus() != 200 && $http->getStatus() != 206)
		{
			throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::downloadPart(".$download["ID"]."): incorrect answer status.[".$http->getStatus()."]");
		}

		//Upload part

		if ($upload)
		{
			$uploadResult = false;
			while ($upload->hasRetries())
			{
				if ($upload->Next($data, $bucket))
				{
					$uploadResult = true;
					break;
				}
			}

			if (!$uploadResult)
			{
				throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): upload part failed.");
			}

			$filePos = $upload->getPos();
		}
		else
		{
			$normTempPath = $io->CombinePath("/", $tempPath);
			if (!$io->ValidatePathString($normTempPath))
			{
				throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): invalid path. [$normTempPath]");
			}

			$absTempPath = $io->CombinePath($_SERVER["DOCUMENT_ROOT"], $normTempPath);
			$file = new \Bitrix\Main\IO\File($absTempPath);
			try
			{
				$file->putContents($data, \Bitrix\Main\IO\File::APPEND);
			}
			catch(\Bitrix\Main\IO\IoException $e)
			{
				throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): failed append file($absTempPath). [".((string)$e)."]");
			}

			$filePos = $file->getSize();
		}

		//Continue next time
		if ($filePos < $download["FILE_SIZE"])
		{
			\Bitrix\Replica\FileDlTable::update($download["ID"], array(
				"FILE_POS" => $filePos,
			));
			return true;
		}

		//Finish
		if ($upload)
		{
			if (!$upload->Finish($bucket))
			{
				throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): finish has failed.");
			}

			$bucket->IncFileCounter($download["FILE_SIZE"]);

			if (!$bucket->FileRename($tempPath, $filePath))
			{
				throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): rename failed.[$tempPath -> $filePath]");
			}
		}
		else
		{
			$normPath = $io->CombinePath("/", $filePath);
			$absPath = $io->CombinePath($_SERVER["DOCUMENT_ROOT"], $normPath);

			if (!rename($absTempPath, $absPath))
			{
				throw new \Bitrix\Replica\ServerException("\\Bitrix\\Replica\\FileLoader::process(".$download["ID"]."): rename failed.[$absTempPath -> $absPath]");
			}
		}

		$connection = \Bitrix\Main\Application::getConnection();
		$sqlHelper = $connection->getSqlHelper();
		$update = $sqlHelper->prepareUpdate("b_file", $fileInfo);

		if ($update[0])
		{
			$connection->query($q = "
				UPDATE b_file
				SET ".$update[0]."
				WHERE ID = ".intval($download["FILE_ID"]),
				$update[1]);
		}

		\Bitrix\Replica\FileDlTable::delete($download["ID"]);
		return false;
	}
}
