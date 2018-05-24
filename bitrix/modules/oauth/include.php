<?
global $DBType;

CModule::AddAutoloadClasses(
	"oauth",
	array(
		"COAuthBase" => "classes/general/oauth.php",
		"COAuthConstants" => "classes/general/oauth.php",
		"COAuthToken" => "classes/".$DBType."/oauth_token.php",
		"COAuthCode" => "classes/".$DBType."/oauth_code.php",
		"COAuthClient" => "classes/".$DBType."/oauth_client.php",
		"COAuthRefreshToken" => "classes/".$DBType."/oauth_refresh.php",
	)
);
?>