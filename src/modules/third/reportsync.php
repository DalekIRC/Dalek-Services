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
\\	Title:		REPORTSYNC
//				
\\	Desc:		REPORTSYNC command
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
class reportsync {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "reportsync";
	public $description = "Provides REPORTSYNC compatibility (Gottem's report module)";
	public $author = "Valware";
	public $version = "1.0";
    public $official = true;

	/* To run when this class is created/when the module is loaded */
	/* Construction: Here's where you'll wanna initialise any globals or databases or anything */
	function __construct()
	{
		reportsync::table_init();
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

		if (!CommandAdd($this->name, 'REPORTSYNC', 'reportsync::cmd_reportsync', 0))
			return false;

		return true;
	}


	/* The public command function that we are calling with CommandAdd in __init.
	 * In this example (and throughout the source), $u contains an array with
	 * information passed along by the caller
	 * $u['reportsync'] = User object
	 */
	public static function cmd_reportsync($u)
    {
		$parv = split($u['params']);

		$add = $parv[0][0] == "+" ? 1 : 0;
		$number = $parv[0][1];

		$timestamp = $parv[1];
		$reporter = $parv[2];
		$report = cut_first_from($u['params'],":");

		if ($add)
			reportsync::add_report($number,$timestamp,$reporter,$report);
		else
			reportsync::del_report($number,$timestamp,$reporter,$report);
    }

	public static function add_report($number, $timestamp, $reporter, $report)
	{
		$conn = sqlnew();
		$prep = $conn->prepare("INSERT INTO dalek_reports (id, timestamp, reporter, report) VALUES (?, ?, ?, ?)");
		$prep->bind_param("iiss", $number, $timestamp, $reporter, $report);
		$prep->execute();
		SVSLog(bold("REPORT").": [Reporter, $reporter] [Report: $report]");
	}
	public static function del_report($number, $timestamp, $reporter, $report)
	{
		$conn = sqlnew();
		$prep = $conn->prepare("DELETE FROM dalek_reports WHERE id = ?");
		$prep->bind_param("i",$number);
		$prep->execute();
	}
	public static function table_init()
	{
		$conn = sqlnew();

		$conn->query("
			CREATE TABLE IF NOT EXISTS dalek_reports (
				id int AUTO_INCREMENT NOT NULL,
				timestamp bigint NOT NULL,
				reporter varchar(255) NOT NULL,
				report varchar(255) NOT NULL,
				PRIMARY KEY(id)
			)
		");
	}
}
