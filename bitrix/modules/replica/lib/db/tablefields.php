<?php
namespace Bitrix\Replica\Db;

class TableFields
{
	/**
	 * Converts string, datetime and date fields into transportable format.
	 *
	 * @param string $tableName Table name.
	 * @param array $record Record from the table.
	 *
	 * @return array
	 */
	public static function convertDbToLog($tableName, $record)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$tableFields = $connection->getTableFields($tableName);

		foreach ($tableFields as $fieldName => $fieldInfo)
		{
			if (
				!isset($record[$fieldName])
				|| strlen($record[$fieldName]) <= 0
				|| $record[$fieldName] === '0'
				|| $record[$fieldName] === 0
			)
			{
				continue;
			}

			if ($fieldInfo instanceof \Bitrix\Main\Entity\StringField)
			{
				if (!\Bitrix\Main\Application::isUtfMode() && preg_match('/[^\x20-\x7f]/', $record[$fieldName]))
				{
					$errorMessage = '';
					$convertedText = \Bitrix\Main\Text\Encoding::convertEncoding($record[$fieldName], SITE_CHARSET, "UTF-8", $errorMessage);
					if ($errorMessage === '')
					{
						$record[$fieldName] = $convertedText;
					}
				}
			}
			elseif ($fieldInfo instanceof \Bitrix\Main\Entity\DatetimeField)
			{
				if ($record[$fieldName] instanceof \Bitrix\Main\Type\DateTime)
				{
					/** @var \Bitrix\Main\Type\DateTime $fieldValue */
					$fieldValue = $record[$fieldName];
					$dateValue = new \Bitrix\Main\Type\DateTime($fieldValue->format("Y-m-d H:i:s"), "Y-m-d H:i:s");
					$dateValue->setTimeZone(new \DateTimeZone('UTC'));
					$record[$fieldName] = $dateValue->format("Y-m-d H:i:s");
				}
			}
			elseif ($fieldInfo instanceof \Bitrix\Main\Entity\DateField)
			{
				if ($record[$fieldName] instanceof \Bitrix\Main\Type\Date)
				{
					/** @var \Bitrix\Main\Type\Date $fieldValue */
					$fieldValue = $record[$fieldName];
					$record[$fieldName] = $fieldValue->format("Y-m-d");
				}
			}
		}

		return $record;
	}

	/**
	 * Converts string, datetime and date fields into database format.
	 *
	 * @param string $tableName Table name.
	 * @param array $record Record from the transport message.
	 *
	 * @return array
	 */
	public static function convertLogToDb($tableName, $record)
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$tableFields = $connection->getTableFields($tableName);

		foreach ($tableFields as $fieldName => $fieldInfo)
		{
			if (
				!isset($record[$fieldName])
				|| strlen($record[$fieldName]) <= 0
				|| $record[$fieldName] === '0'
				|| $record[$fieldName] === 0
			)
			{
				continue;
			}

			if ($fieldInfo instanceof \Bitrix\Main\Entity\StringField)
			{
				if (!\Bitrix\Main\Application::isUtfMode() && preg_match('/[^\x20-\x7f]/', $record[$fieldName]))
				{
					$errorMessage = '';
					$convertedText = \Bitrix\Main\Text\Encoding::convertEncoding($record[$fieldName], "UTF-8", SITE_CHARSET, $errorMessage);
					if ($errorMessage === '')
					{
						$record[$fieldName] = $convertedText;
					}
				}
			}
			elseif ($fieldInfo instanceof \Bitrix\Main\Entity\DatetimeField)
			{
				$dateValue = new \Bitrix\Main\Type\DateTime($record[$fieldName], "Y-m-d H:i:s", new \DateTimeZone('UTC'));
				$record[$fieldName] = $dateValue;
			}
			elseif ($fieldInfo instanceof \Bitrix\Main\Entity\DateField)
			{
				$dateValue = new \Bitrix\Main\Type\Date($record[$fieldName], "Y-m-d");
				$record[$fieldName] = $dateValue;
			}
		}

		return $record;
	}
}
