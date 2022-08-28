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

/* Some defines */
define('TKL_GLOBAL', "G");
define('TKL_ZAP', "Z");
define('TKL_SHUN', "s");
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
		$ut = $parv[2];
		$mask = $parv[3];
		$set_by = $parv[4];
		$expiry = $parv[5];
		$timestamp = $parv[6];

		$parv = explode(" :",$u['params']);
		$reason = mb_substr($u['params'],strlen($parv[0]) + 2);

		if ($add)
			self::add_tkl($type,$ut,$mask,$set_by,$expiry,$timestamp,$reason);
		else
			self::del_tkl($type,$ut,$mask,$set_by,$expiry,$timestamp,$reason);

		$array = array(
			'add' => $add,
			'type' => $type,
			'ut' => $ut,
			'mask' => $mask,
			'set_by' => $set_by,
			'expiry' => $expiry,
			'timestamp' => $timestamp,
			'reason' => $reason
		);
		hook::run("TKL", $array);
	}
	public static function table_init($u)
	{	
		$conn = sqlnew();
		$conn->multi_query(
			"CREATE TABLE IF NOT EXISTS dalek_tkldb (
				id int AUTO_INCREMENT NOT NULL,
				type varchar(2) NOT NULL,
				ut varchar(100) NOT NULL,
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

	public static function add_tkl($type,$ut,$mask,$set_by,$expiry,$timestamp,$reason)
	{
		$conn = sqlnew();
		$prep = $conn->prepare("
			INSERT INTO dalek_tkldb (
				type,
				ut,
				mask,
				set_by,
				expiry,
				timestamp,
				reason
			) VALUES (?,?,?,?,?,?,?)"
		);

		$prep->bind_param("ssssiis",$type,$ut,$mask,$set_by,$expiry,$timestamp,$reason);
		$prep->execute();
		$conn->close();
	}
	public static function del_tkl($type,$ut,$mask)
	{
		$conn = sqlnew();
		$prep = $conn->prepare("
			DELETE FROM dalek_tkldb WHERE
				type = ? AND
				ut = ? AND
				mask = ?"
		);

		$prep->bind_param("sss",$type,$ut,$mask);
		$prep->execute();
		$conn->close();
	}
}

function get_tkl($type,$mask,$ut = "*")
{
	$conn = sqlnew();
	$prep = $conn->prepare("SELECT * FROM dalek_tkldb WHERE type = ? AND ut = ? AND mask = ?");
	$prep->bind_param("sss",$type,$ut,$mask);
	$prep->execute();
	$result = $prep->get_result();
	if (!$result || !$result->num_rows)
		return false;

	$return = [];
	while($row = $result->fetch_assoc())
		$return[] = $row;

	$conn->close();
	return $return;
}

/* wrapper for other *-line commands */
define('TKL_ADD','+');
define('TKL_DEL','-');
function _line($op,$type,$user = "*",$host,$from,$expiry = 0,$reason = "No reason")
{
	if (!$op || !$type || !$host) /* Minimum requirements */
		return false;

	if (!strcmp($type,TKL_GLOBAL) || !strcmp($type,TKL_ZAP))
	{
		if (!strcmp($host,"*@*"))
			return false;
		
		if (!strcmp($op,TKL_ADD) && !($tkl = get_tkl($type,$host,$ut)))
		{
			S2S("TKL $op $type $user $host $from $expiry :$reason");
			tkl::add_tkl($type,$user,$host,$from,$expiry,servertime(),$reason);
		}
		elseif (!strcmp($op,TKL_DEL) && ($tkl = get_tkl($type,$host,$ut)))
		{
			tkl::del_tkl($type,$ut,$host);
			foreach($tkl as $result)
				S2S("TKL $op $type $ut $host ".$result['set_by']." ".$result['expiry']." ".$result['timestamp']." :".$result['reason']);
		}
	}
	return true;
}

function gline($string, $from = NULL)
{
	$parv = explode(" ",$string);
	if (!count($parv))
		return false;

	// if no + or - then we assume it's +
	$op = (strcmp($parv[0][0],TKL_ADD) && strcmp($parv[0][0],TKL_DEL)) ? "+" : $parv[0][0];

	// remove the first char if it's a + or -
	$parv[0] = (strcmp($parv[0][0],TKL_ADD) && strcmp($parv[0][0],TKL_DEL)) ? $parv[0] : mb_substr($parv[0],1);

	/* format our ban mask */
	if (strpos($parv[0],"@"))
	{
		$split = explode("@",$parv[0]);
		$ut = $split[0];
		$host = $split[1];
	}
	elseif (strpos($parv[0],":"))
	{
		$split = explode(":",$parv[0]);
		$ut = $split[0];
		$host = $split[1];
	}
	else
	{
		$ut = "*";
		$host = $parv[0];
	}
	

	/* Calculate our expiry time, default is 0 */
	if (isset($parv[1]))
		$exp = servertime() + $parv[1];
	else
		$exp = 0;


	/* Jam the reason into a string. There's probably an easier way to do this,
	 * but this feels safest to me
	 */
	$reason = "";
	
	/* if the we have any word for the Reason */
	if (isset($parv[2]))
	{
		/* strip any magical colon */
		if ($parv[2][0] == ":")
			$parv[2] = mb_substr($parv[2],1);
	}
	/* make sure we didn't remove the entire reason ":" (no reason) or if no reason was provided */
	if (!isset($parv[2]) || !strlen($parv[2]))
		$parv[2] = "No reason";

	/* iterate over words and put them into the string... */
	for ($i = 2; isset($parv[$i]); $i++)
	{
		$reason .= $parv[$i];

		/* if there is a next parv, add a space for it */
		if (isset($parv[$i + 1]))
			$reason .= " ";
	}
	if ($op == TKL_ADD)
	{
		gline_add($host,$from,$reason,$exp,$ut);
		return true;
	}
	elseif ($op == TKL_DEL)
	{
		gline_del($host,$ut);
		return true;
	}
	return false;
}


function gzline($string, $from = NULL)
{
	$parv = explode(" ",$string);
	if (!count($parv))
		return false;

	// if no + or - then we assume it's +
	$op = (strcmp($parv[0][0],TKL_ADD) && strcmp($parv[0][0],TKL_DEL)) ? "+" : $parv[0][0];

	// remove the first char if it's a + or -
	$parv[0] = (strcmp($parv[0][0],TKL_ADD) && strcmp($parv[0][0],TKL_DEL)) ? $parv[0] : mb_substr($parv[0],1);

	/* format our ban mask */
	if (strpos($parv[0],"@"))
	{
		$split = explode("@",$parv[0]);
		$ut = $split[0];
		$host = $split[1];
	}
	elseif (strpos($parv[0],":"))
	{
		$split = explode(":",$parv[0]);
		$ut = $split[0];
		$host = $split[1];
	}
	else
	{
		$ut = "*";
		$host = $parv[0];
	}
	

	/* Calculate our expiry time, default is 0 */
	if (isset($parv[1]))
		$exp = servertime() + $parv[1];
	else
		$exp = 0;


	/* Jam the reason into a string. There's probably an easier way to do this,
	 * but this feels safest to me
	 */
	$reason = "";
	
	/* if the we have any word for the Reason */
	if (isset($parv[2]))
	{
		/* strip any magical colon */
		if ($parv[2][0] == ":")
			$parv[2] = mb_substr($parv[2],1);
	}
	/* make sure we didn't remove the entire reason ":" (no reason) or if no reason was provided */
	if (!isset($parv[2]) || !strlen($parv[2]))
		$parv[2] = "No reason";

	/* iterate over words and put them into the string... */
	for ($i = 2; isset($parv[$i]); $i++)
	{
		$reason .= $parv[$i];

		/* if there is a next parv, add a space for it */
		if (isset($parv[$i + 1]))
			$reason .= " ";
	}
	if ($op == TKL_ADD)
	{
		gzline_add($host,$from,$reason,$exp,$ut);
		return true;
	}
	elseif ($op == TKL_DEL)
	{
		gzline_del($host,$ut);
		return true;
	}
	return false;
}

function gline_add($host,$from = NULL,$reason = NULL,$expiry = 0,$ut = NULL)
{
	global $cf;

	if ($reason && strlen($reason))
		$reason = NULL;

	if (!$from)
		$from = $cf['sid'];

	if (_line(TKL_GLOBAL,TKL_ADD,$ut,$from,$expiry,$reason))
		return true;
	return false;
}

function gline_del($host,$ut = NULL)
{
	global $cf;

	if ($reason && strlen($reason))
		$reason = NULL;

	if (!$from)
		$from = $cf['sid'];

	if (_line(TKL_GLOBAL,TKL_DEL,$ut,$from,$expiry,$reason))
		return true;
	return false;
	
}


function gzline_add($host,$from = NULL,$reason = NULL,$expiry = 0,$ut = NULL)
{
	global $cf;

	if ($reason && strlen($reason))
		$reason = NULL;

	if (!$from)
		$from = $cf['sid'];

	if (_line(TKL_GZAP,TKL_ADD,$ut,$from,$expiry,$reason))
		return true;
	return false;
	
}

function gzline_del($host,$ut = NULL)
{
	global $cf;

	if ($reason && strlen($reason))
		$reason = NULL;

	if (!$from)
		$from = $cf['sid'];

	if (_line(TKL_ZAP,TKL_DEL,$ut,$from,$expiry,$reason))
		return true;
	return false;
	
}