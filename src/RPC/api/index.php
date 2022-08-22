<?php


require_once("../../rpc.php");

$json = file_get_contents('php://input');
$data = rpc_decode($json);
if (!$data)
    die(rpc_error());

handle_remote_procedure_call($json);