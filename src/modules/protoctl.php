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
\\	Title:		PROTOCTL
//				
\\	Desc:		PROTOCTL compatibility
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
class protoctl {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "protoctl";
	public $description = "Provides PROTOCTL compatibility";
	public $author = "Valware";
<<<<<<< HEAD
	public $version = "1.0";
	public $official = true;
=======
	public $protoctl = "1.0";
    public $official = true;
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a

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
		/* Params: CommandAdd( this module name, command keyword, function, parameter count) */
		/* the function is a string reference to this class, the cmd_elmer method (function) */
		/* The last param is expected parameter count for the command */
		/* (both point to the same function which determines) */

		if (!CommandAdd($this->name, 'PROTOCTL', 'protoctl::cmd_protoctl', 1))
			return false;
<<<<<<< HEAD
=======

		hook::func("preconnect", 'protoctl::table_init');
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_protoctl($u)
	{
<<<<<<< HEAD
		foreach ($u as $key => $value)
			log_to_disk("$key => $value");
		if (empty($u))
			return;
=======
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
		$parv = explode(" ",$u['params']);
		
		$conn = sqlnew();
		if (!$conn)
			return false;
		
		for ($i = 0; isset($parv[$i]); $i++)
		{
			$tok = explode("=",$parv[$i]);
			
			$key = $tok[0];
			$val = $tok[1] ?? false;
			
			$prep = $conn->prepare("INSERT INTO dalek_protoctl_meta (meta_key, meta_value) VALUES (?, ?)");
			
			/* Remembering which type each CHANMODE is  according to https://modern.ircdocs.horse/#mode-message */
			if ($key == "CHANMODES")
			{
				$modetok = explode(",",$val);
				
				
				for ($s = 0; isset($modetok[$s]); $s++)
				{
					$num = $s + 1;
					$fkey = "CHANMODES_TYPE".$num;
					$prep->bind_param("ss",$fkey,$modetok[$s]);
					$prep->execute();
				}
			}
			
			if ($key == "USERMODES")
			{
				$prep->bind_param("ss",$key,$val);
				$prep->execute();
			}
			
			if ($key == "PREFIX")
			{
				$mode = get_string_between($val,"(",")");
				$num = "-".strlen($mode);
				$prefix = substr($val,$num);
				$all = $mode.",".$prefix;
				$prep->bind_param("ss",$key,$all);
				$prep->execute();
			}
<<<<<<< HEAD
			if ($key == "SID")
			{
				$prep->bind_param("ss",$key,$val);
				$prep->execute();
			}
=======
>>>>>>> 1d6af964a27a04cb46dafb3c58b0c93538e7352a
			$prep->close();
		}
	}
	public static function table_init($u)
	{	
		$conn = sqlnew();
		$conn->multi_query(
			"CREATE TABLE IF NOT EXISTS dalek_protoctl_meta (
				id int AUTO_INCREMENT NOT NULL,
				meta_key varchar(255) NOT NULL,
				meta_value varchar(255) NOT NULL,
				PRIMARY KEY(id)
			);

			TRUNCATE TABLE dalek_protoctl_meta;
			DELETE FROM dalek_channel_meta WHERE meta_key = 'ban';
			DELETE FROM dalek_channel_meta WHERE meta_key = 'invite';
			DELETE FROM dalek_channel_meta WHERE meta_key = 'except'"
		);
	}
}


