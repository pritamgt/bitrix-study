<?php
IncludeModuleLangFile(__FILE__);

/**
 * Class CAllOAuthRefreshToken
 *
 * @deprecated
 */
class CAllOAuthRefreshToken
{
	protected function checkFields($action, &$arFields)
	{
		if(($action == 'ADD') &&
			((is_set($arFields, "CLIENT_ID") && intval($arFields["CLIENT_ID"]) <= 0)))
		{
			return false;
		}

		if(($action == 'ADD') &&
			((is_set($arFields, "REFRESH_TOKEN") && strlen($arFields["REFRESH_TOKEN"]) <= 0)))
		{
			return false;
		}

		return true;
	}

	static public function update($id, $arFields)
	{
		global $DB;
		$id = intval($id);

		if($id <= 0 || !self::CheckFields('UPDATE',$arFields))
			return false;
		$strUpdate = $DB->PrepareUpdate("b_oauth_refresh_token", $arFields);

		if(!empty($strUpdate))
		{
			$strSql = "UPDATE b_oauth_refresh_token SET ".$strUpdate." WHERE ID = ".$id." ";
			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
		}

		return $id;
	}

	static public function delete($id)
	{
		global $DB;
		$id = intval($id);
		if ($id > 0)
		{
			if($DB->Query("DELETE FROM b_oauth_refresh_token WHERE ID = ".$id." ", true))
				return true;
		}
		return false;
	}

}