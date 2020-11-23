<?php
// Class to get Coins data from CryptoCompare
// Copyright © 2018-2020 Pathfinder Associates, Inc.
// Author Christopher Barlow
// version 2.83
// updated 11/23/2020
// updated 7/2/2018 added RUP

namespace PAI;

// Include the required Class file using Composer
require_once ("vendor/autoload.php");	

abstract class PAI_Coins_Abstract {

	const version = "2.83";
	abstract function getCoins($coins,$flush);

}
	
class PAI_Coins extends PAI_Coins_Abstract {
	private $pdo;

	function getCoins ($coins = "BTC,BCH,ETH,FCT,LTC,NEO,RUP",$flush=false) {
		if (is_null($coins)) {
			$coins = "BTC,BCH,ETH,FCT,LTC,NEO,RUP";
		}
		// create cache file for this zipcode
		$cfile = "wCoins";
		$pcache = new \PAI\PAI_Cache();
		if ($flush) {$pcache->delete($cfile);}
		$f = $pcache->fetch($cfile);
		if (!$f) {
			// Fetch failed so now retrieve from CoinsCompare api
			// Coins conditions
			$url = "https://min-api.cryptocompare.com/data/pricemulti?fsyms="; 
			$url = $url . $coins . "&tsyms=USD";
			$f = file_get_contents($url);
			// then store in cache
			$pcache->store($cfile,$f,60*60*1);	//cache for 1 hours **or adj to midnight?
		}
		// decode the json from cache or api
		$json = json_decode($f,true);
		$data[0] = $pcache->setCache;
		foreach($json as $key => $item) {
					$data[1][] = array($key, $item["USD"]);
			}
		//now update database if not from cache
		if (!$pcache->fromCache) {
			$msg=null;
			if ($this->opendb($msg)) {
				foreach ($data[1] as $item){
					$this->LogQuote($item[0],$item[1]);
				}
				unset($this->pdo);
			}
		}
		// return array of time and coins
		return $data;
		
	}

	private function LogQuote($coin,$price)
	{
		//update table for this run
		$sql = "INSERT INTO coins (coin,price)
				VALUES (:coin, :price)";
		$val = array("coin"=>$coin, "price"=>$price);
		// execute the SQL statement - if returns fail then report
		$stmt = $this->pdo->prepare($sql);
		if(!$stmt->execute($val)){
			echo ("No update");
		}
		return ;
	}

	private function opendb(&$msg) {
		//function to open PDO database and return PDO object

		$db   = 'sqlite:paicoins.sqlite';
		$dsn = $db;
			$opt = [
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES   => false,
		];
		
		try {
			$this->pdo = new \PDO($dsn);
		} catch (\PDOException $e) {
			$msg = 'Connection failed: ' . $e->getMessage();
			return false;
		}
		$this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		return true;
	}
// end of class	
}

?>