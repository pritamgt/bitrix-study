<?

namespace Bitrix\Mobile;

use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Localization;
use Bitrix\Main\Web\Json;

class ComponentManager
{
	private static $componentPath = "/bitrix/components/bitrix/mobile.jscomponent/jscomponents/";

	public static function getComponentVersion($componentName)
	{
		$componentFolder = new Directory(Application::getDocumentRoot() . self::$componentPath . $componentName);
		$versionFile = new File($componentFolder->getPath()."/version.php");
		if($versionFile->isExists())
		{
			$versionDesc = include($versionFile->getPath());
			return $versionDesc["version"];
		}

		return 1;
	}

	public static function getComponentPath($componentName)
	{
		return "/mobile/mobile_component/$componentName/?version=". self::getComponentVersion($componentName);
	}

}