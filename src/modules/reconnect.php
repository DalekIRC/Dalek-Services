<?php
/*				
//	(C) 2022 DalekIRC Services
\\				
//			dalek.services
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		Reconnect
//				
\\	Desc:		Reconnects to alternative uplinks
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class reconnect {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "reconnect";
	public $description = "Fallback connection";
	public $author = "Valware";
	public $version = "1.0";
	public $official = true;


	/** Our static list of servers to connect to */
	public static $serv_list = [];

	
	function __init()
	{
		

		hook::func(HOOKTYPE_ERROR, 'reconnect::do');
		hook::func(HOOKTYPE_CONFIGTEST, 'reconnect::config');
		if (IsConnected())
		{
			$array['errs'] = [];
			foreach(self::config($array) as $err)
				DebugLog($err);
		}

		return true;
	}

	public static function do(&$info)
	{
		Server::$isConnected = 0;
		$serv = &$info['serv_obj'];
		$input = glue($info['parv']);

		if (count(self::$serv_list) == 1) // if we've only got one server in the list...
		{
			if (strpos($input,'Throttled') !== false)
			{
				$serv->hear("Uh-oh, we've been throttled! Waiting 40 seconds and starting again.");
				sleep(40);
				$serv->shout("Reconnecting...");
				$serv = NULL;
			}
			elseif (strpos($input,'Timeout') !== false)
			{
				if (IsConnected())
				{
					$serv->hear("Connection issue. Trying again in 30 seconds");
					sleep(30);
					$serv = NULL;
				}
				else
				{
					die($serv->hear("Connection issue. Please check dalek.conf"));					
				}
				
			}
			else
			{
				$serv->hear("Unknown exit issue! Waiting 40 seconds and restarting");
				usleep(400000);
				$serv = NULL;
			}
			return;
		}
		if ($serv)
			$serv->hear("Connection lost: $input");

		
		/* else if we have more than one server, let's have a go at the next one. */
		for ($i = 0; ($server = self::$serv_list[$i]); $i++)
		{
			if (!strcmp($server->hostname,$serv->host))
			{
				if (isset(self::$serv_list[$i + 1]))
					$server = self::$serv_list[$i + 1];
					
				else
					$server = self::$serv_list[0];

				if ($server->hostname !== "127.0.0.1" && strcasecmp($server->hostname,"localhost"))
					$server->hostname = "tls://$server->hostname";
				$serv = new Server($server->hostname,$server->port,$server->password);
				break;
			}
		}
		DebugLog("Attempting connection to $serv->host...");
	}

	static function config(&$array)
	{
		/* clear our current list */
		self::$serv_list = [];

		$errs = &$array['errs'];

		$file_name = DALEK_CONF_DIR."/.settings.temp";

		if (!file_exists($file_name))
			return;

		$file = split(file_get_contents($file_name),"\n");
		$obj = new serv_object();
		foreach($file as $line)
		{
			$tok = split($line,"::");
			if ($tok[0] !== "link")
				continue;

			DebugLog("[reconnect] $line");
			/* if it's NULL (and not unset) set it */
			if (is_null($obj->{$tok[1]}))
				$obj->{$tok[1]} = $tok[2];
			
			else
			{
				$str = "Error: Unrecognised configuration item link::".$tok[1];
				DebugLog($str);
				$errs[]=$str;
				continue;
			}
			/* check if the object is full up */
			if (!is_null($obj->hostname) && !is_null($obj->port) && !is_null($obj->password))
			{
				self::$serv_list[] = $obj;
				$tok = NULL;
				$obj = new serv_object;
			}
		}
		/* return the array because why not */
		return $array;
	}
}

/* small server "Struct" */
class serv_object {
	public $hostname = NULL;
	public $port = NULL;
	public $password = NULL;
}