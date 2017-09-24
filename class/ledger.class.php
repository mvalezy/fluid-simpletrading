<?php

/*
 * Ledger Exhange
 * Manage pending and closed orders
*/

class Ledger {
   
    /* CONFIG STRINGS */
    private $db;
    public $exchange;
    public $pair;

    /* DB STRINGS */
    public $id;
    public $parentid;
    public $reference;
    public $orderAction;
    public $type;
    public $volume;
    public $price;
    public $total;
    public $takeProfit;
    public $stopLoss;
    public $description;
    public $volume_executed;
    public $price_executed;
    public $cost;
    public $fee;
    public $trades;
    public $status;
    public $scalp='none';
    public $addDate;
    public $updateDate;
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
        $Alert = new Alert($this->id);
            if($this->takeProfit_active == 1) {
                //$Alert->add($this->takeProfit, 'even'); // Sell alert
                $Alert->add('more', round($this->price * (1 + $this->takeProfit_rate / 2 / 100), 3)); // Reach alert
            }

            if($this->stopLoss_active == 1) {
                //$Alert->add($this->stopLoss, 'even'); // Sell alert
                $Alert->add('less', round($this->price / (1 + $this->stopLoss_rate / 2 / 100), 3)); // Reach alert
            }
        }

    }

    public function updateReference($id) {
        
        $query = "UPDATE trade_ledger SET reference = '".$this->db->real_escape_string($this->reference)."' WHERE id = $id LIMIT 1;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);        
    }


    public function updateByReference($reference) {
        
        $query = "UPDATE trade_ledger SET updateDate = NOW()";
        
        if($this->description)
            $query .= ", description = '".$this->db->real_escape_string($this->description)."'";
        if($this->volume_executed)
            $query .= ", volume_executed = $this->volume_executed";
        if($this->price_executed)
            $query .= ", price_executed = $this->price_executed";
        if($this->cost)
            $query .= ", cost = $this->cost";
        if($this->fee)
            $query .= ", fee = $this->fee";
        if($this->trades)
            $query .= ", trades = '".$this->db->real_escape_string($this->trades)."'";
        if($this->status)
            $query .= ", status = '".$this->db->real_escape_string($this->status)."'";
        if($this->status != 'open') {
            $query .= ", closeDate = NOW()";
        }
        
        $query .= " WHERE reference = '".$this->db->real_escape_string($reference)."' LIMIT 1;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);        

        // SEND Immediate Alert
        if($this->alert && $this->status != 'open') {
            $this->getByReference($reference);
            $Alert  = new Alert($this->id);
            $Alert->add('now', $this->price_executed);
            $Alert->send($Alert->id, $this->price_executed);
        }
    }

    public function cancelByReference($reference) {
        
        $query = "UPDATE trade_ledger SET updateDate = NOW(), status = 'canceled' WHERE reference = '".$this->db->real_escape_string($reference)."' LIMIT 1;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);        
    }

    public function cancel($id) {
        
        $query = "UPDATE trade_ledger SET updateDate = NOW(), status = 'canceled' WHERE id = ".$this->db->real_escape_string($id)." LIMIT 1;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);        
    }    

    public function close($id, $price) {
        
        $query = "UPDATE trade_ledger SET status = 'closed', closeDate = NOW() WHERE id = $id LIMIT 1;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);       
    }

    public function closeScalp($id) {
         
        $query = "UPDATE trade_ledger SET scalp = 'closed' WHERE id = $id LIMIT 1;";

        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);
        
    }


    public function select($limit = 20) {
        $query = "SELECT * FROM trade_ledger ORDER BY addDate DESC LIMIT $limit;";
        
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

    public function selectOpen($limit = 10) {
        $query = "SELECT * FROM trade_ledger WHERE status = 'open' ORDER BY addDate DESC LIMIT $limit;";
        
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

    public function selectClosed($limit = 10) {
        $query = "SELECT * FROM trade_ledger WHERE status != 'open' ORDER BY closeDate DESC LIMIT $limit;";
        
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

    public function selectRefresh($limit = 10) {
        $query = "SELECT * FROM trade_ledger WHERE status = 'open' AND reference IS NOT NULL AND reference != 'SIMULATOR' ORDER BY addDate ASC LIMIT $limit;";
        
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


    public function selectScalp($price) {
        $query = "SELECT * FROM trade_ledger WHERE orderAction = 'buy' AND status = 'closed' AND scalp = 'pending' AND ( 
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

    public function selectEmptyReference($limit = 2) {
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

    public function getByReference($reference) {
        $query = "SELECT * FROM trade_ledger WHERE reference = '".$this->db->real_escape_string($reference)."';";
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);
        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $row      = $sql->fetch_object();
            $this->id = $row->id;
        }

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
            $this->description  = $row->description;
            $this->volume_executed = $row->volume_executed;
            $this->price_executed  = $row->price_executed;
            $this->cost         = $row->cost;
            $this->fee          = $row->fee;
            $this->trades       = $row->trades;
            $this->status		= $row->status;
            $this->addDate    	= $row->addDate;
            $this->updateDate   = $row->updateDate;
            $this->closeDate  	= $row->closeDate;
        }
    }


}

?>