<?php
require './config.php';
require 'Pivotaltracker.php';

class AirTimer
{
	private $pivotalToken;
	private $pivotalMembershipId;
	private $dbh;

	public function login($args)
	{
		$this->_checkCurl();
		
		$pivotalUser = $args['u'];
		$pivotalPass = $args['p'];

		$pivotal = new PivotalTracker();
		
		$this->pivotalToken = $pivotal->getUserToken($pivotalUser,$pivotalPass);
		if($this->pivotalToken == false){
			$this->exception($pivotal->output);
		}

		$this->insertUser($pivotalUser,$this->pivotalToken, $pivotal->membershipId);

		$this->showJson(true,array('token' => $this->pivotalToken, 'membershipId' => (string)$pivotal->membershipId));
	}

	public function logAction($args)
        {
            $this->_checkCurl();
            
            $this->pivotalToken = $args['token'];
            $membershipId = (int)$args['membershipId'];
            $storyId = (int)$args['storyId'];
            $projectId = (int)$args['projectId'];
            $actionType = ($args['actionType'] == 'start')?1:0;
            
            $pivotal = new PivotalTracker();
            
            $dbh = $this->_getDbh();
		$sqlInsertLog = "INSERT INTO timelog (projectId,storyId,membership_id,action_type) VALUES('%s','%s','%s','%s');";
		$sqlInsertLog = sprintf($sqlInsertLog,$projectId,$storyId,$membershipId,$actionType);
		
		$inserted = $dbh->exec($sqlInsertLog);
		if($inserted == 0){
			$this->exception('Insert failed.');
		}else{
			$this->showJson(true,array('message' => 'Logged Successfuly.', 'log_id' => $dbh->lastInsertId()));
		}
        }

	public function showJson($success,$content){
		$j = array_merge(array('success' =>$success), $content);
		die(json_encode($j));
	}

	public static function exception($msg){
		$r = json_encode(array(
				'success' => false,
				'message' => $msg
			));
		die($r);
	}

	private function insertUser($email, $pivotalToken, $pivotalMembershipId)
	{
		$dbh = $this->_getDbh();
		$sqlGetExisting = "SELECT * FROM users WHERE email = '%s' and pivotal_token = '%s' and pivotal_membership_id = '%s'";
		$sqlGetExisting = sprintf($sqlGetExisting,$email,$pivotalToken,$pivotalMembershipId);
		
		$existingUser = $dbh->query($sqlGetExisting)->fetch();
		if(!$existingUser){
			$sqlInsertNewUser = "INSERT INTO users(name,email,pivotal_token, pivotal_membership_id) values('%s','%s','%s','%s')";
			$sqlInsertNewUser = sprintf($sqlInsertNewUser,'',$email,$pivotalToken,$pivotalMembershipId);

			return $dbh->exec($sqlInsertNewUser);
		}else{
			return $existingUser['user_id'];
		}

	}

	private function _getDbh(){
		if($this->dbh != null){
			return $this->dbh;
		}
		try{
			$this->dbh = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD);
		}catch(PDOException $e){
			$this->exception('Nao foi possivel estabelecer uma conexao com o banco de dados AIR TIMER. ' . $e->getMessage());
		}
		return $this->dbh;
	}

	private function _checkCurl(){
		if(!function_exists('curl_exec'))
			die("Curl deve estar ativo");
	}
}