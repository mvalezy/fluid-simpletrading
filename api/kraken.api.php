<?php

/*
 * Kraken Exhange Class
 * Use external client Payward
*/
require_once('client/KrakenAPIClient.php');

class Exchange {
 	
    /* STRINGS */
	public $reference;
	
	private $key;
    private $secret;
    private $beta;
    private $url;
    private $version;
	public  $oflags;
	private $sslverify;

    public $exchange;
	public $pair;
	
	public $price;

    /* OBJECTS */
    public $API;
    public $Ledger;
    public $Error;
    public $Success;
	public $Response;
	
	public $Balance;
	public $OpenOrders;

    
    public function __construct($key = EXCHANGE_API_KEY, $secret = EXCHANGE_API_SECRET, $exchange = TRADE_EXCHANGE, $pair = TRADE_PAIR) {
        $this->exchange = $exchange;
        $this->pair     = $pair;

        // API Credentials
        $this->key      = $key;
        $this->secret   = $secret;

        // set which platform to use (beta or standard)
        $this->beta = false; 
        $this->url = $this->beta ? 'https://api.beta.kraken.com' : 'https://api.kraken.com';
        $this->sslverify = $this->beta ? false : true;
        $this->version = 0;
        $this->API = new \Payward\KrakenAPI($this->key, $this->secret, $this->url, $this->version, $this->sslverify);
    }


    public function AddOrder($ledgerid) {
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
		
		global $debug;
		if($debug)
			krumo($parameters);

		$this->Response = $this->API->QueryPrivate('AddOrder', $parameters);

        if(isset($this->Response['error']) && is_array($this->Response['error']) && count($this->Response['error']) > 0) {
			$this->Error = '';
			foreach($this->Response['error'] as $message) {
				$this->Error .= $message;
			}
            return false;
        }
        // ELSE (Order OK)
        elseif(isset($this->Response['result']) && is_array($this->Response['result']) && count($this->Response['result']) > 0) {
            $this->reference  = $this->Response['result']['txid']['0'];
            $this->Success = $this->Response['result']['descr']['order'];
            return true;
        }
        else {
            krumo($this->Response);
            die("AddOrder Error");
        }
    }


    public function SearchOrder($ledgerid) {
		/*
		SCENARII
		timeout > order created 		> retrieve reference
				> order not created		> create again
		*/

	}


	public function OpenOrders() {
		
		$this->Response = $this->API->QueryPrivate('OpenOrders', array('trades' => true));
		
		if(isset($this->Response['error']) && is_array($this->Response['error']) && count($this->Response['error']) > 0) {
			$this->Error = '';
			foreach($this->Response['error'] as $message) {
				$this->Error .= $message;
			}
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($this->Response['result']) && is_array($this->Response['result']) && count($this->Response['result']) > 0) {
				$this->OpenOrders = $this->Response['result'];
				return true;
		}
		else {
			krumo($this->Response);
			die("OpenOrders Error");
		}

	}


	public function CancelOrder($ledgerid = 0, $reference = 0) {

		if($ledgerid) {
			$this->Ledger = new Ledger();
			$this->Ledger->get($ledgerid);
			$this->reference = $this->Ledger->reference;
		}
		else
			$this->reference = $reference;
		
		$this->Response = $this->API->QueryPrivate('CancelOrder', array('txid' => $this->reference));
		
        if(isset($this->Response['error']) && is_array($this->Response['error']) && count($this->Response['error']) > 0) {
			$this->Error = '';
			foreach($this->Response['error'] as $message) {
				$this->Error .= $message;
			}
            return false;
        }
		// ELSE (Order OK)
		elseif(isset($this->Response['result']) && is_array($this->Response['result']) && count($this->Response['result']) > 0) {
			$this->Success = "Order $this->reference canceled";
			return true;
		}
		else {
			krumo($this->Response);
			die("CancelOrder Error");
		}

	}


	public function Ticker() {
		
		$this->Response = $this->API->QueryPublic('Ticker', array('pair' => $this->pair));
		
		if(isset($this->Response['error']) && is_array($this->Response['error']) && count($this->Response['error']) > 0) {
			$this->Error = '';
			foreach($this->Response['error'] as $message) {
				$this->Error .= $message;
			}
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($this->Response['result']) && is_array($this->Response['result']) && count($this->Response['result']) > 0) {
			if(isset($this->Response['result'][$this->pair]['c']['0'])) {
				$this->price = $this->Response['result'][$this->pair]['c']['0'];
				return true;
            }
			return false;
		}
		else {
			krumo($this->Response);
			die("Ticker Error");
		}

	}


	public function Balance() {
		
		$this->Response = $this->API->QueryPrivate('Balance');
		
		if(isset($this->Response['error']) && is_array($this->Response['error']) && count($this->Response['error']) > 0) {
			$this->Error = '';
			foreach($this->Response['error'] as $message) {
				$this->Error .= $message;
			}
			return false;
		}
		// ELSE (Order OK)
		elseif(isset($this->Response['result']) && is_array($this->Response['result']) && count($this->Response['result']) > 0) {
				$this->Balance = $this->Response['result'];
				return true;
		}
		else {
			krumo($this->Response);
			die("Balance Error");
		}

	}




}

?>