<?php

/*
 * Kraken Exhange Class
 * Use external client Payward
*/
require_once('client/KrakenAPIClient.php');

class Exchange {

	
	/* CONFIG STRINGS */
	public $debug;
    public $exchange;
	public $pair;
	public $oflags;
	public $simulatorOnly;

    /* STRINGS */
	public $reference;
	public $status;
	public $description;
	public $price;
	public $volume;
	public $cost;
	public $fee;
	public $trades;
	


    /* OBJECTS */
    public $API;
    public $Ledger;
    public $Error;
    public $Success;
	
	public $Balance;
	public $OpenOrders;
	public $List;
	
	public $ReferenceList;

    
    public function __construct($key = EXCHANGE_API_KEY, $secret = EXCHANGE_API_SECRET, $exchange = TRADE_EXCHANGE, $pair = TRADE_PAIR, $simulatorOnly = TRADE_SIMULATOR_ONLY) {
		global $debug;
		$this->debug = $debug;
		
		$this->exchange = $exchange;
		$this->pair     = $pair;
		
		$this->simulatorOnly = $simulatorOnly;

        // set which platform to use (beta or standard)
        $beta = false; 
        $url = $beta ? 'https://api.beta.kraken.com' : 'https://api.kraken.com';
        $sslverify = $beta ? false : true;
        $version = 0;
        $this->API = new \Payward\KrakenAPI($key, $secret, $url, $version, $sslverify);
    }


    public function AddOrder($ledgerid) {

		if($this->simulatorOnly) {
			$this->reference  = 'SIMULATOR';
            $this->Success = 'Simulator order posted';
            return true;
		}

        $this->Ledger = new Ledger();
        $this->Ledger->get($ledgerid);

        if($this->Ledger->orderAction == 'buy') {
            $this->oflags = 'fciq';
        }
        else {
            $this->oflags = 'fcib';
        }

        $parameters = array(
            'pair'      => TRADE_PAIR,
            'type'      => $this->Ledger->orderAction, 
            'oflags'    => $this->oflags,

            'ordertype' => $this->Ledger->type, 
            'volume'    => $this->Ledger->volume,
            'price'     => $this->Ledger->price,
            'userref' 	=> strtotime($this->Ledger->addDate), //$this->Ledger->id,
		);
		
		
		if($this->debug)
			krumo($parameters);

		$Response = $this->API->QueryPrivate('AddOrder', $parameters);
		if($this->debug)
			krumo($Response);

        if(isset($Response['error']) && is_array($Response['error']) && count($Response['error']) > 0) {
			$this->Error = '';
			foreach($Response['error'] as $message) {
				$this->Error .= $message;
			}
            return false;
        }
        // ELSE (Order OK)
        elseif(isset($Response['result']) && is_array($Response['result']) && count($Response['result']) > 0) {
            $this->reference  = $Response['result']['txid']['0'];
            $this->Success = $Response['result']['descr']['order'];
            return true;
        }
        else {
            krumo($Response);
            die("AddOrder Error");
        }
    }


	public function searchOrder($addDate, $orderAction, $type, $volume = 0, $price = 0) {

		if($this->simulatorOnly) {
            return false;
		}
		
		/*
		SCENARII
		timeout > order created 		> retrieve reference from open/closed
				> order not created		> create again
		*/
		

		$userref = strtotime($addDate);


		// STEP 1 : SEARCH OPEN ORDERS
		$Response = $this->API->QueryPrivate('OpenOrders', array('trades' => true));
		if($this->debug)
			krumo($Response);
		
		if(isset($Response['error']) && is_array($Response['error']) && count($Response['error']) > 0) {
			echo $Response['error'];
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($Response['result']) && is_array($Response['result']) && count($Response['result']) > 0) {
			if(is_array($Response['result']['open']))
				foreach($Response['result']['open'] as $reference => $result) {
					if($result['userref'] == $userref && $result['descr']['type'] == $orderAction && $result['descr']['ordertype'] == $type) {
						$found = 1;

						if($volume)
							if(floatval($result['vol']) != floatval($volume))
								$found = 0;

						if($price && $type == 'limit')
							if(floatval($result['descr']['price']) != floatval($price))
								$found = 0;
						
						if($found == 1) {
							$this->reference = $reference;
							return true;
						}
					}
				}
		}

		// STEP 2 : SEARCH CLOSED ORDERS
		$parameters = array(
			'trades' => true,
			'userref' => $userref,
			'start' => $userref -7200,
			'end' => $userref +7200,
		);


		if($this->debug)
			krumo($parameters);

		$Response = $this->API->QueryPrivate('ClosedOrders', $parameters);
		if($this->debug)
			krumo($Response);
		
		if(isset($Response['error']) && is_array($Response['error']) && count($Response['error']) > 0) {
			echo $Response['error'];
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($Response['result']) && is_array($Response['result']) && count($Response['result']) > 0) {
			if(is_array($Response['result']['closed']))
				foreach($Response['result']['closed'] as $reference => $result) {
					$found = 1;
					if($volume)
						if($result['vol'] != $volume)
							$found = 0;
					if($price)
						if($result['descr']['price'] != $price)
							$found = 0;
					
					if($found == 1) {
						$this->reference = $reference;
						return true;
					}
				}

		}

		// NOT FOUND
		return false;

	}


    public function QueryOrders($ledgerid = 0, $ReferenceList = array()) {

		$this->List = array();
		
		$parameters = array(
            'trades' => true
		);

		if($ledgerid) {
			$this->Ledger = new Ledger();
			$this->Ledger->get($ledgerid);

			$parameters['userref'] = strtotime($this->Ledger->addDate); //$this->Ledger->id;
			$parameters['txid'] = $this->reference= $this->Ledger->reference;
			
		}
		elseif(count($ReferenceList)) {
			$parameters['txid'] = $this->ReferenceList = implode(',', $ReferenceList);
		}
			

		if($this->debug)
			krumo($parameters);
		
		$Response = $this->API->QueryPrivate('QueryOrders', $parameters);
		if($this->debug)
			krumo($Response);
		
		if(isset($Response['error']) && is_array($Response['error']) && count($Response['error']) > 0) {
			echo $Response['error'];
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($Response['result']) && is_array($Response['result']) && count($Response['result']) > 0) {
			foreach($Response['result'] as $reference => $detail) {
				$this->List[$reference] = new stdClass();
				$this->List[$reference]->userref 		= $detail['userref'];
				$this->List[$reference]->status 		= $detail['status']; // open | closed | canceled
				$this->List[$reference]->description	= $detail['descr']['order'];
				$this->List[$reference]->price 			= $detail['price'];
				$this->List[$reference]->volume 		= $detail['vol_exec'];
				$this->List[$reference]->cost 			= $detail['cost'];
				$this->List[$reference]->fee 			= $detail['fee'];
				$this->List[$reference]->trades 		= $detail['trades'];
			}
			return true;
		}
		
		// NOT FOUND
		return false;
	}


	public function openOrders() {
		
		$Response = $this->API->QueryPrivate('OpenOrders', array('trades' => true));
		if($this->debug)
			krumo($Response);
		
		if(isset($Response['error']) && is_array($Response['error']) && count($Response['error']) > 0) {
			$this->Error = '';
			foreach($Response['error'] as $message) {
				$this->Error .= $message;
			}
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($Response['result']) && is_array($Response['result']) && count($Response['result']) > 0) {
				$this->OpenOrders = $Response['result'];
				return true;
		}
		else {
			krumo($Response);
			die("OpenOrders Error");
		}

	}


	public function cancelOrder($ledgerid = 0, $reference = 0) {

		if($ledgerid) {
			$this->Ledger = new Ledger();
			$this->Ledger->get($ledgerid);
			$this->reference = $this->Ledger->reference;
		}
		else
			$this->reference = $reference;
		
		$Response = $this->API->QueryPrivate('CancelOrder', array('txid' => $this->reference));
		if($this->debug)
			krumo($Response);

        if(isset($Response['error']) && is_array($Response['error']) && count($Response['error']) > 0) {
			$this->Error = '';
			foreach($Response['error'] as $message) {
				$this->Error .= $message;
			}
            return false;
        }
		// ELSE (Order OK)
		elseif(isset($Response['result']) && is_array($Response['result']) && count($Response['result']) > 0) {
			$this->Success = "Order $this->reference canceled";
			return true;
		}
		else {
			krumo($Response);
			die("CancelOrder Error");
		}

	}


	public function ticker() {
		
		$Response = $this->API->QueryPublic('Ticker', array('pair' => $this->pair));
		if($this->debug)
			krumo($Response);

		if(isset($Response['error']) && is_array($Response['error']) && count($Response['error']) > 0) {
			$this->Error = '';
			foreach($Response['error'] as $message) {
				$this->Error .= $message;
			}
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($Response['result']) && is_array($Response['result']) && count($Response['result']) > 0) {
			if(isset($Response['result'][$this->pair]['c']['0'])) {
				$this->price = $Response['result'][$this->pair]['c']['0'];
				return true;
            }
			return false;
		}
		else {
			krumo($Response);
			die("Ticker Error");
		}

	}


	public function balance() {
		
		$Response = $this->API->QueryPrivate('Balance');
		if($this->debug)
			krumo($Response);

		if(isset($Response['error']) && is_array($Response['error']) && count($Response['error']) > 0) {
			$this->Error = '';
			foreach($Response['error'] as $message) {
				$this->Error .= $message;
			}
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($Response['result']) && is_array($Response['result']) && count($Response['result']) > 0) {
				$this->Balance = $Response['result'];
				return true;
		}
		else {
			krumo($Response);
			die("Balance Error");
		}

	}




}

?>