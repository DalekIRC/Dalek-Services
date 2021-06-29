<?php
/*				
//	(C) 2021 DalekIRC Services
\\				
//			pathweb.org
\\				
//	GNU GENERAL PUBLIC LICENSE
\\							v3
//				
\\				
//				
\\	Title:		Protocol
//				
\\	Desc:		Class for the server itself which hold functions
//				which use the IRC protocol.
\\				
//				
\\				
//				
\\	Version:	1
//				
\\	Author:		Valware
//				
*/




class Server {
	
	function __construct($server,$port,$password) {
				
		// INITIALISING CONNECT SEQUENCE lmao
		$this->connect($server,$port,$password);
	
	}
	private function connect($server,$port,$password) {
		
		// Declare de globals;
		global $socket,$nickserv,$chanserv,$botserv,$operserv,$hostserv,$servertime,$svs,$cf;
		
		// Anything we wanna initialise before we connect
		
		/* pre connect shit */
		
		// we are disabling verification for now until built upon more :>
		// create ssl context
		$context = stream_context_create(['ssl' => [
			'verify_peer'  => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
			'ciphers' => 'ECDHE-ECDSA-AES256-GCM-SHA384'
		]]);

		//opening socket YO
		$socket = stream_socket_client($server.':'.$port, $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);
		
		
		
		
		$this->sendraw("PASS $password");
		$this->sendraw("PROTOCTL EAUTH=".$cf['servicesname']." SID=".$cf['sid']);
		$this->sendraw("PROTOCTL NOQUIT NICKv2 SJOIN SJ3 CLK TKLEXT2 NICKIP ESVID MLOCK EXTSWHOIS SJSBY");
		$this->sendraw("SERVER ".$cf['servicesname']." 1 :Dalek IRC Services");
		$this->sendraw("EOS");
		

	}
	function sendraw($string){
		// Declare de globals;
		global $socket;
		
		fputs($socket, ircstrip($string)."\n");
		
	}
	function shout($string){
		global $me;
		echo "[".$me."][-->] ".$string."\n";
	}
	function hear($string){
		global $me;
		echo "[".$me."][<--] ".$string."\n";
	}
}
