<?php

class VisitorLog {

	public $database_table = "visitors"; // Set to 'false` to disable..

    public function __construct() {
        if($this->database_table) {
            try {
                ORM::raw_execute("SELECT * FROM " .$this->database_table. " LIMIT 1;");
            } catch (Exception $e) {
                $sql = "CREATE TABLE " .$this->database_table. " (
                    id INT(9) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    ip varchar(64) NOT NULL,
					location varchar(512) NOT NULL,
                    agent varchar(1024) NOT NULL,
                    browser varchar(64) NOT NULL,
                    os varchar(64) NOT NULL,
                    device varchar(64) NOT NULL,
                    ref varchar(512) NOT NULL,
                    country varchar(128) NULL,
                    time int(11) NOT NULL
                )";
                if(ORM::raw_execute($sql)) {
                    echo "Table created.";
                }
            }
        }
    }

    private function get_ip() {
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];
        if (filter_var($client, FILTER_VALIDATE_IP)) {
            $ip = $client;
        }
        elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
            $ip = $forward;
        }
        else {
            $ip = $remote;
        }
        return $ip;
    }
	
	private function get_location() {
		return $_SERVER['REQUEST_URI'];
	}
	
	private function get_user_agent() {
		return $_SERVER['HTTP_USER_AGENT'];
	}
	
	public function start() {
		
		$browser = new VeryIdiot\BrowserDetection;
		$device = new VeryIdiot\MobileDetect();
		
		if($device->isMobile())
			$device_type = 'Mobile';
		elseif($device->isTablet())
			$device_type = ' Tablet';
		else
			$device_type = 'PC';
			
		if($device->isiOS())
			$os = 'iOS';
		elseif($device->isAndroidOS())
			$os = 'Android';
		elseif($device->isLinux())
			$os = 'Linux';
		else
			$os = 'Windows';

	if(isset($_SERVER['HTTP_REFERER']))
		$ref = $_SERVER['HTTP_REFERER'];
	else
		$ref = '';
		
		$data = array(
			"ip" => $this->get_ip(),
			"location" => $this->get_location(),
			"agent" => $_SERVER['HTTP_USER_AGENT'],
			"browser" => $browser->getName(),
			"os" => $os,
			"device" => $device_type,
			"ref" => $ref,
			"time" => time()
		);
		
		$this->log_visitor($data);
	}	
	
	private function log_visitor($data) {
		if($data) {
			$visitor = ORM::for_table($this->database_table)->create();
			$visitor->ip = $data["ip"];
			$visitor->time = $data["time"];
			$visitor->location = $data["location"];
			$visitor->agent = $data["agent"];
			$visitor->browser = $data["browser"];
			$visitor->os = $data["os"];
			$visitor->device = $data["device"];
			$visitor->ref = $data["ref"];
			$visitor->save();
		}
		else return false;
	}
	
	public function pull($latest = null) {
		if(isset($latest)) $visitors = ORM::for_table($this->database_table)->where_null("country")->find_many();
		else $visitors = ORM::for_table($this->database_table)->order_by_desc("id")->find_many();
		return $visitors;
	}
}

$VisitorLog = new VisitorLog;
