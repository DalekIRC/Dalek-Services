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
\\	Title:		JSON RPC-API 
//				
\\	Desc:		Implementing https://www.jsonrpc.org/specification
//
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/


if (file_exists("../../misc.php"))
	require_once("../../misc.php");
if (file_exists("../../hook.php"))
	require_once("../../hook.php");
if (file_exists("../../sql.php"))
	require_once("../../sql.php");


/* JSON-RPC version */
const JSON_RPC_VERSION = "2.0";

/* Database inf0rmation lol */
const RPC_PIPE = "rpc.pipe";
const RPC_RESPONSES = "rpcr.pipe";
const RPC_MAGIC_LEVEL = "\1\1\1";
const RPC_MAGIC_SPLIT = "\2\2\2";

/* spec-defined error codes */
define("RPC_ERR_INVALID_REQUEST", -32600);
define("RPC_ERR_PARSE_ERROR", -32700);
define("RPC_ERR_METHOD_NOT_FOUND", -32601);
define("RPC_ERR_INVALID_PARAMS", -32602);
define("RPC_ERR_INTERNAL_ERROR", -32603);

/* unrealircd defined error codes */
define("RPC_ERR_API_CALL_DENIED", -32000); // Caller does not have permission to make the call
define("RPC_ERR_NOT_FOUND", -1000); // nick or channel not found
define("RPC_ERR_ALREADY_EXISTS", -1001); // Resource already exists by that name (eg on nickchange request, a gline, etc) 
define("RPC_ERR_INVALID_NAME", -1002); // is not a valid name, e.g. channel name, nick name.
define("RPC_ERR_USER_NOT_IN_CHANNEL", -1003); // specified user is not in the specified channel
define("RPC_ERR_TOO_MANY_ENTRIES", -1004); // Too many entries
define("RPC_ERR_DENIED", -1005); // Permission denied for user (unrelated to RPC permissions)

/* Some encoding/decoding funcs to make things quicker */
function rpc_encode($arr)
{
	return json_encode($arr, JSON_PRETTY_PRINT);
}
function rpc_decode($json)
{
	return json_decode($json, true);
}


/* Generate a new rpc json reply */
function rpc_new_reply()
{
	$array = [];
	$array["jsonrpc"] = JSON_RPC_VERSION;
	return json_encode($array);
}

/* Converts an array and adds onto the reply. $ovr will override current values with any new ones when set to true */
function rpc_append_array(&$reply, Array $arr, bool $ovr = false) : void
{
	$convert = rpc_decode($reply);
	foreach($arr as $k => $v)
	{
		if (!strcasecmp($k,"id")) /* Add the ID last because that's how it looks in the examples lmao */
			continue;

		if (!IsRpcItem($k)) /* Not a valid JSON-RPC item, skip it */
			continue;

		if (isset($convert[$k]) && $ovr)
			$convert[$k] = $v;

		elseif (!isset($convert[$k]))
			$convert[$k] = $v;
	}

	$convert['id'] = isset($arr['id']) ? $arr['id'] : NULL;
	$reply = rpc_encode($convert);	
}
function rpc_append_error(&$reply, $error, $errcode = RPC_ERR_INTERNAL_ERROR)
{
	rpc_append_array($reply, ['error' => ['code' => $errcode, 'message' => $error]]);
}
function rpc_append_result(&$reply,$result)
{
	rpc_append_array($reply, ['result' => $result]);
}
function rpc_append_id(&$reply, $id)
{
	$id = (int)$id;
	rpc_append_array($reply, ['id' => $id]);
}
function IsRpcItem($n) : bool
{
	if (strcasecmp($n,"id") && strcasecmp($n,"error") && strcasecmp($n,"result") && strcasecmp($n, "code") && strcasecmp($n, "message") && strcasecmp($n,"data"))
		return false;
	return true;
}
function rpc_append_json(&$reply, $arr, bool $ovr = false) : void
{
	$convert = rpc_decode($reply);
	$jconvert = rpc_decode($arr);
	rpc_append_array($convert, $jconvert);
	$reply = rpc_encode($convert);

}
function rpc_error($errcode = RPC_ERR_INVALID_REQUEST, $id = NULL, $message = NULL)
{

	$k['code'] = $errcode;
	$rpc = rpc_new_reply();
	if ($message)
		$k['message'] = $message;
	elseif ($errcode == RPC_ERR_INVALID_REQUEST)
		$k['message'] = "Invalid request";
	elseif ($errcode == RPC_ERR_PARSE_ERROR)
		$k['message'] = "Parse error";
	elseif ($errcode == RPC_ERR_METHOD_NOT_FOUND)
		$k['message'] = "Method not found";
	elseif ($errcode == RPC_ERR_INVALID_PARAMS)
		$k['message'] = "Invalid parameters";
	elseif ($errcode == RPC_ERR_INTERNAL_ERROR)
		$k['message'] = "Internal error";
	elseif ($errcode = RPC_ERR_API_CALL_DENIED)
		$k['message'] = "Server error";
	$j['error'] = $k;
	$j['id'] = $id;
	if ($id)
	{
		/* Well! Gotta clear up after we shit all over the place */
		$pipe = new RPCpipe();
		if (($pipe->lookup($id)))
			$pipe->delete($id);
	}
	rpc_append_array($rpc, $j);

	die($rpc);
	
}


function handle_remote_procedure_call($data)
{
	$data = rpc_decode($data);

	if (!isset($data['jsonrpc']) || $data['jsonrpc'] !== "2.0")
		rpc_error(RPC_ERR_INVALID_PARAMS, NULL, "Not using JSON-RPC 2.0");
	if (!isset($data['id']))
		rpc_error(RPC_ERR_INVALID_PARAMS, NULL, "No ID Specified");
	$id = $data['id'];
	if (!isset($data['method']) || BadPtr($data['method']))
		rpc_error(RPC_ERR_INVALID_PARAMS, $id, "Method not specified");
	
	$params = (isset($data['params'])) ? $data['params'] : "[]";

	$pipe = new RPCpipe();
	$pipe->query($id, $data['method'], $params);	
}
function rpc_dir()
{
	$sp = split(getcwd(),"/");
	$found = 0; // keeping track of things lol
	for ($i = 0; isset($sp[$i]); $i++)
	{
		if ($found)
			$sp[$i] = NULL;
		else
		{
			if (BadPtr($sp[$i]))
				$sp[$i] = NULL;

			if ($sp[$i] == "RPC")
			{
				/* Okay so we wanna go back up two dirs now lmao */
				$sp[$i] = NULL;
				$sp[$i - 1] = NULL;
				$found++;
			}
		}
	}
	$dir = "/".glue($sp,"/")."/data/";
	$pipename = $dir.RPC_PIPE;
	$piperesponses = $dir.RPC_RESPONSES;

	if (!file_exists($dir))
		mkdir($dir);
	
	if (!file_exists($pipename))
	{
		$pipe = fopen($pipename,'c+');
		fwrite($pipe, "DALEKRPC".RPC_MAGIC_SPLIT.servertime().RPC_MAGIC_LEVEL);
		fclose($pipe);
	}
	if (!file_exists($piperesponses))
	{
		$pipe = fopen($piperesponses,'c+');
		fwrite($pipe, "DALEKRPC RESPONSE".RPC_MAGIC_SPLIT.servertime().RPC_MAGIC_LEVEL);
		fclose($pipe);
	}
	return $dir;
}


/** RPC Shared pipe
 * Example structure of pipe containing 1 request to listen channels matching "#dalek*":
 *
 *   id |	method	|	  params	 |
 *------|--------------|-----------------|
 *  123 |  chan.list   |   [#dalek*]	 |
 * 	456 | etc... | etc... |
*/
class RPCpipe
{
	public $pipe, $responder;
	/** Finds our pipe */
	static function find_pipe()
	{
		return rpc_dir().RPC_PIPE;
	}
	/** Finds our response pipe */
	static function find_responder()
	{
		return rpc_dir().RPC_RESPONSES;
	}
	
	/**  Constructor requires no parameters */
	function __construct()
	{
		$this->pipe = self::find_pipe();
		$this->responder = self::find_responder();
	}

	/** Add information to our pipe
	 * @param String $id of the request
	 * @param String $method Method of the request
	 * @param String $params The parameters associated with the method
	 */
	function add($id, $method, $params)
	{
		while ($this->IsBeingWritten($this->pipe)) // wait for it to become available
			usleep(2000);

		if ($this->lookup($id))
			return false;
		
		$string = file_get_contents($this->pipe);
		$pipe = fopen($this->pipe, 'w');

		$params = (is_array($params)) ? rpc_encode($params) : $params;
		$string .= $id.RPC_MAGIC_SPLIT.$method.RPC_MAGIC_SPLIT.$params.RPC_MAGIC_LEVEL;
		fwrite($pipe, $string);
		fclose($pipe);
		return true;
	}
	/** Looks up an RPC request by ID
	 * @param $id
	 */
	function lookup($id)
	{
		while ($this->IsBeingWritten($this->pipe)) // wait for it to become available
			usleep(2000);
		$contents = file_get_contents($this->pipe);
		$lines = split($contents, RPC_MAGIC_LEVEL);
		foreach($lines as $line)
		{
			$item = split($line, RPC_MAGIC_SPLIT);
			if ($item[0] == $id)
				return ['id' => $item[0], 'method' => $item[1], 'params' => $item[2]];
		}
		return false;
	}
	/** Look up a reply by ID
	 * @param String id ID of the RPC command we are replying to
	 */
	function get_reply($id)
	{
		$contents = file_get_contents($this->responder);
		$lines = split($contents, RPC_MAGIC_LEVEL);
		foreach($lines as $line)
		{
			$item = split($line, RPC_MAGIC_SPLIT);
			if ($item[0] == $id)
				return ['id' => $item[0], 'message' => $item[1], 'error' => $item[2]];
		}
		return false;

	}
	function delete($id)
	{
		while ($this->IsBeingWritten($this->pipe)) // wait for it to become available
			usleep(2000);
		$contents = file_get_contents($this->pipe);
		$pipe = fopen($this->pipe, 'w'); // open the file for now to make sure things can't change while we change things
		$lines = split($contents, RPC_MAGIC_LEVEL);
		foreach($lines as $line)
		{
			$item = split($line, RPC_MAGIC_SPLIT);
			if ($item[0] == $id)
			{
				$to_remove = $item[0].RPC_MAGIC_SPLIT.$item[1].RPC_MAGIC_SPLIT.$item[2].RPC_MAGIC_LEVEL;
				$contents = str_replace($to_remove,"",$contents);
			}
		}
		fwrite($pipe, $contents);
		fclose($pipe);
	}
	function delete_reply($id)
	{
		while ($this->IsBeingWritten($this->responder)) // wait for it to become available
			usleep(2000);
		$contents = file_get_contents($this->responder);
		$pipe = fopen($this->responder, 'w'); // open the file for now to make sure things can't change while we change things
		flock($pipe, LOCK_EX);
		$lines = split($contents, RPC_MAGIC_LEVEL);
		foreach($lines as $line)
		{
			$item = split($line, RPC_MAGIC_SPLIT);
			if ($item[0] == $id)
			{
				$to_remove = $item[0].RPC_MAGIC_SPLIT.$item[1].RPC_MAGIC_SPLIT.$item[2].RPC_MAGIC_LEVEL;
				$contents = str_replace($to_remove,"",$contents);
			}
		}
		fwrite($pipe, $contents);
		fclose($pipe);
	}
	function reply($id, $result = NULL, $error = NULL)
	{
		while ($this->IsBeingWritten($this->responder)) // wait for it to become available
			usleep(2000);
		if (!$result && !$error)
			return false;
		if (!$this->lookup($id))
			return false;
		$string = file_get_contents($this->responder);
		$pipe = fopen($this->responder, 'w');
		$string .= $id.RPC_MAGIC_SPLIT.$result.RPC_MAGIC_SPLIT.$error.RPC_MAGIC_LEVEL;
		fwrite($pipe, $string);
		fclose($pipe);
		$this->delete($id); // clear up after the self
	}
	function query(int $id, $method, $params)
	{
		if (!IsRPCCall()) // this is for RPC calls yo
			return; // gtfo

		if (!$this->add($id, $method, $params))
		{
			rpc_error(RPC_ERR_INTERNAL_ERROR, $id, "ID currently in use");
		}
		$timeout = servertime() + 10; // 10 seconds to try and make the query =]
		
		do
		{
			if ($timeout == servertime())
				rpc_error(RPC_ERR_INTERNAL_ERROR, $id, "The request timed out");

			usleep(20000); // let's not smash those resources

		} while (!($reply = $this->get_reply($id)));
		
		$r = rpc_new_reply();

		if (!BadPtr($reply['error']))
		{
			$error = ['error' => rpc_decode($reply['error'])];
			rpc_append_array($r, $error);
			rpc_append_array($r, ['id' => $id]);
			$return = $reply['error'];
		
		}
		else $return = $reply['message'];
		$this->delete_reply($id);
		die($return);
	}
	function IsBeingWritten($src)
	{
		if (!filesize($src))
			return true;
		return false;
	}

}

function rpc_check()
{
	$pipe = new RPCpipe;

	if ($pipe->IsBeingWritten($pipe->pipe)) // let's deal with other stuff instead of waiting, we can always come back =]
		return;
	
	if (empty_pipe($pipe->pipe)) // nothing do deal with =]
		return;

	$contents = file_get_contents($pipe->pipe);
	if (!$contents)
	{
		unset($contents);
		return;
	}
	$contents = split($contents, RPC_MAGIC_LEVEL);
	if (!count($contents))
		return;
	for ($i = 1; isset($contents[$i]); $i++)
	{
		$c = $contents[$i];
		$parv = split($c, RPC_MAGIC_SPLIT);
		if (BadPtr($c) || !is_numeric($parv[0]) || count($parv) < 3)
			continue;
		RPC::run($parv[1], $parv[0], $parv[2]);
	}
	unset($contents);
}

function empty_pipe($src)
{
	$contents = file_get_contents($src);
	if ($contents == false || !$contents)
		return false;
	$contents = split($contents, RPC_MAGIC_LEVEL);
	$c = count($contents);

	if ($c == 1)
		return true;

	if ($c == 0 || $c == false)
		return false;
}


class RPC {

	public static $actions = [];

	public static function run($rpc_hook, $id = NULL, $params = NULL)
	{
		$pipe = new RPCpipe();

		if (isset(self::$actions[$rpc_hook]))
			self::$actions[$rpc_hook]['func']($id, rpc_decode($params));

		else 
		{
			$error = rpc_encode(['code' => RPC_ERR_METHOD_NOT_FOUND, 'message' => "Method not found: \"$rpc_hook\""]);
			$pipe->reply($id, NULL, $error);
		}
	}

	public static function func($rpc_hook, $function, $module = NULL)
	{
		SVSLog("RPC handler loaded: $rpc_hook (Module: ".($module ?? "No module").")");
		self::$actions[$rpc_hook]['func'] = $function;
		self::$actions[$rpc_hook]['modinfo'] = $module;
	}
	public static function del($rpc_hook)
	{
		SVSLog("RPC handler unloaded: $rpc_hook (Module: ".(self::$actions[$rpc_hook]['modinfo'] ?? "No module").")");
		array_splice(self::$actions,$rpc_hook);
	}

	public static function unload_by_module($module = NULL)
	{
		if (!$module)
			return;
		foreach(self::$actions as $i => $action)
		{
			if (!strcasecmp($action['modinfo'],$module))
				self::del($i);
		}
	}
	public static function IsHandler($handler)
	{
		if (isset(self::$actions[$handler]))
			return true;
		return false;
	}
}


function RPCHandlerAdd($modinfo, $handler, $function, &$error)
{
	if (RPC::IsHandler($handler))
	{
		$error = "$handler is already an RPC handler";
		return false;
	}

	if (!strpos($function,"::"))
	{
		$error = "Your RPC handler must reference a static class method pt.1";
		return false;
	}

	$tok = split($function,"::");
	if (!method_exists($tok[0],$tok[1]))
	{
		$error = "Your RPC handler must reference a static class method pt.2";
		return false;
	}

	RPC::func($handler, $function, $modinfo);

	if (!RPC::IsHandler($handler))
	{
		$error = "Something went wrong adding the RPC handler, beats the hell out of me, sorry";
		SVSLog($error);
		SVSLog("Here's some extra info anyway lol. Handler: $handler / Function (should be class method): $function / Module: $modinfo");
		DebugLog(generate_backtrace(debug_backtrace()));
		return false;
	}
	return true;
	
}

function rpc_send_reply($id, $reply)
{
	$pipe = new RPCpipe();
	$pipe->reply($id, $reply);
}


// make easy RPC requests
// You must specify the url in your extension. example underneath
class RPCQuery
{
	private $url = NULL;
	private $curl = NULL;
	private $response = NULL;
	function __construct()
	{
		if (!$this->url)
			die("Cannot construct RPC query extension without a URL");
		$this->curl = curl_init($this->url);
		curl_setopt($this->curl, CURLOPT_URL, $this->url);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false);
		
		$headers = array(
			"Accept: application/json",
			"Content-Type: application/json",
		);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		
		
	}
	/* $data = json encoded string */
	function query($data)
	{
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
		$this->response = curl_exec($this->curl) ?? NULL;
		curl_close($this->curl);
		if ($this->response)
			return $this->response;
		return false;
	}

	function build_query($method, $params)
	{
		$query = rpc_new_reply();
		/* to-do: make appends for method and params */

	}
}

class UnrealRPC extends RPCQuery
{
	private $url = "~/unrealircd/data/rpc.sock";
}

class DalekRPC extends RPCQuery
{
	private $url = "http://localhost:1024/api";
}