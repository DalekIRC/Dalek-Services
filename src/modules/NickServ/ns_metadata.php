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
\\	Title: METADATA
//	
\\	Desc: Implement IRCv3 'METADATA' compatability.
//	This is compatible with k4be's UnrealIRCd module:
\\	UnrealIRCd METADATA module README:
//	https://github.com/pirc-pl/unrealircd-modules/blob/master/README.md#metadata
\\
//	IMPORTANT: Requires module 'src/modules/md.php'
\\	
//	
\\	Version: 1.1
//				
\\	Author:	Valware
//				
*/
class ns_metadata {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "ns_metadata";
	public $description = "IRCv3 METADATA functionality";
	public $author = "Valware";
	public $version = "1.0";
    public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		/* Silence is golden */
	}

	/* To run when the class is destroyed/when the module is unloaded */
	/* Destruction: Here's where to clear up your globals or databases or anything */
	function __destruct()
	{
		/* Silence is golden */
	}


	/* Initialisation: Here's where to run things that should be run 
	 * after the module has been successfully registered.
	 * i.e. anything which has module data like the first parameter 
	 * of CommandAdd() which requires the module to be registered first
	*/
	function __init()
	{
		if (!CommandAdd($this->name, "METADATA", 'ns_metadata::usermeta', 0))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function usermeta($u)
	{
		$parv = explode(" ",$u['params']);

<<<<<<< HEAD
=======
		$target = new User($parv[0]);
		$uid = $target->uid;


>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		$key = $parv[1];
		
		$tok = explode("* :",$u['params']);
		$value = mb_substr($u['params'],strlen($tok[0]) + 3);
	
<<<<<<< HEAD
		md::md_add($parv[0],$key,$value);
		hook::run("usermeta", ['nick' => $parv[0], 'key' => $key, 'value' => $value]);
=======
		md::md_add($uid,$key,$value);
		hook::run("usermeta", ['nick' => $target, 'key' => $key, 'value' => $value]);
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
	}
	/* Send USERMETA command */
	public static function send_usermeta($from = NULL, $to, $key, $value)
	{
		global $cf;
		if (!$from)
<<<<<<< HEAD
			$from = $cf['sid'];
=======
			$from = $cf['servicesname'];
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a

		S2S(":$from METADATA $to $key * :$value");
		md::md_add($to,$key,$value);
		hook::run("usermeta", ['nick' => $to, 'key' => $key, 'value' => $value]);
	}
}
