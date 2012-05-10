<?php

// Parameter
$arrParameters = array();
$bolIsConsole = false;
// Konsole oder HTTP-Parameter auslesen
if(count($argv) > 1) {
	unset($argv[0]);
	foreach($argv as $arg) {
		$arrArg = explode('=', $arg);
		$arrParameters[$arrArg[0]] = $arrArg[1];
	}
	$bolIsConsole = true;
} else if ($_POST) {
	$arrParameters = $_POST;
} else if ($_GET) {
	$arrParameters = $_GET;
}

if(!empty($arrParameters['username'])
&& !empty($arrParameters['password'])
) {
	if(empty($arrParameters['hostname'])) {
		$arrParameters['hostname'] = '{imap.gmail.com:993/ssl}';
	}

	if(empty($arrParameters['filter'])) {
		$arrParameters['filter'] = 'SINCE "1 JANUARY 2004"';
		$arrParameters['filter'] = 'SINCE "10 MAY 2012"';
	}

	echo "Connect to " . $arrParameters['hostname'] . "\n";
	$inbox = imap_open(
				$arrParameters['hostname'],
				$arrParameters['username'],
				$arrParameters['password']
			 );
	if(empty($inbox)) {
		die('Cannot connect to Gmail: ' . imap_last_error());
	}

	echo "Open Inbox 'Chats'\n";
	imap_reopen($inbox, $arrParameters['hostname'].'[Google Mail]/Chats');

	echo "Filter with '" . $arrParameters['filter'] . "'\n";
	$emails = imap_search($inbox,$arrParameters['filter']);

	$arrChats = array();
	if($emails) {
		foreach($emails as $strEmailNumber) {
		
			$strMessage	= imap_fetchbody($inbox,$strEmailNumber,2);
			$arrHeader 	= imap_fetch_overview($inbox,$strEmailNumber);

			$intMessageTimestamp = strtotime ($arrHeader[0]->date);
			$strMessageTime = 0;
			$strMessageSender = '';
			$strMessageText = '';
		
			// Table entfernen

			$strMessage = preg_replace('/<table.+>(.*)<\/table>/','',$strMessage);
			$strMessage = preg_replace('/<\/div>/',"<\div>\n",$strMessage);

			foreach(explode("\n", $strMessage) as $strLine) {
				$arrMatchTime 		= array();
				$arrMatchName 		= array();
				$arrMatchMessage 	= array();
		
				// Zeit auslesen		
				if(preg_match('/<span style=.?display:block;float:left;color:#888.?>(\d{1,2}:\d{1,2})&nbsp;<\/span>/', $strLine, $arrMatchTime)) {
					$strMessageTime = trim($arrMatchTime[1]);
				}

				if(preg_match('/<span style=.?display:block;padding-left:6em;text-indent:-1em.?><span><span style=.?font-weight:bold.? dir=.?ltr.?>(.+)<\/span>:/i', $strLine, $arrMatchName)) {
					$strMessageName = trim($arrMatchName[1]);
				}

	#echo $strLine . "\n";
				if(preg_match('/<\/span>:(.+)<\/span>/i', $strLine, $arrMatchMessage)) {
					$strMessageText = trim(strip_tags($arrMatchMessage[1]));
				} else if (preg_match('/<span style=display:block;padding-left:6em><SPAN>(.+)<\/span><\/span>/i', $strLine, $arrMatchMessage)) {
					$strMessageText = trim(strip_tags($arrMatchMessage[1]));
				}
				
				$arrData = array(
					'date'		=> $intMessageTimestamp,
					'time' 		=> $strMessageTime,
					'sender' 	=> $strMessageName,
					'message' 	=> htmlspecialchars_decode($strMessageText),
				);
	
				$arrChats[$intMessageTimestamp][] = $arrData;
			}
		}
	} 
	ksort($arrChats);

	if(!empty($arrParameters['output']))
	{
		echo "\n";
		// Ausgeben
		foreach($arrChats as $arrChat) {
			foreach($arrChat as $arrMessage) {
				if($arrParameters['output'] == 'csv') {
					$strFulltimestamp = strtotime(date("Y-m-d",$arrMessage['date']) . ' ' . $arrMessage['time']);
					echo '"' . $strFulltimestamp . '";';
					echo '"' . $arrMessage['sender'] . '";';
					echo '"' . $arrMessage['message'] . '";';
					echo "\n";
				} else {
					echo date("Y-m-d",$arrMessage['date']) . ' ';
					echo $arrMessage['time'] . ' | ';
					echo $arrMessage['sender'] . ' | ';
					echo $arrMessage['message'] . "\n";
        			echo "\n";
				}
			}

		}
	}

	/* close the connection */
	imap_close($inbox);

} else {
	print "\nusage:\n\n";
	print "username  = (required) Your Gmail username without @gmail.com or @googlemail.com\n";
	print "password  = (required) Your Gmail-Password\n";
	print "hostname  = (optional) IMAP-Host\n";
	print "filter    = (optional) Message-Filter-String.\n";
	print "output    = (optional) 1 = print out\n\n";
}



?>
