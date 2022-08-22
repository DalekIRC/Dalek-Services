<?php

/*				
//	(C) 2021 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title: bbServ
//	
\\	Desc: Post to the chat whenever something new goes on the forum
//	
\\	Expects an "forumschan" setting in wordpress.conf
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class unreal_updates {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "unreal_updates";
	public $description = "Post to channel whenever there is a new version of UnrealIRCd";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		$conn = sqlnew();
		$conn->query("CREATE TABLE IF NOT EXISTS dalek_unreal_version (
				id int AUTO_INCREMENT NOT NULL,
				version varchar(255) NOT NULL,
				PRIMARY KEY (id)
			)");
		$conn->close();
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		hook::del("chanmsg", 'unreal_updates::fantasy');
	}


	function __init()
	{

		Events::Add(servertime(), 0, 300, 'unreal_updates::check_for_new_version', [], 'unreal_updates');
		if (IsConnected())
			unreal_updates::check_for_new_version(NULL);
		hook::func("chanmsg", 'unreal_updates::fantasy');
		return true;
	}

	static function fantasy($u)
	{
		global $chanserv;
		$cs = Client::find($chanserv['nick']);
		$parv = split($u['params']);
		if (strcasecmp(mb_substr($parv[1],1),"!unreal") || $u['dest'] !== "#PossumsOnly")
			return;
		$json = (array)json_decode(file_get_contents("https://www.unrealircd.org/downloads/list.json"));
		if ($json)
		{
			foreach($json as $latest)
			{
				if (!isset($latest->Stable))
					continue;
				$cs->msg("#PossumsOnly",bold("Latest UnrealIRCd version:")." ".$latest->Stable->version);
				$cs->msg("#PossumsOnly",bold("Download link:")." ".$latest->Stable->downloads->src);
			}
		}
	}

	static function check_for_new_version($u)
	{
		$latest = NULL;
		$json = (array)json_decode(file_get_contents("https://www.unrealircd.org/downloads/list.json"));
		if ($json)
		{
			foreach($json as $lates)
			{
				if (!isset($lates->Stable))
					continue;
				$version = $lates->Stable->version;
				$latest = $lates;
			}
		}
		else return;
		if ($version > $v = self::get_last())
		{
			self::set_last($version);
			global $chanserv;
			$cs = Client::find($chanserv['nick']);
			if ($v)
				$cs->msg("#PossumsOnly","UnrealIRCd v$version released! - $latest->Stable->downloads->src");
			else
				$cs->msg("#PossumsOnly","First run: Got UnrealIRCd version $version. I'll let you know when there's a new version released!");
		}
	}
	static function set_last($id)
	{
		$conn = sqlnew();
		if (!unreal_updates::get_last())
		{
			$conn->query("TRUNCATE TABLE dalek_unreal_version");
			$conn->query("INSERT INTO dalek_unreal_version (version) VALUES ('$id')");
		}
		else
			$conn->query("UPDATE dalek_unreal_version SET version = '$id' WHERE id = 1");
	}
	static function get_last()
	{
		$conn = sqlnew();
		$result = $conn->query("SELECT * FROM dalek_unreal_version WHERE id = 1");
		if (!$result || $result->num_rows < 1)
			return 0;

		$row = $result->fetch_assoc();
		return $row['version'];

	}
}