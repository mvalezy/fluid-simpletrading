<?php

/*
 * Alert Manager for Trading
 * Platform : notify my android
*/

class Alert {
   
    /* DB */
    private $db;

    /* STRINGS */
    public $id;
    public $exchange;
    public $pair;
    public $ledgerid;
    public $operator;
    public $price;
    public $status;
    public $priority;
    public $addDate;
    public $closeDate;

    /* OBJECTS */
    public $List;
    public $API;

    
    public function __construct($exchange = TRADE_EXCHANGE, $pair = TRADE_PAIR) {
        global $db;
        $this->db = $db;

        $this->priority = 0;

        $this->exchange = $exchange;
        $this->pair = $pair;
        
    }


    public function add($price, $operator = 'less') {

        switch($operator) {
            case '>':
                $operator = 'more';
                break;
            case '<':
                $operator = 'less';
                break; 
            case '=':
                $operator = 'even';
                break; 
        }

        $query_ins = "INSERT INTO trade_alert SET
        exchange = '$this->exchange',
        pair = '$this->pair',
        operator = '$operator',
        price = '$price'";

        if(isset($this->ledgerid) && $this->ledgerid > 0) {
            $query_ins .= ", ledgerid = $ledgerid";
        }
        
        $sql_ins = $this->db->query($query_ins);
        mysqlerr($this->db, $query_ins);
    }


    public function select($price = 0, $status = 'new') {
        $query = "SELECT * FROM trade_alert WHERE status = '$status'";
        if($price)
            $query .= " AND (
                (operator = 'more' AND price < $price) OR
                (operator = 'less' AND price > $price) OR
                (operator = 'even' AND price = $price) 
            )";
        $query .= ";";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $this->List = array();
            while($row = $sql->fetch_object()) {

                $this->List[$row->id] = new stdClass();

                $this->List[$row->id]->id       = $row->id;
                $this->List[$row->id]->price    = $row->price;
                $this->List[$row->id]->operator = $row->operator;
                $this->List[$row->id]->pair     = $row->pair;
                $this->List[$row->id]->ledgerid = $row->ledgerid;

            }
        }
    }
    

    public function get($id) {
            $query = "SELECT * FROM trade_alert WHERE id = '$id';";
            $sql = $this->db->query($query);
            mysqlerr($this->db, $query);

            if(isset($sql->num_rows) && $sql->num_rows > 0) {
                $row            = $sql->fetch_object();
                $this->id       = $row->id;
                $this->price    = $row->price;
                $this->operator = $row->operator;
                $this->pair     = $row->pair;
                $this->ledgerid = $row->ledgerid;
            }
    }

  
    public function send($id, $price = 0) {
      
        if(isset($this->List[$id])) {
            $this->id           = $id;
            $this->price        = $this->List[$id]->price;
            $this->operator     = $this->List[$id]->operator;
            $this->pair         = $this->List[$id]->pair;
            $this->ledgerid     = $this->List[$id]->ledgerid;
        }
        else
            $this->get($id);


        $this->API              = new NotifyMyAndroid();
        $this->API->url         = "http://demo.fluid-element.com/trade";
        $this->API->priority    = $this->priority;

        switch($this->operator) {

            case 'more':
                $this->API->event = $this->API->application = "Target price reached";
                $this->API->description = "[$this->pair] Target price reached $this->price";
                break;

            case 'less':
                $this->API->event = $this->API->application = "Low price reached";
                $this->API->description = "[$this->pair] Low price reached $this->price";
                break;

            case 'even':
                $this->API->event = $this->API->application = "Price reached";
                $this->API->description = "[$this->pair] Price reached $this->price";
                break;

        }

        if(@$this->ledgerid)    $this->API->description .= ". Order $this->ledgerid.";
        if(@$price)             $this->API->description .= ". Current price $price.";



        if($this->API->post()) {

            $query = "UPDATE trade_alert SET status = 'sent', closeDate = NOW(), remaining = ".$this->API->remaining.", resettimer = ".$this->API->resettimer."  WHERE id = $id LIMIT 1;";

            $sql = $this->db->query($query);
            mysqlerr($this->db, $query);
        }
    }

}

?>