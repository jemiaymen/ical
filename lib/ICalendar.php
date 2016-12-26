<?php

require_once "ICalConfig.php";


class Task{

	//mysqli Instance
	private $c;
	//query to execute
	private $qry;
	//result query fetched
	private $result;

	//calender attribute
	private $acl;
	private $user ;
	private $role ;
	private $start ;
	private $end  ;
	private $rec ;
	private $state  ;
	private $rappel  ;
	private $title  ;
	private $des  ;
	private $ref  ;
	private $ical;

	public function __construct(){
		$this->c = new mysqli(HOST, USER, PW, DB);
	}

	public function printAll(){

		echo "<pre>";
		echo $this->acl;
		echo $this->user ;
		echo $this->role ;
		echo $this->start ;
		echo $this->end  ;
		echo $this->rec ;
		echo $this->state  ;
		echo $this->rappel  ;
		echo $this->title  ;
		echo $this->des  ;
		echo $this->ref  ;
		echo "</pre>";

		echo "<pre>";
		var_dump($this->result);
		echo "</pre>";
	}

	private function getUser($token){
		$token = addslashes($token);
		$this->qry = "SELECT id,user,role FROM " . ACL . " WHERE token ='" . $token ."'";
		$re = $this->c->query($this->qry);
		$this->result  = $re->fetch_assoc() ;
		$this->result["acl"] = $this->result["id"];
		$this->explode($this->result);
	}

	public function getIcalByUser($token,$start,$end,$limit = 30){
		$this->getUser($token);
		$limit = addslashes($limit);
		$start = addslashes($start);
		$end = addslashes($end);
		$this->qry = "SELECT * FROM " . ICAL . " WHERE  acl = " . $this->acl . " AND (start BETWEEN '$start' AND '$end') LIMIT " . $limit ;
		$re = $this->c->query($this->qry);
		if ($re) {
			$i = 0;
			$this->result = array();
			while ($res = $re->fetch_assoc() ) {
				$this->result[$i] = $res;
				$this->result[$i]["user"] = $this->user;
				$this->result[$i]["role"] = $this->role;
				$i += 1;
			}
			$re->free();
		}
		
	}

	public function getAllIcalByUser($token){
		$this->getUser($token);
		$this->qry = "SELECT * FROM " . ICAL . " WHERE  acl = " . $this->acl ;
		$re = $this->c->query($this->qry);
		if ($re) {
			$i = 0;
			$this->result = array();
			while ($res = $re->fetch_assoc() ) {
				$this->result[$i] = $res;
				$this->result[$i]["user"] = $this->user;
				$this->result[$i]["role"] = $this->role;
				$i += 1;
			}
			$re->free();
		}
		
	}

	public function getIcal($id){
		$id = addslashes($id);
		$this->qry = "SELECT * FROM " . ICAL . " WHERE  id = " . $id ;
		$re = $this->c->query($this->qry);
		$this->result = $re->fetch_assoc();
		$this->explode($this->result);
	}

	private function explode($data){

		$this->user = addslashes($data["user"]);
		$this->role = addslashes($data["role"]);
		$this->start = addslashes($data["start"]);
		$this->end  = addslashes($data["end"]);
		$this->rec = addslashes($data["rec"]);
		$this->state  = addslashes($data["state"]);
		$this->rappel  = addslashes($data["rappel"]);
		$this->title = addslashes($data["title"]);
		$this->acl = addslashes($data["acl"]);
	}

	public function updateIcal($data){
		$this->createQueryUpdate($data);
		$this->c->query($this->qry);
	}

	public function createQueryUpdate($data){
		$condition = " WHERE id = ";

		$qry = "UPDATE " . ICAL . " SET " ;

		$count = count($data);

		foreach ($data as $key => $value) {

			$value = addslashes($value);

			if ( $key == "id"){
				$condition .= $value ;
			}else if($count == 1) {

				$qry .= " $key = '$value' ";

			}else {
				$qry .= " $key = '$value' ,";
			}
			
			$count -= 1;

		}

		$this->qry = $qry . $condition;
	}

	public function createQueryInsert($data){

		$acl = $this->acl;

		$qr = "INSERT INTO " . ICAL . " (acl , borderColor , backgroundColor , " ;
		
		$valu =" VALUES ($acl , '" . STILL . "' , '" . STILL . "' , " ;

		$count = count($data);

		foreach ($data as $key => $value) {

			$value = addslashes($value);

			if($count == 1) {

				$valu .= " '$value' ) ";
				$qr .= " $key )";

			}else {
				$valu .= " '$value' , ";
				$qr .= " $key , ";
			}
			
			$count -= 1;

		}

		$this->qry = $qr . $valu;
	}

	public function insertIcal($data){
		$this->createQueryInsert($data);
		$this->c->query($this->qry);
	}

	public function insertIcalAndID($data){
		$this->explode($data);
		$uuid =  uniqid('ical_');
		$this->qry = "INSERT INTO " . ACL . "(user,role,token) ";
		$this->qry .= vsprintf(" values('%s$user' ,'%s$role' ,'%s$token')", array('user' => $this->user,'role' => $this->role , 'token' => $uuid ));
		$this->c->query($this->qry);
		$data["acl"] = $this->c->insert_id;
		$this->insertIcal($data);

	}

	public function parseToEvent(){
		return json_encode($this->result);
	}

	public function export($pid = "//Jemix inc//NONSGML v1.0//EN"){

		$pid = addslashes($pid);
		$this->ical ="BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-$pid\r\n";
		foreach ($this->result as $array) {
			$uuid = uniqid() ."@" .$array["user"];
			$title = $array["title"];

			$created = $this->formatDateTime($array["dt"]) ;
			$start = $this->formatDateTime($array["start"]) ;
			$end = $this->formatDateTime($array["end"]) ;

			$this->ical .= "BEGIN:VEVENT\r\nUID:$uuid\r\nCREATED:$created\r\nDTSTART:$start\r\nDTEND:$end\r\nSUMMARY:$title\r\nEND:VEVENT\r\n";
		}
		       
		$this->ical .= "END:VCALENDAR\r\n";

		$fn="calendar-" . uniqid() .".ics";

		header('Content-type: text/calendar; charset=utf-8');
		header('Content-Disposition: inline; filename="'.$fn.'"');
		echo $this->ical;
		exit;
	}

	public function import($file){
		$cal = new ICAL($file);

		$data = $cal->parseEvents();

		foreach ($data as  $d) {
			$this->insertIcal($d);
		}
	}

	public function formatDateTime($datetime){
		$strdt = new DateTime($datetime);
		return $strdt->format("Ymd") . "T" . $strdt->format("His") . "Z" ;
	}

}

class Config{

	private $qry;
	private $c;

	public function __construct($ical = ICAL ,$acl = ACL ){
		$this->c = new mysqli(HOST, USER, PW, DB);

		$this->qry = "

		CREATE TABLE IF NOT EXISTS $acl(
			id int auto_increment,
			user varchar(200) not null,
			role varchar(200) not null,
			token varchar(400),
			dt timestamp default current_timestamp,
			primary key(id),
			unique(user)
		);

		CREATE TABLE IF NOT EXISTS $ical(
			id int auto_increment,
			acl int(11) not null,
			start DATETIME not null,
			end DATETIME not null,
			rec varchar(200) ,
			state int(1) default 1 not null,
			backgroundColor varchar(40) not null,
			borderColor varchar(40) not null,
			rappel time ,
			title varchar(500) not null,
			des varchar(1000),
			ref varchar(1000),
			dt timestamp default current_timestamp,
			primary key(id)
		);


		ALTER TABLE $ical add constraint fk_acl_$ical foreign key (acl) references $acl(id);

		";

		if($this->c->multi_query($this->qry)){
			echo "config success";
		}
	}
}

class ICal{
    /* How many ToDos are in this ical? */
    public  /** @type {int} */ $todo_count = 0;
    /* How many events are in this ical? */
    public  /** @type {int} */ $event_count = 0; 
    /* The parsed calendar */
    public /** @type {Array} */ $cal;
    /* Which keyword has been added to cal at last? */
    private /** @type {string} */ $_lastKeyWord;
    /** 
     * Creates the iCal-Object
     * 
     * @param {string} $filename The path to the iCal-file
     *
     * @return Object The iCal-Object
     */ 
    public function __construct($filename) {
        if (!$filename) {
            return false;
        }
        
        $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (stristr($lines[0], 'BEGIN:VCALENDAR') === false) {
            return false;
        } else {
            // TODO: Fix multiline-description problem (see http://tools.ietf.org/html/rfc2445#section-4.8.1.5)
            foreach ($lines as $line) {
                $line = trim($line);
                $add  = $this->keyValueFromString($line);
                if ($add === false) {
                    $this->addCalendarComponentWithKeyAndValue($type, false, $line);
                    continue;
                } 
                list($keyword, $value) = $add;
                switch ($line) {
                // http://www.kanzaki.com/docs/ical/vtodo.html
                case "BEGIN:VTODO": 
                    $this->todo_count++;
                    $type = "VTODO"; 
                    break; 
                // http://www.kanzaki.com/docs/ical/vevent.html
                case "BEGIN:VEVENT": 
                    //echo "vevent gematcht";
                    $this->event_count++;
                    $type = "VEVENT"; 
                    break; 
                //all other special strings
                case "BEGIN:VCALENDAR": 
                case "BEGIN:DAYLIGHT": 
                    // http://www.kanzaki.com/docs/ical/vtimezone.html
                case "BEGIN:VTIMEZONE": 
                case "BEGIN:STANDARD": 
                    $type = $value;
                    break; 
                case "END:VTODO": // end special text - goto VCALENDAR key 
                case "END:VEVENT": 
                case "END:VCALENDAR": 
                case "END:DAYLIGHT": 
                case "END:VTIMEZONE": 
                case "END:STANDARD": 
                    $type = "VCALENDAR"; 
                    break; 
                default:
                    $this->addCalendarComponentWithKeyAndValue($type, 
                                                               $keyword, 
                                                               $value);
                    break; 
                } 
            }
            return $this->cal; 
        }
    }
    /** 
     * Add to $this->ical array one value and key.
     * 
     * @param {string} $component This could be VTODO, VEVENT, VCALENDAR, ... 
     * @param {string} $keyword   The keyword, for example DTSTART
     * @param {string} $value     The value, for example 20110105T090000Z
     *
     * @return {None}
     */ 
    public function addCalendarComponentWithKeyAndValue($component, $keyword, $value) {
        if ($keyword == false) { 
            $keyword = $this->last_keyword; 
            switch ($component) {
            case 'VEVENT': 
                $value = $this->cal[$component][$this->event_count - 1]
                                               [$keyword].$value;
                break;
            case 'VTODO' : 
                $value = $this->cal[$component][$this->todo_count - 1]
                                               [$keyword].$value;
                break;
            }
        }
        
        if (stristr($keyword, "DTSTART") or stristr($keyword, "DTEND")) {
            $keyword = explode(";", $keyword);
            $keyword = $keyword[0];
        }
        switch ($component) { 
        case "VTODO": 
            $this->cal[$component][$this->todo_count - 1][$keyword] = $value;
            //$this->cal[$component][$this->todo_count]['Unix'] = $unixtime;
            break; 
        case "VEVENT": 
            $this->cal[$component][$this->event_count - 1][$keyword] = $value; 
            break; 
        default: 
            $this->cal[$component][$keyword] = $value; 
            break; 
        } 
        $this->last_keyword = $keyword; 
    }
    /**
     * Get a key-value pair of a string.
     *
     * @param {string} $text which is like "VCALENDAR:Begin" or "LOCATION:"
     *
     * @return {array} array("VCALENDAR", "Begin")
     */
    public function keyValueFromString($text) {
        preg_match("/([^:]+)[:]([\w\W]*)/", $text, $matches);
        if (count($matches) == 0) {
            return false;
        }
        $matches = array_splice($matches, 1, 2);
        return $matches;
    }
    /** 
     * Return Unix timestamp from ical date time format 
     * 
     * @param {string} $icalDate A Date in the format YYYYMMDD[T]HHMMSS[Z] or
     *                           YYYYMMDD[T]HHMMSS
     *
     * @return {int} 
     */ 
    public function iCalDateToUnixTimestamp($icalDate) { 
        $icalDate = str_replace('T', '', $icalDate); 
        $icalDate = str_replace('Z', '', $icalDate); 
        $pattern  = '/([0-9]{4})';   // 1: YYYY
        $pattern .= '([0-9]{2})';    // 2: MM
        $pattern .= '([0-9]{2})';    // 3: DD
        $pattern .= '([0-9]{0,2})';  // 4: HH
        $pattern .= '([0-9]{0,2})';  // 5: MM
        $pattern .= '([0-9]{0,2})/'; // 6: SS
        preg_match($pattern, $icalDate, $date); 
        // Unix timestamp can't represent dates before 1970
        if ($date[1] <= 1970) {
            return false;
        } 
        // Unix timestamps after 03:14:07 UTC 2038-01-19 might cause an overflow
        // if 32 bit integers are used.
        $timestamp = mktime((int)$date[4], 
                            (int)$date[5], 
                            (int)$date[6], 
                            (int)$date[2],
                            (int)$date[3], 
                            (int)$date[1]);
        return date("Y-m-d H:i:s",$timestamp); 
    } 

    public function events() {
        $array = $this->cal;
        return $array['VEVENT'];
    }

    public function parseEvents(){
    	$ev = $this->events();
    	$i = 0;
    	$result = [];
    	foreach ($ev as $event) {
    		$event["DTEND"] = $this->iCalDateToUnixTimestamp($event["DTEND"]);
    		$event["DTSTART"] = $this->iCalDateToUnixTimestamp($event["DTSTART"]);
    		$result[$i] =  array('start' => $event["DTSTART"],
    							 'end' => $event["DTEND"], 
    							 'title' => $event["SUMMARY"] );
    		$i+= 1;
    	}
    	return $result;
    }

}

$t = new Task();

date_default_timezone_set(TIMEZONE);

if(isset($_GET['tk']) && isset($_GET['start']) && isset($_GET['end'])){
	$t->getIcalByUser($_GET['tk'],$_GET['start'],$_GET['end']);
	echo $t->parseToEvent();
}

if(isset($_POST["id"]) && is_numeric($_POST["id"])){

	$t->updateIcal($_POST);

}

if (isset($_GET["tk"]) && isset($_GET["exp"])) {
	$tk = addslashes($_POST["tk"]);
	$t->getAllIcalByUser($tk);
 	$t->export();
}


if (isset($_FILES["fn"]) ) {
	$tk = addslashes($_GET["tk"]);
	$t->getAllIcalByUser($tk);
 	$t->import($_FILES["fn"]["tmp_name"]);

}


?>