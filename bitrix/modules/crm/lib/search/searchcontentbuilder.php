<?php
namespace Bitrix\Crm\Search;
use Bitrix\Crm\Integrity\DuplicateCommunicationCriterion;
abstract class SearchContentBuilder
{
	abstract public function getEntityTypeID();
	abstract public function isFullTextSearchEnabled();
	abstract protected function prepareEntityFields($entityID);
	abstract public function prepareEntityFilter(array $params);
	/**
	 * Prepare search map.
	 * @param array $fields Entity Fields.
	 * @return SearchMap
	 */
	abstract protected function prepareSearchMap(array $fields);
	abstract protected function save($entityID, SearchMap $map);

	protected function getSearchFieldName()
	{
		return 'SEARCH_CONTENT';
	}

	/**
	 * Convert entity list filter values.
	 * @param array $filter List Filter.
	 * @return void
	 */
	public function convertEntityFilterValues(array &$filter)
	{
		$this->transferEntityFilterKeys(array('FIND'), $filter);
	}
	/**
	 * Transfer specified filter keys to search content.
	 * @param array $sourceKeys Filter keys for transfer to search
	 * @param array $filter List Filter.
	 */
	protected function transferEntityFilterKeys(array $sourceKeys, array &$filter)
	{
		$searchFieldName = $this->getSearchFieldName();
		foreach($sourceKeys as $key)
		{
			if(!isset($filter[$key]))
			{
				continue;
			}

			if(is_string($filter[$key]))
			{
				$find = trim($filter[$key]);
				if($find !== '')
				{
					$find = SearchEnvironment::prepareSearchContent($find);
					if(!isset($filter[$searchFieldName]))
					{
						$filter[$searchFieldName] = $find;
					}
					else
					{
						$filter[$searchFieldName] .= ' '.$find;
					}
				}
			}
			unset($filter[$key]);
		}
	}

	protected function getEntityMultiFields($entityID)
	{
		if(!is_int($entityID))
		{
			$entityID = (int)$entityID;
		}

		if($entityID <= 0)
		{
			return array();
		}

		return DuplicateCommunicationCriterion::prepareEntityMultifieldValues($this->getEntityTypeID(), $entityID);
	}

	public function build($entityID)
	{
		$fields = $this->prepareEntityFields($entityID);
		if(is_array($fields))
		{
			$this->save($entityID, $this->prepareSearchMap($fields));
		}
	}

	public function bulkBuild(array $entityIDs)
	{
		foreach($entityIDs as $entityID)
		{
			$fields = $this->prepareEntityFields($entityID);
			if(is_array($fields))
			{
				$this->save($entityID, $this->prepareSearchMap($fields));
			}
		}
	}
}