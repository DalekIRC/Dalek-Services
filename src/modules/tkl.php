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
\\	Title:		TKL
//				
\\	Desc:		TKL compatibility
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
class tkl {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "tkl";
	public $description = "Provides TKL compatibility";
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
		/* Params: CommandAdd( this module name, command keyword, function, parameter count) */
		/* the function is a string reference to this class, the cmd_elmer method (function) */
		/* The last param is expected parameter count for the command */
		/* (both point to the same function which determines) */

		if (!CommandAdd($this->name, 'TKL', 'tkl::cmd_tkl', 1))
			return false;

		hook::func("preconnect", 'tkl::table_init');
		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['nick'] = User object
	 */
	public static function cmd_tkl($u)
	{
		$parv = explode(" ",$u['params']);
		$add = ($parv[0] == "+") ? true : false;
		$type = $parv[1];
		// $parv[2] = "*" always so we ignore this and don't use it
		$mask = $parv[3];
		$set_by = $parv[4];
		$expiry = $parv[5];
		$timestamp = $parv[6];

		$parv = explode(" :",$u['params']);
		$reason = mb_substr($u['params'],strlen($parv[0]) + 2);

		if ($add)
			self::add_tkl($type,$mask,$set_by,$expiry,$timestamp,$reason);
		else
			self::del_tkl($type,$mask,$set_by,$expiry,$timestamp,$reason);

		hook::run("TKL", array(
			'add' => $add,
			'type' => $type,
			'mask' => $mask,
			'set_by' => $set_by,
			'expiry' => $expiry,
			'timestamp' => $timestamp,
			'reason' => $reason
		));
	}
	public static function table_init($u)
	{	
		$conn = sqlnew();
		$conn->multi_query(
			"CREATE TABLE IF NOT EXISTS dalek_tkldb (
				id int AUTO_INCREMENT NOT NULL,
				type varchar(2) NOT NULL,
				mask varchar(255) NOT NULL,
				set_by varchar(255) NOT NULL,
				expiry bigint NOT NULL,
				timestamp bigint NOT NULL,
				reason varchar(255) NOT NULL,
				PRIMARY KEY(id)
			);

			TRUNCATE TABLE dalek_tkldb"
		);
	}

	public static function add_tkl($type,$mask,$set_by,$expiry,$timestamp,$reason)
	{
		$conn = sqlnew();
		$prep = $conn->prepare("
			INSERT INTO dalek_tkldb (
				type,
				mask,
				set_by,
				expiry,
				timestamp,
				reason
			) VALUES (?,?,?,?,?,?)"
		);

		$prep->bind_param("sssiis",$type,$mask,$set_by,$expiry,$timestamp,$reason);
		$prep->execute();
		$conn->close();
	}
	public static function del_tkl($type,$mask,$set_by,$expiry,$timestamp,$reason)
	{
		$conn = sqlnew();
		$prep = $conn->prepare("
			DELETE FROM dalek_tkldb WHERE
				type = ? AND
				mask = ? AND
				set_by = ? AND
				expiry = ? AND
				timestamp = ? AND
				reason = ?"
		);

		$prep->bind_param("sssiis",$type,$mask,$set_by,$expiry,$timestamp,$reason);
		$prep->execute();
		$conn->close();
	}
}