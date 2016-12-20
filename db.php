<?php


define("HOST", "127.0.0.1");

define("PW", "sdfpro");

define("USER", "root");

define("DB", "ical");

define("ACL" , "acl");

define("ICAL" , "ical");

define("STILL","#872D62");
define("ENDS","#57B707");
define("PAUSE","#542B72");


date_default_timezone_set('UTC');


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

	public function getIcalByUser($token,$limit = 30){
		$this->getUser($token);
		$limit = addslashes($limit);
		$this->qry = "SELECT * FROM " . ICAL . " WHERE  acl = " . $this->acl . " LIMIT " . $limit ;
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

	public function updateIcal($id,$data){

		$id= addslashes($id);

		$this->explode($data);

		$this->qry = "UPDATE " . ACL . " SET " . " user ='".$this->user."'  , role ='".$this->role."' WHERE id =" . $this->acl;
		$this->c->query($this->qry);

		$this->qry = "UPDATE " . ICAL . " SET " . " start='".$this->start."' , end ='".$this->end."' , rec ='".$this->rec."', state ='".$this->state."',rappel='".$this->rappel."', title='".$this->title."' WHERE id =" . $id;
		$this->c->query($this->qry);

	}

	public function insertIcal($data){
		$this->explode($data);
		$this->qry = "INSERT INTO " . ICAL . "(acl,start,end,rec,state,rappel,title) ";
		$this->qry .= vsprintf(" values(%d$acl ,'%s$start' ,'%s$end' ,'%s$rec' ,%d$state,'%s$rappel' , '%s$title')", array('acl' => $this->acl,'start' => $this->start , 'end' => $this->end , 'rec' => $this->rec , 'colorstate' =>  STILL,'rappel' => $this->rappel , 'title' => $this->title));

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




$t = new Task();

$t->getIcalByUser('token');

echo $t->parseToEvent();

?>