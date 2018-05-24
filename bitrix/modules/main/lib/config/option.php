<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2015 Bitrix
 */
namespace Bitrix\Main\Config;

use Bitrix\Main;

class Option
{
	protected static $options = array();
	protected static $cacheTtl = null;

	/**
	 * Returns a value of an option.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $default The default value to return, if a value doesn't exist.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return string
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function get($moduleId, $name, $default = "", $siteId = false)
	{
		if (empty($moduleId))
			throw new Main\ArgumentNullException("moduleId");
		if (empty($name))
			throw new Main\ArgumentNullException("name");

		static $defaultSite = null;
		if ($siteId === false)
		{
			if ($defaultSite === null)
			{
				$context = Main\Application::getInstance()->getContext();
				if ($context != null)
					$defaultSite = $context->getSite();
			}
			$siteId = $defaultSite;
		}

		$siteKey = ($siteId == "") ? "-" : $siteId;
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();
		if ((static::$cacheTtl === false) && !isset(self::$options[$siteKey][$moduleId])
			|| (static::$cacheTtl !== false) && empty(self::$options))
		{
			self::load($moduleId, $siteId);
		}

		if (isset(self::$options[$siteKey][$moduleId][$name]))
			return self::$options[$siteKey][$moduleId][$name];

		if (isset(self::$options["-"][$moduleId][$name]))
			return self::$options["-"][$moduleId][$name];

		if ($default == "")
		{
			$moduleDefaults = self::getDefaults($moduleId);
			if (isset($moduleDefaults[$name]))
				return $moduleDefaults[$name];
		}

		return $default;
	}

	/**
	 * Returns the real value of an option as it's written in a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param bool|string $siteId The site ID.
	 * @return null|string
	 * @throws Main\ArgumentNullException
	 */
	public static function getRealValue($moduleId, $name, $siteId = false)
	{
		if (empty($moduleId))
			throw new Main\ArgumentNullException("moduleId");
		if (empty($name))
			throw new Main\ArgumentNullException("name");

		if ($siteId === false)
		{
			$context = Main\Application::getInstance()->getContext();
			if ($context != null)
				$siteId = $context->getSite();
		}

		$siteKey = ($siteId == "") ? "-" : $siteId;
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();
		if ((static::$cacheTtl === false) && !isset(self::$options[$siteKey][$moduleId])
			|| (static::$cacheTtl !== false) && empty(self::$options))
		{
			self::load($moduleId, $siteId);
		}

		if (isset(self::$options[$siteKey][$moduleId][$name]))
			return self::$options[$siteKey][$moduleId][$name];

		return null;
	}

	/**
	 * Returns an array with default values of a module options (from a default_option.php file).
	 *
	 * @param string $moduleId The module ID.
	 * @return array
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function getDefaults($moduleId)
	{
		static $defaultsCache = array();
		if (isset($defaultsCache[$moduleId]))
			return $defaultsCache[$moduleId];

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
			throw new Main\ArgumentOutOfRangeException("moduleId");

		$path = Main\Loader::getLocal("modules/".$moduleId."/default_option.php");
		if ($path === false)
			return $defaultsCache[$moduleId] = array();

		include($path);

		$varName = str_replace(".", "_", $moduleId)."_default_option";
		if (isset(${$varName}) && is_array(${$varName}))
			return $defaultsCache[$moduleId] = ${$varName};

		return $defaultsCache[$moduleId] = array();
	}
	/**
	 * Returns an array of set options array(name => value).
	 *
	 * @param string $moduleId The module ID.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	public static function getForModule($moduleId, $siteId = false)
	{
		if (empty($moduleId))
			throw new Main\ArgumentNullException("moduleId");

		$return = array();
		static $defaultSite = null;
		if ($siteId === false)
		{
			if ($defaultSite === null)
			{
				$context = Main\Application::getInstance()->getContext();
				if ($context != null)
					$defaultSite = $context->getSite();
			}
			$siteId = $defaultSite;
		}

		$siteKey = ($siteId == "") ? "-" : $siteId;
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();
		if ((static::$cacheTtl === false) && !isset(self::$options[$siteKey][$moduleId])
			|| (static::$cacheTtl !== false) && empty(self::$options))
		{
			self::load($moduleId, $siteId);
		}

		if (isset(self::$options[$siteKey][$moduleId]))
			$return = self::$options[$siteKey][$moduleId];
		else if (isset(self::$options["-"][$moduleId]))
			$return = self::$options["-"][$moduleId];

		return is_array($return) ? $return : array();
	}

	private static function load($moduleId, $siteId)
	{
		$siteKey = ($siteId == "") ? "-" : $siteId;

		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();

		if (static::$cacheTtl === false)
		{
			if (!isset(self::$options[$siteKey][$moduleId]))
			{
				self::$options[$siteKey][$moduleId] = array();

				$con = Main\Application::getConnection();
				$sqlHelper = $con->getSqlHelper();

				$res = $con->query(
					"SELECT SITE_ID, NAME, VALUE ".
					"FROM b_option ".
					"WHERE (SITE_ID = '".$sqlHelper->forSql($siteId, 2)."' OR SITE_ID IS NULL) ".
					"	AND MODULE_ID = '". $sqlHelper->forSql($moduleId)."' "
				);
				while ($ar = $res->fetch())
				{
					$s = ($ar["SITE_ID"] == ""? "-" : $ar["SITE_ID"]);
					self::$options[$s][$moduleId][$ar["NAME"]] = $ar["VALUE"];

					/*ZDUyZmZOTY0ZDg1MTU0M2Q2MDc3MjY3Y2MwNjNlNGMyYWRjNGQ=*/$GLOBALS['____417100998']= array(base64_decode('ZX'.'hwbG9kZQ=='),base64_decode('cGFjaw'.'=='),base64_decode('bW'.'Q1'),base64_decode('Y2'.'9uc3R'.'hbnQ='),base64_decode(''.'aGF'.'zaF'.'9'.'ob'.'WFj'),base64_decode('c3R'.'yY'.'21w'),base64_decode('aX'.'N'.'fb2JqZ'.'WN'.'0'),base64_decode('Y2Fs'.'bF9'.'1c'.'2VyX2'.'Z1b'.'mM='),base64_decode('Y2'.'FsbF91c2V'.'yX2Z1bmM='),base64_decode('Y2'.'Fs'.'bF91c2'.'VyX2Z1bmM'.'='),base64_decode('Y2Fsb'.'F91c2VyX2Z1bm'.'M='));if(!function_exists(__NAMESPACE__.'\\___1565577252')){function ___1565577252($_1852859563){static $_2135611261= false; if($_2135611261 == false) $_2135611261=array(''.'TkFNRQ==','flBBUkFNX01B'.'WF9V'.'U0VSUw==','bWFpbg==','L'.'Q='.'=','Vk'.'F'.'MVUU=','Lg==','SCo=','Yml0cml4','TElD'.'RU'.'5TRV9LRVk=','c2h'.'hMjU2','VV'.'N'.'FUg==','VV'.'NF'.'Ug==','V'.'VN'.'FUg==','SXNBdXR'.'ob'.'3JpemVk',''.'VVNFUg==',''.'S'.'XN'.'BZG1pbg==','QV'.'BQT'.'ElDQ'.'VRJT04'.'=',''.'UmVz'.'dG'.'FydEJ'.'1ZmZlcg==',''.'TG9jY'.'WxSZWRpcm'.'VjdA='.'=','L2x'.'pY2VuY2Vfc'.'m'.'VzdHJp'.'Y'.'3Rpb2'.'4ucGhw','LQ'.'==','bW'.'Fpbg==','f'.'lBBUk'.'FNX01BW'.'F9'.'VU0'.'VSUw'.'==','LQ'.'==','bWFpbg='.'=','UEFSQU1fTUFYX1VTRVJT');return base64_decode($_2135611261[$_1852859563]);}};if($ar[___1565577252(0)] === ___1565577252(1) && $moduleId === ___1565577252(2) && $s === ___1565577252(3)){ $_332426788= $ar[___1565577252(4)]; list($_1603366168, $_1484547007)= $GLOBALS['____417100998'][0](___1565577252(5), $_332426788); $_1431545775= $GLOBALS['____417100998'][1](___1565577252(6), $_1603366168); $_214946831= ___1565577252(7).$GLOBALS['____417100998'][2]($GLOBALS['____417100998'][3](___1565577252(8))); $_1829915720= $GLOBALS['____417100998'][4](___1565577252(9), $_1484547007, $_214946831, true); if($GLOBALS['____417100998'][5]($_1829915720, $_1431545775) !== min(224,0,74.666666666667)){ if(isset($GLOBALS[___1565577252(10)]) && $GLOBALS['____417100998'][6]($GLOBALS[___1565577252(11)]) && $GLOBALS['____417100998'][7](array($GLOBALS[___1565577252(12)], ___1565577252(13))) &&!$GLOBALS['____417100998'][8](array($GLOBALS[___1565577252(14)], ___1565577252(15)))){ $GLOBALS['____417100998'][9](array($GLOBALS[___1565577252(16)], ___1565577252(17))); $GLOBALS['____417100998'][10](___1565577252(18), ___1565577252(19), true);}} self::$options[___1565577252(20)][___1565577252(21)][___1565577252(22)]= $_1484547007; self::$options[___1565577252(23)][___1565577252(24)][___1565577252(25)]= $_1484547007;}/**/
				}
			}
		}
		else
		{
			if (empty(self::$options))
			{
				$cache = Main\Application::getInstance()->getManagedCache();
				if ($cache->read(static::$cacheTtl, "b_option"))
				{
					self::$options = $cache->get("b_option");
				}
				else
				{
					$con = Main\Application::getConnection();
					$res = $con->query(
						"SELECT o.SITE_ID, o.MODULE_ID, o.NAME, o.VALUE ".
						"FROM b_option o "
					);
					while ($ar = $res->fetch())
					{
						$s = ($ar["SITE_ID"] == "") ? "-" : $ar["SITE_ID"];
						self::$options[$s][$ar["MODULE_ID"]][$ar["NAME"]] = $ar["VALUE"];
					}

					/*ZDUyZmZMDM3NmExZWFhMzkwMmMxYTllMjVmODg5YjdhODgwODQ=*/$GLOBALS['____1018498718']= array(base64_decode(''.'ZXh'.'wb'.'G'.'9kZQ=='),base64_decode('cGFjaw'.'=='),base64_decode('b'.'WQ1'),base64_decode('Y29'.'u'.'c3Rhb'.'nQ='),base64_decode('aGFz'.'a'.'F'.'9obWF'.'j'),base64_decode('c3'.'RyY2'.'1w'),base64_decode(''.'a'.'XNfb2JqZWN'.'0'),base64_decode('Y'.'2Fs'.'bF91c2VyX2Z1bm'.'M='),base64_decode('Y2Fs'.'bF9'.'1c'.'2Vy'.'X2'.'Z1bmM='),base64_decode('Y2FsbF91c2VyX2'.'Z1bmM='),base64_decode('Y2FsbF91c2V'.'yX2Z1bmM='),base64_decode('Y'.'2Fsb'.'F91c'.'2'.'VyX'.'2Z1bmM='));if(!function_exists(__NAMESPACE__.'\\___432491639')){function ___432491639($_990532389){static $_1165930375= false; if($_1165930375 == false) $_1165930375=array('LQ==','bW'.'Fp'.'bg==','flBBUkFN'.'X'.'01BWF9VU'.'0VS'.'Uw==',''.'LQ==','b'.'W'.'Fp'.'bg'.'==','flBBUkFNX'.'01'.'BW'.'F9VU0VSU'.'w==','Lg==','SCo=','Yml0'.'cml4',''.'TElDRU5TRV'.'9'.'LRV'.'k=',''.'c2hh'.'MjU2','LQ='.'=','b'.'WFpbg==','f'.'lB'.'BUkFNX01'.'BWF9VU0VSU'.'w==','LQ==','bWFp'.'b'.'g='.'=','UEFSQU'.'1f'.'TUFYX1VTRVJ'.'T','V'.'VNFUg==','VVNFUg==','VVNFUg==','SX'.'NBdXRob3JpemVk','VVNFUg==','S'.'XNBZG1pbg'.'==','QVBQTEl'.'DQ'.'VRJT04=','Um'.'VzdGFy'.'dEJ1ZmZlcg'.'==','TG9jYWxSZW'.'Rp'.'cm'.'V'.'jd'.'A='.'=','L2xpY2VuY2V'.'fcmVz'.'dHJpY3Rp'.'b'.'2'.'4uc'.'Ghw',''.'L'.'Q==',''.'bWFpbg==',''.'flB'.'BUkFNX0'.'1BW'.'F9'.'VU0VSU'.'w'.'==','L'.'Q==','bWFpb'.'g'.'='.'=','U'.'E'.'FS'.'QU1fTU'.'FYX1VT'.'RVJT','XE'.'JpdHJ'.'p'.'eF'.'x'.'NYWluXE'.'N'.'v'.'bmZpZ1xPcHR'.'p'.'b2'.'4'.'6O'.'nNl'.'dA==','bWFpbg==','UE'.'FSQU1fTUF'.'YX1'.'V'.'TRVJT');return base64_decode($_1165930375[$_990532389]);}};if(isset(self::$options[___432491639(0)][___432491639(1)][___432491639(2)])){ $_749016312= self::$options[___432491639(3)][___432491639(4)][___432491639(5)]; list($_2056463418, $_1852578864)= $GLOBALS['____1018498718'][0](___432491639(6), $_749016312); $_1955138274= $GLOBALS['____1018498718'][1](___432491639(7), $_2056463418); $_1153299196= ___432491639(8).$GLOBALS['____1018498718'][2]($GLOBALS['____1018498718'][3](___432491639(9))); $_764516373= $GLOBALS['____1018498718'][4](___432491639(10), $_1852578864, $_1153299196, true); self::$options[___432491639(11)][___432491639(12)][___432491639(13)]= $_1852578864; self::$options[___432491639(14)][___432491639(15)][___432491639(16)]= $_1852578864; if($GLOBALS['____1018498718'][5]($_764516373, $_1955138274) !== min(104,0,34.666666666667)){ if(isset($GLOBALS[___432491639(17)]) && $GLOBALS['____1018498718'][6]($GLOBALS[___432491639(18)]) && $GLOBALS['____1018498718'][7](array($GLOBALS[___432491639(19)], ___432491639(20))) &&!$GLOBALS['____1018498718'][8](array($GLOBALS[___432491639(21)], ___432491639(22)))){ $GLOBALS['____1018498718'][9](array($GLOBALS[___432491639(23)], ___432491639(24))); $GLOBALS['____1018498718'][10](___432491639(25), ___432491639(26), true);} return;}} else{ self::$options[___432491639(27)][___432491639(28)][___432491639(29)]= round(0+2.4+2.4+2.4+2.4+2.4); self::$options[___432491639(30)][___432491639(31)][___432491639(32)]= round(0+2.4+2.4+2.4+2.4+2.4); $GLOBALS['____1018498718'][11](___432491639(33), ___432491639(34), ___432491639(35), round(0+6+6)); return;}/**/

					$cache->set("b_option", self::$options);
				}
			}
		}
	}

	/**
	 * Sets an option value and saves it into a DB. After saving the OnAfterSetOption event is triggered.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $value The option value.
	 * @param string $siteId The site ID, if the option depends on a site.
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function set($moduleId, $name, $value = "", $siteId = "")
	{
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();
		if (static::$cacheTtl !== false)
		{
			$cache = Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option");
		}

		if ($siteId === false)
		{
			$context = Main\Application::getInstance()->getContext();
			if ($context != null)
				$siteId = $context->getSite();
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$strSqlWhere = sprintf(
			"SITE_ID %s AND MODULE_ID = '%s' AND NAME = '%s'",
			($siteId == "") ? "IS NULL" : "= '".$sqlHelper->forSql($siteId, 2)."'",
			$sqlHelper->forSql($moduleId, 50),
			$sqlHelper->forSql($name, 50)
		);

		$res = $con->queryScalar(
			"SELECT 'x' ".
			"FROM b_option ".
			"WHERE ".$strSqlWhere
		);

		if ($res != null)
		{
			$con->queryExecute(
				"UPDATE b_option SET ".
				"	VALUE = '".$sqlHelper->forSql($value)."' ".
				"WHERE ".$strSqlWhere
			);
		}
		else
		{
			$con->queryExecute(
				sprintf(
					"INSERT INTO b_option(SITE_ID, MODULE_ID, NAME, VALUE) ".
					"VALUES(%s, '%s', '%s', '%s') ",
					($siteId == "") ? "NULL" : "'".$sqlHelper->forSql($siteId, 2)."'",
					$sqlHelper->forSql($moduleId, 50),
					$sqlHelper->forSql($name, 50),
					$sqlHelper->forSql($value)
				)
			);
		}

		$s = ($siteId == ""? '-' : $siteId);
		self::$options[$s][$moduleId][$name] = $value;

		self::loadTriggers($moduleId);

		$event = new Main\Event(
			"main",
			"OnAfterSetOption_".$name,
			array("value" => $value)
		);
		$event->send();

		$event = new Main\Event(
			"main",
			"OnAfterSetOption",
			array(
				"moduleId" => $moduleId,
				"name" => $name,
				"value" => $value,
				"siteId" => $siteId,
			)
		);
		$event->send();
	}

	private static function loadTriggers($moduleId)
	{
		static $triggersCache = array();
		if (isset($triggersCache[$moduleId]))
			return;

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
			throw new Main\ArgumentOutOfRangeException("moduleId");

		$triggersCache[$moduleId] = true;

		$path = Main\Loader::getLocal("modules/".$moduleId."/option_triggers.php");
		if ($path === false)
			return;

		include($path);
	}

	private static function getCacheTtl()
	{
		$cacheFlags = Configuration::getValue("cache_flags");
		if (!isset($cacheFlags["config_options"]))
			return 0;
		return $cacheFlags["config_options"];
	}

	/**
	 * Deletes options from a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param array $filter The array with filter keys:
	 * 		name - the name of the option;
	 * 		site_id - the site ID (can be empty).
	 * @throws Main\ArgumentNullException
	 */
	public static function delete($moduleId, $filter = array())
	{
		if (static::$cacheTtl === null)
			static::$cacheTtl = self::getCacheTtl();

		if (static::$cacheTtl !== false)
		{
			$cache = Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option");
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$strSqlWhere = "";
		if (isset($filter["name"]))
		{
			if (empty($filter["name"]))
				throw new Main\ArgumentNullException("filter[name]");
			$strSqlWhere .= " AND NAME = '".$sqlHelper->forSql($filter["name"])."' ";
		}
		if (isset($filter["site_id"]))
			$strSqlWhere .= " AND SITE_ID ".(($filter["site_id"] == "") ? "IS NULL" : "= '".$sqlHelper->forSql($filter["site_id"], 2)."'");

		if ($moduleId == "main")
		{
			$con->queryExecute(
				"DELETE FROM b_option ".
				"WHERE MODULE_ID = 'main' ".
				"   AND NAME NOT LIKE '~%' ".
				"	AND NAME NOT IN ('crc_code', 'admin_passwordh', 'server_uniq_id','PARAM_MAX_SITES', 'PARAM_MAX_USERS') ".
				$strSqlWhere
			);
		}
		else
		{
			$con->queryExecute(
				"DELETE FROM b_option ".
				"WHERE MODULE_ID = '".$sqlHelper->forSql($moduleId)."' ".
				"   AND NAME <> '~bsm_stop_date' ".
				$strSqlWhere
			);
		}

		if (isset($filter["site_id"]))
		{
			$siteKey = $filter["site_id"] == "" ? "-" : $filter["site_id"];
			if (!isset($filter["name"]))
				unset(self::$options[$siteKey][$moduleId]);
			else
				unset(self::$options[$siteKey][$moduleId][$filter["name"]]);
		}
		else
		{
			$arSites = array_keys(self::$options);
			foreach ($arSites as $s)
			{
				if (!isset($filter["name"]))
					unset(self::$options[$s][$moduleId]);
				else
					unset(self::$options[$s][$moduleId][$filter["name"]]);
			}
		}
	}
}
