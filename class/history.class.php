<?php

/*
 * Echange Price History
 * List all previous prices for each minute
*/

class History {
   
    /* DB */
    private $db;

    /* STRINGS */
    public $id;
    public $exchange;
    public $pair;
    public $price;
    public $addDate;

    /* OBJECTS */
    public $List;

    
    public function __construct($exchange = TRADE_EXCHANGE, $pair = TRADE_PAIR) {
        global $db;
        $this->db = $db;

        $this->exchange = $exchange;
        $this->pair = $pair;
        
    }


    public function add($price) {

        // Store Price
        $query_ins = "INSERT INTO trade_history SET 
        echange = '$this->exchange',
        pair = '$this->pair',
        price = '$price' ;";
     
        $sql_ins = $this->db->query($query_ins);
        mysqlerr($this->db, $query_ins);

        //$query = "SELECT id FROM trade_history WHERE addDate > (NOW() - INTERVAL ($ticker*60) SECOND)";

    }


    public function select($limit = 10) {
        $query = "SELECT * FROM trade_history ORDER BY addDate DESC LIMIT $limit;";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $this->List = array();
            while($row = $sql->fetch_object()) {

                $this->List[$row->id] = new stdClass();

                $this->List[$row->id]->id       = $row->id;
                $this->List[$row->id]->price    = $row->price;
                $this->List[$row->id]->addDate  = $row->addDate;

            }
        }
    }

}

?>