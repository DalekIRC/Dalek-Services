<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\				
//				
\\	Title:		Config parser
//				
\\	Desc:		Allows doing config things lol
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/

define("CONF_SYMBOL", "[CONFIG] ");

class Conf
{

	static $arrae = []; // de arrae we is using lol
	static $list = NULL;
	static function run($by = "SSH Admin") // to run on startup
	{
		global $cf;
		$confile = "conf/dalektest.conf";

		/* empty the list first */
		self::$arrae = [];
		if (!file_exists($confile))
			config_fail("No configuration file detected.\n".
				"Please make sure you have a valid /conf/dalek.conf file\n",$by);
		$file = file_get_contents($confile);
		if (!$file)
		  config_fail("Config file was found, but there were no contents.\n".
				"Please make sure you have a valid /conf/dalek.conf file\n");
	   
		Conf::parse_config($file, $by);
		
		self::$list = (object)self::$arrae;
		//var_dump(self::$list);
	}

	/* Turns a file as a string into an array of objects
	 * representing each config block.
	 */
	static function parse_config(&$file, $by)
	{
		$config = array(); // de arrae we going to return and get a fkn refund

		/* Strip comments like this one lol */
		$file = preg_replace('/\/\*(.|\s)*?\*\//', '
		', $file);
		$file = preg_replace('/\/\/(.|\s)*?\n/', '
		', $file);
		$file = preg_replace('/#(.|\s)*?\n/', '
		', $file);
		$file = str_replace("	"," ",$file);
		//$file = str_replace("  ", " ",$file);
		$c = split($file); // split words up into arrae
		$line = 1; // where we are keeping track of line numbers for the error printer

		$r = "";

		$entry = NULL; // an entry for the config that we add below;
		$option = NULL; // the option for the entry

		if (dcount_chars($file,"{") !== dcount_chars($file,"}"))
			config_fail("Odd amount of curly braces", $by);

		foreach ($c as $i => &$word)
		{
			for ($i = 0; isset($word[$i]); $i++)
			{
				$char = $word[$i];
				$double = (isset($word[$i + 1])) ? $word[$i].$word[$i + 1] : $word[$i];
				
					
				// if it's a newline, increment the line count and continue
				if ($char == "\n")
				{
					$r .= "\n";
					$line++;
					continue;
				}

				// if it's a tab, ignore it
				if ($char == "\t")
				{
					$r .= " ";
					continue;
				}
				
				$r .= $char;
			}
			
			$r .= " ";
		}
		var_dump((array)$r);
		if (BadPtr($r))
			config_fail("Invalid configuration. Is it empty?", $by);

		conf2json2array2obj($r);
	}
}

function config_fail($reason = "No reason provided", $user = NULL)
{
	SVSLog("Configuration failed to pass: $reason");

	if (IsConnected() && $user)
		sendnotice($user, NULL, NULL, "Configuration failed to pass:", $reason);

	if (!IsConnected()) // if we're not connected yet, let it die
		die($reason);
}


function conf2json2array2obj(&$conf)
{
	$c = explode("\n",$conf);
	$Conf = [];
	$json = "{";
	foreach($c as $line)
	{
		if (BadPtr($line))
			continue;
		
		
		$tok = split($line);
		
		for ($i = 0; $i <= count($tok) - 1; $i++)
		{
			if ($tok[0] == "{")
				strcat($json," { ");

			elseif ($tok[0] == "}")
				strcat($json, " }, ");

			elseif (strstr($tok[0],";"))
				$value = $tok[0];

		}
	}
}


class ConfigEntry {

	/* Fillable */
	public $name = NULL;
	public $value = NULL; // can be 
	public $modname = NULL;
	public $next,$prev = NULL;

	function __construct(){}
}

