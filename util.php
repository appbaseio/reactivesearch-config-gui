<?php
	// backup logpath
	$logPath = "td-agent-bit-backup.conf";
	// logPath for local
	// $tdBitConf = "td-agent-bit.conf";

	// logPath for ami
	// $tdBitConf = "/etc/td-agent-bit/td-agent-bit.conf";

	// logPath for docker
	$tdBitConf = "/reactivesearch-data/td-agent-bit.conf";

	// env file path for local
	// $filePath = "env.sample";
	
	// env file path for AMI
	// $filePath = "/etc/systemd/system/arc.env";
	
	// env file path for docker images
	$filePath = "/reactivesearch-data/.env";

	function getEnvVars() {
		global $filePath;
		
		$file = fopen($filePath, "r");
		$fileLines = array();
		while(!feof($file))  {
			$result = fgets($file);
			if (!empty($result)) {
				$splitResult = explode("=", $result);
				$fileLines[$splitResult[0]] = trim($splitResult[1]);
			}
		}
		fclose($file);
		return $fileLines;
	}

	function upsertEnvVars($data) {
		$finalData = getEnvVars();
		foreach ($data as $key => $val) {
			$finalData[$key] = $val;
		}

		$fileContent = "";
		foreach ($finalData as $key => $value) {
			$trimValue = trim($value);
			if (!empty($trimValue)) {
				$fileContent = $fileContent.$key."=".$value."\n";
			}
		}
		global $filePath;
		$file = fopen($filePath, "w") or die("Unable to open file!");
		fwrite($file, $fileContent);
	}

	function splitURL($url) {
		$username = "";
		$password = "";
		$tls = "On";
		$host = "";
		$port = "443";
		$url = rtrim($url, "/");
		$hostSplit = explode("://", $url);
		if (count($hostSplit) === 2) {
			$host = $hostSplit[1];
			
			if ($hostSplit[0] == "http") {
				$tls = "Off";
				$port = "80";
			}
			
		} else {
			$host = $hostSplit[0];
			$tls = "Off";
			$port = "80";
		}
		
		$authSplit = explode( "@", $host);
		if (count($authSplit) === 2) {
			$credentialSplit = explode(":", $authSplit[0]);
			$username = $credentialSplit[0];
			$password = $credentialSplit[1];
			$host = $authSplit[1];
		}
		
		$portSplit = explode(":", $host);
		if (count($portSplit) === 2) {
			$port = $portSplit[1];
			$host = $portSplit[0];
		}
		
		return [
			$host,
			$port,
			$tls,
			$username,
			$password
		];
	}

	function upsertLogFile($data) {
		global $logPath;
		global $tdBitConf;
		$url = $data["ES_CLUSTER_URL"];
		if (isset($url) && $url != "") {
			$contents = file_get_contents($logPath);
			$splitRes = splitURL($url);
			$host = $splitRes[0];
			$port = $splitRes[1];
			$tls = $splitRes[2];
			$username = $splitRes[3];
			$password = $splitRes[4];
			$contents = str_replace('__ES_URL__', $host, $contents);
			$contents = str_replace('__ES_PORT__', $port, $contents);
			$contents = str_replace('__ES_TLS__', $tls, $contents);

			if (trim($username) !== "") {
				$contents = str_replace('__ES_USERNAME__', $username, $contents);
			} else {
				$contents = str_replace('HTTP_User   __ES_USERNAME__', '', $contents);
			}

			if (trim($password) !== "") {
				$contents = str_replace('__ES_PASSWORD__', $password, $contents);
			} else {
				$contents = str_replace('HTTP_Passwd __ES_PASSWORD__', '', $contents);
			}
			
			$res = file_put_contents($tdBitConf, trim($contents));
		}
	}

	function checkAuth() {
		$currentTime = time();
		if (!isset($_SESSION["password"]) || !isset($_SESSION["username"]) || !isset($_SESSION["EXPIRES"]) || (time() - $_SESSION['EXPIRES'] > 900)) {
			logout("?error=Please login");
			header("Location: index.php?error=Please login");
		}
	}

	function logout($data="") {
		unset($_SESSION["username"]);
        unset($_SESSION["password"]);
        unset($_SESSION["EXPIRES"]);
        session_destroy();
        session_unset();
        header("Location: index.php".$data);
	}
?>
