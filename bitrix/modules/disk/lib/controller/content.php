<?php

namespace Bitrix\Disk\Controller;

use Bitrix\Disk;
use Bitrix\Disk\Bitrix24Disk;
use Bitrix\Disk\Internals\Engine\Controller;
use Bitrix\Disk\Internals\Error\Error;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\IO;

final class Content extends Controller
{
	const ERROR_COULD_TOO_BIG_REQUEST = 'too_big_request';

	public function configureActions()
	{
		return [
			'upload' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\Csrf(),
					new ActionFilter\CloseSession(),
					new ActionFilter\HttpMethod(['POST']),
				],
			],
		];
	}

	public function uploadAction($filename, $token = null)
	{
		if ($_SERVER['CONTENT_LENGTH'] >
			min(\CUtil::unformat(ini_get('upload_max_filesize')), \CUtil::unformat(ini_get('post_max_size'))))
		{
			$this->errorCollection[] = new Error(
				'The content length is too big to process request.', self::ERROR_COULD_TOO_BIG_REQUEST
			);

			return;
		}

		list($startRange, $endRange, $fileSize) = $this->getContentRange();

		if (($startRange === null || $startRange === 0) && $token)
		{
			$this->errorCollection[] = new Error('You have to send Content-Range header to continue upload');

			return;
		}

		$tmpFileArray = $this->getTmpFileArrayByInput();

		$tmpFileManager = new Bitrix24Disk\UploadFileManager();
		$tmpFileManager
			->setTmpFileClass(Disk\Document\CloudImport\TmpFile::class)
			->setToken($token)
			->setUser($this->getCurrentUser()->getId())
			->setFileSize($fileSize)
			->setContentRange([$startRange, $endRange])
		;

		if (!$tmpFileManager->upload($filename, $tmpFileArray))
		{
			$this->errorCollection->add($tmpFileManager->getErrors());

			return;
		}

		return [
			'token' => $tmpFileManager->getToken(),
		];
	}

	public function rollbackUploadAction($token)
	{
		$tmpFileManager = new Bitrix24Disk\UploadFileManager();
		$tmpFileManager
			->setToken($token)
			->setUser($this->getCurrentUser()->getId())
		;

		if (!$tmpFileManager->rollbackByToken())
		{
			$this->errorCollection->add($tmpFileManager->getErrors());
		}
	}

	public function getStatusAction(Disk\Bitrix24Disk\TmpFile $content)
	{
		return [
			'content' => [
				'size' => $content->getSize(),
				'receivedSize' => $content->getReceivedSize(),
			],
		];
	}

	public function getChunkSizeAction($filename, $size)
	{
		$size = (int)$size;
		if ($size < 0)
		{
			throw new ArgumentException('Error in size');
		}

		$tmpFileManager = new Bitrix24Disk\UploadFileManager();

		return [
			'size' => $tmpFileManager->getChunkSize($filename, $size)
		];
	}

	/**
	 * Return null, if not such range.
	 * Return array($start, $end, $length)
	 * @return array|null|bool
	 */
	private function getContentRange()
	{
		$headers = $this->getAllHeaders();
		if (!$headers || !isset($headers['Content-Range']))
		{
			return false;
		}
		$contentRange = $headers['Content-Range'];
		if (!preg_match("/(\\d+)-(\\d+)\\/(\\d+)\$/", $contentRange, $match))
		{
			return null;
		}

		return [(int)$match[1], (int)$match[2], (int)$match[3]];
	}

	/**
	 * @see getallheaders()
	 * @return array|false
	 */
	private function getAllHeaders()
	{
		if (!function_exists('getallheaders'))
		{
			$headers = [];
			foreach ($_SERVER as $name => $value)
			{
				if (substr($name, 0, 5) == 'HTTP_')
				{
					$headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
					$headers[$headerName] = $value;
				}
			}

			return $headers;
		}
		return getallheaders();
	}

	private function getTmpFileArrayByInput()
	{
		$tmpFilePath = \CTempFile::getFileName(uniqid('disk', true));
		$dir = IO\Path::getDirectory($tmpFilePath);
		IO\Directory::createDirectory($dir);
		$file = new IO\File($tmpFilePath);
		$file->putContents(file_get_contents("php://input"));

		$tmpFileArray = \CFile::makeFileArray($tmpFilePath);

		return $tmpFileArray;
	}
}