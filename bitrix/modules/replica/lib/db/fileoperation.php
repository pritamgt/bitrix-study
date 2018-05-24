<?php
namespace Bitrix\Replica\Db;

class FileOperation extends BaseOperation
{
	/**
	 * Writes NEW FILE operation into log.
	 *
	 * @param integer $fileId File identifier from b_file.ID.
	 * @param string $fileGuid Global identifier.
	 * @param array $allNodes All related databases.
	 * @param array $targetNodes Target databases.
	 *
	 * @return void
	 */
	public function writeAddToLog($fileId, $fileGuid, $allNodes, $targetNodes)
	{
		$this->nodes = $targetNodes;
		$fileInfo = \CFile::getFileArray($fileId);
		if (!$fileInfo)
		{
			return;
		}

		$map = $this->getTranslation($fileId);
		if ($map)
		{
			return;
		}

		if ($fileGuid)
		{
			$guid = $fileGuid;
		}
		else
		{
			$guid = $this->addTranslation($fileId, $targetNodes);
		}

		$event = array(
			"operation" => "file_add",
			"nodes" => $allNodes,
			"src" => \CHTTP::urn2uri($fileInfo["SRC"]),
			"name" => $fileInfo["ORIGINAL_NAME"],
			"type" => $fileInfo["CONTENT_TYPE"],
			"size" => $fileInfo["FILE_SIZE"],
			"height" => $fileInfo["HEIGHT"],
			"width" => $fileInfo["WIDTH"],
			"description" => $fileInfo["DESCRIPTION"],
			"guid" => $guid,
			"ts" => time(),
			"ip" => \Bitrix\Main\Application::getInstance()->getContext()->getServer()->get('REMOTE_ADDR'),
		);

		\Bitrix\Replica\Log\Client::getInstance()->write(
			$targetNodes,
			$event
		);
	}

	/**
	 * Writes FILE DELETE operation into log.
	 *
	 * @param integer $fileId File identifier.
	 *
	 * @return void
	 */
	public function writeDeleteToLog($fileId)
	{
		$map = $this->getTranslation($fileId);
		if (!$map)
		{
			return;
		}

		$fileGuid = false;
		$targetNodes = array();
		foreach ($map as $guid => $nodes)
		{
			$fileGuid = $guid;
			$targetNodes = array_merge($targetNodes, $nodes);
		}

		if ($targetNodes)
		{
			$event = array(
				"operation" => "file_delete",
				"nodes" => $targetNodes,
				"guid" => $fileGuid,
			);

			\Bitrix\Replica\Log\Client::getInstance()->write(
				$targetNodes,
				$event
			);
		}
	}

	/**
	 * Returns guid map for the file.
	 *
	 * @param integer $fileId File identifier.
	 *
	 * @return array
	 * @see \Bitrix\Replica\Mapper::getByPrimaryValue
	 */
	public function getTranslation($fileId)
	{
		$relation = "b_file.ID";
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$map = $mapper->getByPrimaryValue($relation, false, $fileId);

		return $map;
	}

	/**
	 * Returns generated new guid and adds it into the map.
	 *
	 * @param integer $fileId File identifier.
	 * @param array $nodes Target databases.
	 *
	 * @return string
	 * @see \Bitrix\Replica\Mapper::generateGuid
	 * @see \Bitrix\Replica\Mapper::add
	 */
	public function addTranslation($fileId, array $nodes)
	{
		$relation = "b_file.ID";
		$mapper = \Bitrix\Replica\Mapper::getInstance();

		$guid = $mapper->generateGuid();

		foreach ($nodes as $node)
		{
			$mapper->add($relation, $fileId, $node, $guid);
		}

		return $guid;
	}

	/**
	 * Replay replication log.
	 *
	 * @param array $event Event description formed by writeToLog method.
	 * @param string $nodeFrom Source database identifier.
	 * @param string $nodeTo Target database identifier.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public function applyAddLog($event, $nodeFrom, $nodeTo)
	{
		$fileLoader = \Bitrix\Replica\Client\FileLoader::getInstance();
		$partSize = $fileLoader->getPartSize($event["name"], $event["size"]);
		$useFileLoader = ($event["size"] > $partSize);

		if ($useFileLoader)
		{
			$file = array(
				"content" => "",
				"name" => $event["name"],
				"height" => $event["height"],
				"width" => $event["width"],
				"description" => $event["description"],
			);
		}
		else
		{
			$file = \CFile::makeFileArray($event["src"], $event["type"]);
			$file["name"] = $event["name"];
			$file["height"] = $event["height"];
			$file["width"] = $event["width"];
			$file["description"] = $event["description"];
			if (!$file["tmp_name"])
			{
				throw new \Bitrix\Replica\ServerException("New file failed. Failed to download file. [".$event["src"]."]");
			}
		}

		$fileId = \CFile::saveFile($file, 'replica');
		if (!$fileId)
		{
			throw new \Bitrix\Replica\ServerException("New file failed. Failed to save file. [".$event["src"]."]");
		}

		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$mapper->add("b_file.ID", $fileId, $nodeFrom, $event["guid"]);
		foreach ($event["nodes"] as $node)
		{
			if ($node != $nodeTo)
			{
				$mapper->add("b_file.ID", $fileId, $node, $event["guid"]);
			}
		}

		if ($useFileLoader)
		{
			$fileLoader->registerDownload(
				$event["src"],
				$event["size"],
				$fileId,
				$partSize,
				array(
					"FILE_SIZE" => $event["size"],
					"HEIGHT" => $event["height"],
					"WIDTH" => $event["width"],
					"CONTENT_TYPE" => $event["type"],
				)
			);
		}
	}

	/**
	 * Replay replication log.
	 *
	 * @param array $event Event description formed by writeToLog method.
	 * @param string $nodeFrom Source database identifier.
	 * @param string $nodeTo Target database identifier.
	 *
	 * @return void
	 * @throws \Bitrix\Replica\ServerException
	 */
	public function applyDeleteLog($event, $nodeFrom, $nodeTo)
	{
		$relation = "b_file.ID";
		$mapper = \Bitrix\Replica\Mapper::getInstance();
		$fileId = $mapper->getByGuid($relation, $event["guid"]);
		if (!$fileId)
		{
			throw new \Bitrix\Replica\ServerException("Delete file failed. Map not found [".$event["guid"]."]");
		}

		\CFile::delete($fileId);
		$mapper->deleteByGuid($relation, $event["guid"]);
	}
}
