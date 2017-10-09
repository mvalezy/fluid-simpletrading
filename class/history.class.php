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

        $this->id = $this->db->insert_id;
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

    public function selectChart($unit = '1m') {

        $query = "SELECT id, AVG(price) AS price, addDate FROM trade_history ";

        switch($unit) {
            case '1m':
                $div = 1;
                break;
            case '5m':
                $div = 300;
                break;
            case '15m':
                $div = 900;
                break;
            case '30m':
                $div = 1800;
                break;
            case '1h':
                $div = 3600;
                break;
            case '4h':
                $div = 14400;
                break;
            case '12h':
                $div = 43200;
                break;
            case '1d':
                $div = 86400;
                break;
        }

        $query .= "GROUP BY UNIX_TIMESTAMP(addDate) DIV $div ORDER BY addDate DESC LIMIT 30;";
        
        
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


    public function getLast() {
        $query = "SELECT price FROM trade_history ORDER BY addDate DESC LIMIT 1;";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            
            $row = $sql->fetch_object();
            $this->price = $row->price;
            return $this->price;
        }

        return false;
    }

    public function getTick($date) {

        $query = "SELECT price, addDate FROM trade_history WHERE addDate > '".$this->db->real_escape_string($date)."' ORDER BY addDate ASC LIMIT 1;";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            
            $row = $sql->fetch_object();
            $this->price    = $row->price;
            $this->addDate  = $row->addDate;
            return $this->price;
        }
        
        return false;
    }    
}

?>