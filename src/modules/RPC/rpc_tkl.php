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
\\	Title: RPC User functions
//	
\\	Desc: Allows RPC callers to get information about users on IRC
//	
\\	Version: 1.0
//				
\\	Author:	Valware
//				
*/
class rpc_tkl {

	/* Module handle */
	/* $name needs to be the same name as the class and file lol */
	public $name = "rpc_tkl";
	public $description = "Provides RPC lookups for users";
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
		/* We automatically clear up things attached to the module information, like AddServCmd();
		 * so don't worry!
		*/
	}

	function __init()
	{
		$err = NULL;

		RPCHandlerAdd($this->name, "tkl.list", 'rpc_tkl::list', $err);
		RPCHandlerAdd($this->name, "tkl.add", 'rpc_tkl::add', $err);
		RPCHandlerAdd($this->name, "tkl.del", 'rpc_tkl::delete', $err);

		if ($err)
			return die(SVSLog($err));

		return true;
	}
	
	function list($id, $params)
	{
		$bans = [];
		$reply = rpc_new_reply();
		$conn = sqlnew();
		$result = $conn->query("SELECT * FROM dalek_tkldb");
		while($row = $result->fetch_assoc())
		{
			$ban = [];
			$ban['type'] = $row['type'];
			$ban['ident'] = $row['ut'];
			$ban['mask'] = $row['mask'];
			$ban['set by'] = $row['set_by'];
			$ban['expiry'] = $row['expiry'];
			$ban['timestamp'] = $row['timestamp'];
			$ban['reason'] = $row['reason'];
			$bans[] = $ban;
		}
		rpc_append_result($reply, $bans);
		rpc_append_id($reply, $id);
		rpc_send_reply($id, $reply);
	}
	function add($id, $params)
	{
		$errors = 0;
		$types = "sQGZ";
		$err_str = "";
		$err_type = NULL;
		foreach($params as $key => $value)
		{
			if ($errors)
				break;

			if ($key == "type")
			{
				if (strlen($value) != 1 || !strstr($types,$value))
				{
					++$errors;
					strcat($err_str, "Invalid type specified");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				$type = $key;
			}
			elseif ($key == "ident")
			{
				if (strlen($value) > 100)
				{
					++$errors;
					strcat($err_str, "Ident specified too long - limit is 100 chars");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				
				for ($i = 0; !BadPtr($value[$i]); $i++)
				{
					if (!ctype_alnum($value[$i]) && $value[$i] !== "-" && $value[$i] !== "_")
					{
						++$errors;
						strcat($err_str, "Ident specified invalid - may only contain numbers, letters, hyphens and underscores");
						$err_type = RPC_ERR_INVALID_PARAMS;
						continue;
					}
				}
				
				$ident = $value;
			}
			elseif ($key == "mask")
			{
				if (strlen($value) > 255)
				{
					++$errors;
					strcat($err_str, "Mask specified too long - limit is 255 chars");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				
				$mask = $value;
			}
			elseif ($key == "set by")
			{
				if (strlen($value) > 255)
				{
					++$errors;
					strcat($err_str, "Mask specified too long - limit is 255 chars");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				
				$set_by = $value;
			}
			elseif ($key == "expiry")
			{
				if (!ctype_digit($value))
				{
					++$errors;
					strcat($err_str, "Expiry specified is not a timestamp");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				
				$expiry = $value;
			}
			elseif ($key == "timestamp")
			{
				if (!ctype_digit($value))
				{
					++$errors;
					strcat($err_str, "Timestamp specified is not a timestamp");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				
				$timestamp = $value;
			}
			elseif ($key == "reason")
			{
				if (strlen($value) > 255)
				{
					++$errors;
					strcat($err_str, "Reason specified too long - limit is 255 chars");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				
				$reason = $value;
			}
			elseif (BadPtr($key))
			{
				continue;
			}
		}
		$reply = rpc_new_reply();
		rpc_append_id($reply, $id);
		if ($errors)
		{
			rpc_append_error($reply, $err_str, $err_type);
		}
		else
		{
			S2S("TKL - $type $ident $mask $set_by $expiry $timestamp :$reason");
			tkl::add_tkl($type,$ident,$mask,$set_by,$expiry,$timestamp,$reason);
			rpc_append_result($reply, "Success");
		}
		rpc_send_reply($id, $reply);
	}
	/**
	 * Expected params:
	 * @param type A type of TKL (s, Q, G, Z)
	 * @param mask The mask or nick which is being removed
	 * @param ident OPTIONAL, only needed when an ident is specified in a G-Line
	 */
	public static function delete($id, $params)
	{
		$errors = 0;
		$err_str = "";
		$err_type = RPC_ERR_INVALID_PARAMS;
		$reply = rpc_new_reply();
		rpc_append_id($reply, $id);
		$types = "sQGZ";
		$type = NULL;
		$mask = NULL;
		foreach($params as $key => $value)
		{
			if ($key == "type")
			{
				if (!strstr($types,$value))
				{
					++$errors;
					strcat($err_str, "Invalid type '$value'");
					continue;
				}
				$type = $value;
			}
			elseif ($key == "mask")
			{
				if (strlen($value) > 255)
				{
					++$errors;
					strcat($err_str, "Mask specified too long - limit is 255 chars");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				$mask = $value;
			}
			elseif ($key == "ident")
			{
				if (strlen($value) > 100)
				{
					++$errors;
					strcat($err_str, "Ident specified too long - limit is 100 chars");
					$err_type = RPC_ERR_INVALID_PARAMS;
					continue;
				}
				$ident = $value;
			}
			else
			{
				continue;
			}
		}
		$ident = $ident ?? "*";
		if ($errors)
		{
			rpc_append_error($reply, $err_str, $err_type);
		}
		else
		{
			$conn = sqlnew();
			$prep = $conn->prepare("SELECT * FROM dalek_tkldb WHERE mask = ? AND type = ?");
			$prep->bind_param("ss", $mask, $type);
			$prep->execute();
			$result = $prep->get_result();
			if (!$result || !$result->num_rows)
			{
				rpc_append_error($reply, "Not found", RPC_ERR_INTERNAL_ERROR);
				rpc_send_reply($id, $reply);
				return;
			}
			$row = $result->fetch_assoc();
			S2S("TKL - ".$row['type']." ".$row['ut']." ".$row['mask']." ".$row['set_by']." ".$row['expiry']." ".$row['timestamp']." ".$row['reason']);
			SVSLog("Deleted TKL matching '$mask' of type '$type'", LOG_RPC);
			tkl::del_tkl($row['type'],$row['ut'],$row['mask']);
			rpc_append_result($reply, "Success");
		}
		rpc_send_reply($id, $reply);
	}
}