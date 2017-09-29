<?php

/*
 * User Management
 * List and manage users
*/

class User {
   
    /* DB */
    private $db;

    /* STRINGS */
    public $id;
    public $name;
    public $email;
    public $addDate;

    /* OBJECTS */
    public $List;

    
    public function __construct() {
        global $db;
        $this->db = $db;
       
    }


    public function get($id) {
        $query = "SELECT * FROM trade_user WHERE id = $id LIMIT 1;";
        
        $sql = $this->db->query($query);
        mysqlerr($this->db, $query);

        if(isset($sql->num_rows) && $sql->num_rows > 0) {
            
            $row = $sql->fetch_object();
            $this->id       = $row->id;
            $this->email    = $row->email;
            $this->name     = $row->name;

            return true;
        }

        return false;
    }

}