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
	    //check if the request is duplicated
	    $selectLastTime = "SELECT actionTime, actionType FROM timelog WHERE storyId=:storyId AND projectId=:projectId AND membershipId=:membershipId ORDER BY actionTime DESC LIMIT 1";
	    $sth = $dbh->prepare($selectLastTime, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	    $sth->execute(array(':storyId' => $args['storyId'], ':projectId' => $args['projectId'], ':membershipId' => $args['membershipId']));
	    $result = $sth->fetchAll();
	    if(isset($result[0]['actionType']) AND $result[0]['actionType'] == $actionType){
		$msg = sprintf('The story is already %s.', ($actionType==0)?'stopped':'started');
		$this->exception($msg);
	    }else if($actionType==0 AND !isset($result[0])){
		$this->exception('This story has not been started.');
	    }
	    
	    $sqlInsertLog = "INSERT INTO timelog (projectId,storyId,membershipId,actionType) VALUES('%s','%s','%s','%s');";
	    $sqlInsertLog = sprintf($sqlInsertLog,$projectId,$storyId,$membershipId,$actionType);

	    $inserted = $dbh->exec($sqlInsertLog);
	    if($inserted == 0){
		    $this->exception('Insert failed.');
	    }else{
		    if($actionType==0){
			$this->updateDuration($args);
		    }
		    $this->showJson(true,array('message' => 'Logged Successfuly.', 'log_id' => $dbh->lastInsertId()));
	    }
        }
	
	public function updateDuration($args){
	    $dbh = $this->_getDbh();
	    $stop_log_id = (isset($args['logId']))?$args['logId']:$dbh->lastInsertId();
	    $selectLastTime = "SELECT actionTime, actionType, duration FROM timelog WHERE storyId=:storyId AND projectId=:projectId AND membershipId=:membershipId AND actionType = 1 ORDER BY actionTime DESC LIMIT 1";

	    $sth = $dbh->prepare($selectLastTime, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	    $sth->execute(array(':storyId' => $args['storyId'], ':projectId' => $args['projectId'], ':membershipId' => $args['membershipId']));
	    
	    $result = $sth->fetchAll();
	    $last_action_time = $result[0]['actionTime'];
	    
	    $sqlUpdate = "UPDATE timelog SET duration = CURRENT_TIMESTAMP-TIMESTAMP('%s') WHERE log_id=%s";
	    $sqlUpdate = sprintf($sqlUpdate,$last_action_time,$stop_log_id);
	    $affr = $dbh->exec($sqlUpdate);
	    return ($affr);
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