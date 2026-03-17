<?php
#[\AllowDynamicProperties]

class DB {
    private $dbHost     = "localhost";
    private $dbUsername = "digitalb2k_adsninja";
    private $dbPassword = getenv('DB_PASS');
    private $dbName     = "digitalb2k_adsninja";
  
    public function __construct(){
        if(!isset($this->db)){
            // Connect to the database
            $conn = new mysqli($this->dbHost, $this->dbUsername, $this->dbPassword, $this->dbName);
            if($conn->connect_error){
                die("Failed to connect with MySQL: " . $conn->connect_error);
            }else{
                $this->db = $conn;
            }
        }
    }
  
    public function is_table_empty($uId) {
        $result = $this->db->query("SELECT id FROM google_oauth WHERE provider = 'google' AND uId='".$uId."'");
        if($result->num_rows >0) {
            return false;
        }
  
        return true;
    }
  
    public function get_access_token($uId) {
        $sql = $this->db->query("SELECT provider_value FROM google_oauth WHERE provider = 'google' AND uId='".$uId."'");
        $result = $sql->fetch_assoc();
        return json_decode($result['provider_value']);
    }
  
    public function get_refersh_token($uId) {
        $result = $this->get_access_token($uId);
        return $result->refresh_token;
    }
  
    public function update_access_token($uId,$token) {
        if($this->is_table_empty($uId)) {
            $this->db->query("INSERT INTO google_oauth(provider, provider_value, uId) VALUES('google', '$token', '$uId')");
        } else {
            $this->db->query("UPDATE google_oauth SET provider_value = '$token' WHERE provider = 'google' AND uId='".$uId."'");
        }
    }
    public function getSheetId($uId,$tblId) {
        $sql = $this->db->query("SELECT googlesheet_id FROM leads_acc WHERE tbl_id = ".$tblId." AND uid='".$uId."'");
        $result = $sql->fetch_assoc();
        return json_decode($result['googlesheet_id']);
        
    }
    public function addSheetId($uId,$sheetId,$tbl_id) {
        $this->db->query("UPDATE leads_acc SET googlesheet_id = '".$sheetId."' WHERE uid='".$uId."' AND tbl_id='".$tbl_id."'");
       // $result = $sql->fetch_assoc();
        
    }
    public function updateRes($phone,$res,$spreadsheetId,$sheetTab) { 
        if($this->is_table_empty($res)) {
            $sheetTab = str_replace("!","",$sheetTab);
            $this->db->query("
                INSERT INTO leads_spreadsheet_response
                (leadgen_id, response, sheetId, tabN, created) 
                VALUES (
                    '".$this->db->real_escape_string($phone)."',
                    '".$this->db->real_escape_string($res)."',
                    '".$this->db->real_escape_string($spreadsheetId)."',
                    '".$this->db->real_escape_string($sheetTab)."',
                    NOW()
                )
            ");
        }
    }
}