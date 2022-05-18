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
\\	Title:		MD
//				
\\	Desc:		MD command
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
class md {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "md";
	public $description = "Provides MD compatibility";
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

		if (!CommandAdd($this->name, 'MD', 'md::cmd_md', 0))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_md($u)
    {
        $parv = explode(" ",$u['params']);
		if ($parv[0] == "client")
		{
			$user = $parv[1];
			$key = $parv[2];
<<<<<<< HEAD
			$t = explode(" :",$u['params']);
			$value = (isset($t[1])) ? $t[1] : "";
			
			md::md_add($user,$key,$value);
=======
			$value = ($t = explode(" :",$u['params'])) ? $t[1] : "";
			if (!md::md_add($user,$key,$value))
			{ }
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		}
    }
	public static function md_add($person,$key,$value)
	{
		$conn = sqlnew();
		if (!$conn)
			return false;

		$prep = $conn->prepare("INSERT INTO dalek_user_meta (UID, meta_key, meta_data) VALUES (?, ?, ?)");
		$prep->bind_param("sss",$person,$key,$value);
		$prep->execute();
		$prep->close();
	}
}
