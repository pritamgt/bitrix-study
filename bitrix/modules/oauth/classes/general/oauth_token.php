<?php
IncludeModuleLangFile(__FILE__);

/**
 * Class CAllOAuthToken
 *
 * @deprecated
 */
class CAllOAuthToken
{
	protected function checkFields($action, &$arFields)
	{
		if(isset($arFields["SCOPE"]))
		{
			if(!is_array($arFields["SCOPE"]))
				$arFields["SCOPE"] = explode(",", $arFields["SCOPE"]);
			if(is_array($arFields["SCOPE"]))
				$arFields["SCOPE"] = serialize($arFields["SCOPE"]);
			else
				return false;
		}

		if(($action == 'ADD') &&
			((is_set($arFields, "CLIENT_ID") && intval($arFields["CLIENT_ID"]) <= 0)))
		{
			return false;
		}

		if(($action == 'ADD') &&
			((is_set($arFields, "OAUTH_TOKEN") && strlen($arFields["OAUTH_TOKEN"]) <= 0)))
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
		$strUpdate = $DB->PrepareUpdate("b_oauth_token", $arFields);

		if(!empty($strUpdate))
		{
			$strSql = "UPDATE b_oauth_token SET ".$strUpdate." WHERE ID = ".$id." ";
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
			if($DB->Query("DELETE FROM b_oauth_token WHERE ID = ".$id." ", true))
				return true;
		}
		return false;
	}

	function CleanUp()
	{
		global $DB;

		$strSqlCode = "DELETE FROM b_oauth_code WHERE EXPIRES < ".(time() - COAuthConstants::AUTH_CODE_LIFETIME - 3600);

		// access_token record should be alive while its refresh_token lives
		$strSql = "DELETE FROM b_oauth_token WHERE EXPIRES < ".(time() - COAuthConstants::REFRESH_TOKEN_LIFETIME - 604800);
		$strSqlRefresh = "DELETE FROM b_oauth_refresh_token WHERE EXPIRES < ".(time() - COAuthConstants::REFRESH_TOKEN_LIFETIME - 604800);

		$DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query($strSqlRefresh, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		$DB->Query($strSqlCode, true, "File: ".__FILE__."<br>Line: ".__LINE__);

		return "COAuthToken::CleanUp();";
	}
}