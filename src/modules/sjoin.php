<?php
/*				
//	(C) 2022 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\				v3
//				
\\				
//				
\\	Title:		SJOIN
//				
\\	Desc:		SJOIN command
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

/* class name needs to be the same name as the file */
class sjoin {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "sjoin";
	public $description = "Provides SJOIN compatibility";
	public $author = "Valware";
	public $version = "1.0";
    public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
	
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		/* Params: CommandAdd( this module name, command keyword, function, parameter count)
		 * the function is a string reference to this class, the csjoin_elmer method (function)
		 * The last param is expected parameter count for the command
		 * (both point to the same function which determines)
        */

		if (!CommandAdd($this->name, 'SJOIN', 'sjoin::cmd_sjoin', 0))
			return false;
		hook::func("SJOIN", 'sjoin::sjoin_add');
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_sjoin($u)
    {
        $parv = explode(" ",$u['params']);
		
		$sid = $u['nick']->uid;
		$timestamp = $parv[0];
		$chan = $parv[1];
		$modes = ($parv[2][0] == ":") ? "" : $parv[2];
		
		$array = array(
			"sid" => $sid,
			"timestamp" => $timestamp,
			"channel" => $chan,
			"modes" => $modes,
			"full" => ":$sid SJOIN ".$u['params']);
		hook::run("SJOIN", $array);
    }
	public static function sjoin_add($u)
	{
		global $sql;
	
		$tokens = explode(" ",$u['full']);
		$chan = $tokens[3];
		$list = explode(" :",$u['full']);
		$parv = explode(" ",$list[count($list) - 1]);
		
		if (!$parv)
		{
			return;
		}
		for ($p = 0; isset($parv[$p]); $p++)
		{
			$mode = "";
			$item = $parv[$p];
			loopback:
			if (!isset($item[0]))
			{
				continue;
			}
			if ($item[0] == "+")
			{
				$mode .= "v";
				$item = mb_substr($item,1);
				goto loopback;
			}
			if ($item[0] == "%")
			{
				$mode .= "h";
				$item = mb_substr($item,1);
				goto loopback;
			}
			if ($item[0] == "@")
			{
				$mode .= "o";
				$item = mb_substr($item,1);
				goto loopback;
			}
			if ($item[0] == "~")
			{
				$mode .= "a";
				$item = mb_substr($item,1);
				goto loopback;
			}
			if ($item[0] == "*")
			{
				$mode .= "q";
				$item = mb_substr($item,1);
				goto loopback;
			}
			if ($item[0] == "<")
				bie($chan,$item);
			
			if (isset($mode))
			{
				$sql::insert_ison($chan,$item,$mode);
				$array = array('chan' => $chan, 'nick' => $item);

				hook::run("join", $array);
			}
		}
	}
}
