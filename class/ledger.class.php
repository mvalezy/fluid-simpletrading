<?php

/*
 * Ledger Exhange
 * Manage pending and closed orders
*/

class Ledger {
   
    /* DB */
    private $db;

    /* DB STRINGS */
    public $id;
    public $parentid;
    public $exchange;
    public $pair;
    public $reference;
    public $orderAction;
    public $type;
    public $volume;
    public $price;
    public $total;
    public $takeProfit;
    public $stopLoss;
    public $status;
    public $scalp='none';
    public $addDate;
    public $closeDate;

    /* OTHER STRINGS */
    public $takeProfit_active;
    public $stopLoss_active;

    public $takeProfit_rate;
    public $stopLoss_rate;

    public $alert;

    /* OBJECTS */
    public $List;

    
    public function __construct($alert = TRADE_ALERT, $exchange = TRADE_EXCHANGE, $pair = TRADE_PAIR) {
        global $db;
        $this->db = $db;

        $this->alert    = $alert;
        $this->exchange = $exchange;
        $this->pair     = $pair;       
    }


    public function getVars($array) {
        if(is_array($array) && count($array) > 0) {
            foreach($array as $key => $val) {

                if(property_exists('Ledger', $key)) {
                    $this->$key = $this->db->real_escape_string($val);
                }

            }

            $this->total        = $this->price * $this->volume;
            $this->takeProfit   = $this->price * (1 + $this->takeProfit_rate / 100);
            $this->stopLoss     = $this->price / (1 + $this->stopLoss_rate / 100);

            if($this->takeProfit_active || $this->stopLoss_active)
                $this->scalp = 'pending';
            else
                $this->scalp = 'none';
        }
    }

    public function round() {
        $this->volume       = round($this->volume-0.0001, 4, PHP_ROUND_HALF_DOWN);
        $this->price        = round($this->price, 3);
        $this->priceWish    = round($this->priceWish, 3);
        $this->total        = round($this->total, 3);
        $this->takeProfit   = round($this->takeProfit, 3);
        $this->stopLoss     = round($this->stopLoss, 3);
    }


    public function add() {

        $query_ins = "INSERT INTO trade_ledger SET exchange = '$this->exchange',
            pair = '$this->pair',
            orderAction = '$this->orderAction',
            type = '$this->type',
            volume = '$this->volume',
            price = '$this->price',
            total = '$this->total',
            scalp = '$this->scalp'";

        if($this->reference)
            $query_ins .= ", reference = '$this->reference'";
		
		    if($this->parentid)
            $query_ins .= ", parentid = $this->parentid";
            
        if($this->priceWish)
            $query_ins .= ", priceWish = '$this->priceWish'";
        
        if($this->takeProfit_active == 1)
            $query_ins .= ", takeProfit = '$this->takeProfit'";

        if($this->stopLoss_active == 1)
            $query_ins .= ", stopLoss = '$this->stopLoss'";

        $query_ins .= ";";

        $sql_ins = $this->db->query($query_ins);
        mysqlerr($this->db, $query_ins);

        $this->id = $this->db->insert_id;


        // Schedule new Alerts
        if($this->alert) {
        $Alert = new Alert($Ledger->id);
            if($this->takeProfit_active == 1) {
                //$Alert->add($Ledger->takeProfit, 'even'); // Sell alert
                $Alert->add('more', round($this->price * (1 + $this->takeProfit_rate / 2 / 100), 3)); // Reach alert
            }

            if($this->stopLoss_active == 1) {
                //$Alert->add($Ledger->stopLoss, 'even'); // Sell alert
                $Alert->add('less', round($this->price / (1 + $this->stopLoss_rate / 2 / 100), 3)); // Reach alert
            }
        }

    }

    public function update($id) {
        
        $query = "UPDATE trade_ledger SET reference = '$this->reference' WHERE id = $id;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);        
    }

    public function close($id, $price) {
        
        $query = "UPDATE trade_ledger SET status = 'closed' WHERE id = $id;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        // SEND Immediate Alert
        if($this->alert) {
            //$this->get($id);
            $Alert  = new Alert($Ledger->id);
            $Alert->add('now', $price);
            $Alert->send($Alert->id, $price);
        }
        
    }

    public function closeScalp($id) {
         
        $query = "UPDATE trade_ledger SET scalp = 'closed' WHERE id = $id;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);
        
    }


    public function select($limit = 10) {
        $query = "SELECT * FROM trade_ledger ORDER BY addDate DESC LIMIT $limit;";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $this->List = array();
            while($row = $sql->fetch_object()) {
				$this->List[$row->id] = $row;
            }
        }
    }

    public function selectScalp($price) {
        $query = "SELECT * FROM trade_ledger WHERE scalp = 'pending' AND ( 
            (orderAction = 'buy' AND takeProfit IS NOT NULL AND takeProfit < $price) OR
            (orderAction = 'buy' AND stopLoss   IS NOT NULL AND stopLoss   > $price)
            );";        
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $this->List = array();
            while($row = $sql->fetch_object()) {
				$this->List[$row->id] = $row;
            }
        }
    }

    public function selectEmptyReference($limit = 5) {
        $query = "SELECT id, volume, price, addDate FROM trade_ledger WHERE reference IS NULL ORDER BY addDate DESC LIMIT $limit;";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $this->List = array();
            while($row = $sql->fetch_object()) {
                $this->List[$row->id] = $row;
            }
            return true;
        }
        else
            return false;
    }    


    public function get($id) {
        $query = "SELECT * FROM trade_ledger WHERE id = '$id';";
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $row                = $sql->fetch_object();

            $this->id       	= $row->id;
            $this->parentid     = $row->parentid;
            $this->exchange    	= $row->exchange;
            $this->pair  		= $row->pair;
            $this->reference    = $row->reference;
            $this->orderAction  = $row->orderAction;
            $this->type         = $row->type;
            $this->volume  		= $row->volume;
            $this->price       	= $row->price;
            $this->total    	= $row->total;
            $this->takeProfit  	= $row->takeProfit;
            $this->stopLoss		= $row->stopLoss;
            $this->status		= $row->status;
            $this->addDate    	= $row->addDate;
            $this->closeDate  	= $row->closeDate;
        }
    }


}

?>