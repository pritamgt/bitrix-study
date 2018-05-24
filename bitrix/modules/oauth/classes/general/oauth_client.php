<?phpIncludeModuleLangFile(__FILE__);

/**
 * Class CAllOAuthClient
 *
 * @deprecated
 */
class CAllOAuthClient
{
	protected function checkFields($action, &$arFields)
	{
		if(isset($arFields["SCOPE"]))
		{
			if(is_array($arFields["SCOPE"]))
				$arFields["SCOPE"] = serialize($arFields["SCOPE"]);
			else
				return false;
		}

		if(($action == 'ADD') &&
			((is_set($arFields, "CLIENT_ID") && strlen($arFields["CLIENT_ID"]) <= 0)))
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
		$strUpdate = $DB->PrepareUpdate("b_oauth_client", $arFields);

		if(!empty($strUpdate))
		{
			$strSql = "UPDATE b_oauth_client SET ".$strUpdate." WHERE ID = ".$id." ";
			if(!$DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__))
				return false;
			$DB->Query("DELETE FROM b_oauth_token WHERE CLIENT_ID = ".$id." ", true);
			$DB->Query("DELETE FROM b_oauth_refresh_token WHERE CLIENT_ID = ".$id." ", true);
		}

		return $id;
	}

	static public function delete($id)
	{
		global $DB;
		$id = intval($id);
		if ($id > 0)
		{
			if($DB->Query("DELETE FROM b_oauth_client WHERE ID = ".$id." ", true))
				return true;
		}
		return false;
	}

}