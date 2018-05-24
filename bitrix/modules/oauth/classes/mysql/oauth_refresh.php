<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/oauth/classes/general/oauth_refresh.php");

/**
 * Class COAuthRefreshToken
 *
 * @deprecated
 */
class COAuthRefreshToken extends CAllOAuthRefreshToken
{
	static public function add($arFields)
	{
		/** @global CDataBase $DB */

		global $DB;

		if(!self::checkFields('ADD',$arFields))
			return false;

		$arInsert = $DB->PrepareInsert("b_oauth_refresh_token", $arFields);

		$strSql =
			"INSERT INTO b_oauth_refresh_token (".$arInsert[0].") ".
				"VALUES(".$arInsert[1].")";

		$res = $DB->Query($strSql, true, "File: ".__FILE__."<br>Line: ".__LINE__);
		if(!$res)
			return false;
		$lastId = intval($DB->LastID());
		return $lastId;
	}

	static public function getList($arOrder = array(), $arFilter = array(), $arGroupBy = false, $arNavStartParams = false, $arSelectFields = array())
	{
		global $DB;
		if(count($arSelectFields) <= 0)
			$arSelectFields = array("ID", "CLIENT_ID", "REFRESH_TOKEN", "EXPIRES", "USER_ID", "OAUTH_TOKEN_ID", "APPLICATION_ID", "SCOPE");

		$arFields = array(
			"ID" => array("FIELD" => "RT.ID", "TYPE" => "int"),
			"CLIENT_ID" => array("FIELD" => "RT.CLIENT_ID", "TYPE" => "int"),
			"REFRESH_TOKEN" => array("FIELD" => "RT.REFRESH_TOKEN", "TYPE" => "string"),
			"EXPIRES" => array("FIELD" => "RT.EXPIRES", "TYPE" => "int"),
			"USER_ID" => array("FIELD" => "RT.USER_ID", "TYPE" => "int"),
			"OAUTH_TOKEN_ID" => array("FIELD" => "RT.OAUTH_TOKEN_ID", "TYPE" => "int"),
			"APPLICATION_ID" => array("FIELD" => "OA.CLIENT_ID", "TYPE" => "string", "FROM" => "RIGHT JOIN b_oauth_client OA ON (RT.CLIENT_ID = OA.ID)"),
			"SCOPE" => array("FIELD" => "OT.SCOPE", "TYPE" => "string", "FROM" => "RIGHT JOIN b_oauth_token OT ON (RT.OAUTH_TOKEN_ID = OT.ID)"),
		);

		$arSqls = CGroup::PrepareSql($arFields, $arOrder, $arFilter, $arGroupBy, $arSelectFields);
		$arSqls["SELECT"] = str_replace("%%_DISTINCT_%%", "", $arSqls["SELECT"]);

		if (is_array($arGroupBy) && count($arGroupBy)==0)
		{
			$strSql =
				"SELECT ".$arSqls["SELECT"]." ".
					"FROM b_oauth_refresh_token RT ".
					"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			if ($arRes = $dbRes->Fetch())
				return $arRes["CNT"];
			else
				return false;
		}

		$strSql =
			"SELECT ".$arSqls["SELECT"]." ".
				"FROM b_oauth_refresh_token RT ".
				"	".$arSqls["FROM"]." ";
		if (strlen($arSqls["WHERE"]) > 0)
			$strSql .= "WHERE ".$arSqls["WHERE"]." ";
		if (strlen($arSqls["GROUPBY"]) > 0)
			$strSql .= "GROUP BY ".$arSqls["GROUPBY"]." ";
		if (strlen($arSqls["ORDERBY"]) > 0)
			$strSql .= "ORDER BY ".$arSqls["ORDERBY"]." ";
		if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"])<=0)
		{
			$strSql_tmp =
				"SELECT COUNT('x') as CNT ".
					"FROM b_oauth_refresh_token RT ".
					"	".$arSqls["FROM"]." ";
			if (strlen($arSqls["WHERE"]) > 0)
				$strSql_tmp .= "WHERE ".$arSqls["WHERE"]." ";
			if (strlen($arSqls["GROUPBY"]) > 0)
				$strSql_tmp .= "GROUP BY ".$arSqls["GROUPBY"]." ";

			$dbRes = $DB->Query($strSql_tmp, false, "File: ".__FILE__."<br>Line: ".__LINE__);
			$cnt = 0;
			if (strlen($arSqls["GROUPBY"]) <= 0)
			{
				if ($arRes = $dbRes->Fetch())
					$cnt = $arRes["CNT"];
			}
			else
			{
				$cnt = $dbRes->SelectedRowsCount();
			}

			$dbRes = new CDBResult();

			$dbRes->NavQuery($strSql, $cnt, $arNavStartParams);
		}
		else
		{
			if (is_array($arNavStartParams) && intval($arNavStartParams["nTopCount"])>0)
				$strSql .= "LIMIT ".intval($arNavStartParams["nTopCount"]);

			$dbRes = $DB->Query($strSql, false, "File: ".__FILE__."<br>Line: ".__LINE__);
		}

		return $dbRes;
	}

}