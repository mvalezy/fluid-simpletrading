<?php

/*
 * Alert Manager for Trading
 * Platform : notify my android
*/

class Alert {
   
    /* DB */
    private $db;

    /* DB STRINGS */
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

    private $snooze;

    /* OBJECTS */
    public $List;
    public $API;

    
    public function __construct($ledgerid = 0, $exchange = TRADE_EXCHANGE, $pair = TRADE_PAIR, $snooze = TRADE_ALERT_SNOOZE) {
        global $db;
        $this->db = $db;

        $this->priority = 0;
        $this->snooze = $snooze;

        $this->exchange = $exchange;
        $this->pair = $pair;

        $this->ledgerid = $ledgerid;
        
    }

    public function send($id, $price = 0, $comment = '') {
        
        if(isset($this->List[$id])) {
            $this->id           = $id;
            $this->price        = $this->List[$id]->price;
            $this->operator     = $this->List[$id]->operator;
            $this->pair         = $this->List[$id]->pair;
            $this->ledgerid     = $this->List[$id]->ledgerid;
        }
        else
            $this->get($id);

        $this->price = round($this->price, 1);

        $this->API              = new Notify();
        $this->API->url         = TRADE_WEBSITE_URL;
        $this->API->priority    = $this->priority;      

        switch($this->operator) {

            case 'more':
                $this->API->event = $this->API->application = "Target price reached";
                $this->API->description = "[$this->pair] Target price reached ".money_format('%i', $this->price);
                break;

            case 'less':
                $this->API->event = $this->API->application = "Low price reached";
                $this->API->description = "[$this->pair] Low price reached ".money_format('%i', $this->price);
                break;

            case 'even':
                $this->API->event = $this->API->application = "Price reached";
                $this->API->description = "[$this->pair] Price reached ".money_format('%i', $this->price);
                break;

            case 'now':
                $this->API->event = $this->API->application = "Order closed";
                $this->API->description = "[$this->pair] Price ".money_format('%i', $this->price);
                break;

            case 'drop':
                $threshold = round((TRADE_ALERT_THRESHOLD-1)*100);
                $this->API->event = $this->API->application = "Price change thershold $threshold% vs last $comment";
                $this->API->description = "[$this->pair] Price ".money_format('%i', $this->price);
                break;
        }

        if(@$this->ledgerid) {
            $obj = new Ledger();
            $obj->get($this->ledgerid);

            $this->API->description .= ". Order($obj->id) $obj->orderAction $obj->type price:".money_format('%i', $obj->price)." vol:".round($obj->volume,2)." tot:".money_format('%i', $obj->total)." ref:$obj->exchange.";
        }

        if($price) {
            $price = round($price, 1);
            $this->API->description .= ". Current price ".money_format('%i', $price);
        }
  
  
  
        if($this->API->post()) {
  
            // UPDATE current Alert
            $query = "UPDATE trade_alert SET status = 'sent', closeDate = NOW(), remaining = ".$this->API->remaining.", resettimer = ".$this->API->resettimer."  WHERE id = $id LIMIT 1;";

            $sql = $this->db->query($query);
            mysqlerr($this->db, $query);

            // Archive other Ledger Alerts
            if(@$this->ledgerid) {
                $query = "UPDATE trade_alert SET status = 'ignored', closeDate = NOW() WHERE id != $id AND ledgerid = $this->ledgerid;";
                
                $sql = $this->db->query($query);
                mysqlerr($this->db, $query);
            }
        }
        else echo "error API POST";
    }


    public function add($operator = 'less', $price = 0) {

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
            $query_ins .= ", ledgerid = $this->ledgerid";
        }
        
        $sql_ins = $this->db->query($query_ins);
        mysqlerr($this->db, $query_ins);

        $this->id = $this->db->insert_id;
    }


    public function select($price = 0, $status = 'new') {
        $query = "SELECT * FROM trade_alert WHERE status = '$status'";
        if($price)
            $query .= " AND (
                (operator = 'more' AND price < $price) OR
                (operator = 'less' AND price > $price) OR
                (operator = 'even' AND price = $price) OR
                (operator = 'now')
            )";
        $query .= ";";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            $this->List = array();
            while($row = $sql->fetch_object()) {
                $this->List[$row->id] = $row;
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

    public function snooze($operator) {

        $date = time()-$this->snooze;
        
        $query = "SELECT id FROM trade_alert WHERE operator = '".$this->db->real_escape_string($operator)."' AND status = 'sent' AND addDate > '".$this->db->real_escape_string($date)."' ORDER BY addDate ASC LIMIT 1;";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) 
            return false;
        
        return true;
    }  

}

?>