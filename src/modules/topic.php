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
\\	Title:		TOPIC
//				
\\	Desc:		TOPIC command
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
class topic {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "topic";
	public $description = "Provides TOPIC compatibility";
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
		 * the function is a string reference to this class, the cmd_elmer method (function)
		 * The last param is expected parameter count for the command
		 * (both point to the same function which determines)
        */

		if (!CommandAdd($this->name, 'TOPIC', 'topic::cmd_topic', 0))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_topic($u)
    {
		$parv = explode(" ",$u['params']);
		$topic = ($t = explode(" :",$u['params'])) ? str_replace($t[0]." :","",$u['params']) : "";
		$chan = strtolower($parv[0]);
		self::update_topic($chan,utf8_encode($topic));
    }

	public static function update_topic($chan,$topic)
	{
		$conn = sqlnew();
		
		$prep = $conn->prepare("UPDATE ".sqlprefix()."channels SET topic = ? WHERE lower(channel) = ?");
		$prep->bind_param("ss",$topic,$chan);
		$prep->execute();
		$prep = $conn->prepare("UPDATE ".sqlprefix()."chaninfo SET topic = ? WHERE lower(channel) = ?");
		$prep->bind_param("ss", $topic,$chan);
		$prep->execute();
		$prep->close();
	}
}
