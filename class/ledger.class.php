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
    public $volume;
    public $price;
    public $total;
    public $takeProfit;
    public $stopLoss;
    public $status;
    public $addDate;
    public $closeDate;

    /* OTHER STRINGS */
    public $takeProfit_active;
    public $stopLoss_active;

    /* OBJECTS */
    public $List;
    public $API;

    
    public function __construct($exchange = TRADE_EXCHANGE, $pair = TRADE_PAIR) {
        global $db;
        $this->db = $db;

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
        }
    }


    public function add($price, $operator = 'less') {

        $query_ins = "INSERT INTO trade_ledger SET exchange = '$this->exchange',
        pair = '$this->pair',
        orderAction = '$this->orderAction',
        volume = '$this->volume',
        price = '$this->price',
        total = '".round($volume * $price, 3)."',
        reference = '$reference'";
		
		if($this->parentid)
			$query_ins .= ", parentid = $this->parentid";
        
        if($this->takeProfit_active == 1)
            $query_ins .= ", takeProfit = '".round($price * (1 + $takeProfit / 100), 3)."'";

        if($this->stopLoss_active == 1)
            $query_ins .= ", stopLoss = '".round($price / (1 + $stopLoss / 100), 3)."'";

        $query_ins .= ";";

        $sql_ins = $this->db->query($query_ins);
        mysqlerr($this->db, $query_ins);

    }


    public function select($limit = 10) {
        $query = "SELECT * FROM trade_ledger ORDER BY addDate DESC LIMIT $limit;";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $this->List = array();
            while($row = $sql->fetch_object()) {

				$this->List[$row->id] = $row;

				
                /*$this->List[$row->id] = new stdClass();
				
                $this->List[$row->id]->id       	= $row->id;
                $this->List[$row->id]->parentid     = $row->parentid;
				$this->List[$row->id]->exchange    	= $row->exchange;
                $this->List[$row->id]->pair  		= $row->pair;
				$this->List[$row->id]->reference    = $row->reference;
                $this->List[$row->id]->orderAction  = $row->orderAction;
                $this->List[$row->id]->volume  		= $row->volume;
				$this->List[$row->id]->price       	= $row->price;
                $this->List[$row->id]->total    	= $row->total;
                $this->List[$row->id]->takeProfit  	= $row->takeProfit;
				$this->List[$row->id]->stopLoss		= $row->stopLoss;
				$this->List[$row->id]->status		= $row->status;
                $this->List[$row->id]->addDate    	= $row->addDate;
                $this->List[$row->id]->closeDate  	= $row->closeDate;*/

            }
        }
    }
    

    public function get($id) {

    }


}

?>