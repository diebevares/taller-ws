<?php
use Slim\Http\Request;
use Slim\Http\Response;

// Routes
$app->get('/login', function (Request $request, Response $response, array $args) {
	$data = $request->getQueryParams();
	$username = $data['username'];
    $password = $data['password'];
	
	$sql = "
		SELECT id, name, email, username
			FROM User
		WHERE (username=:username or email=:username) and password=:password
	";
	
	$stmt = $this->db->prepare($sql);
	$stmt->bindParam("username", $username, PDO::PARAM_STR);
	$stmt->bindParam("password", hash('sha256', $password), PDO::PARAM_STR);
	$stmt->execute();
	$userData = $stmt->fetch(PDO::FETCH_OBJ);
	
	try {
		if($userData) {
			return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
				array( 'userData' => $userData )
			);
		} else {
			return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
				array( 'error' => array( 'text' => 'Usuario o contraseña erroneos' ) )
			);
		}	
	} catch(PDOException $e) {
		return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
			array( 'error' => array( 'text' => $e->getMessage() ) )
		);
	}
});


$app->get('/signup', function (Request $request, Response $response, array $args) {
	$data = $request->getQueryParams();
	$email = $data['email'];
    $name = $data['name'];
    $username = $data['username'];
    $password = $data['password'];
	
	try {
		$username_check = preg_match('~^[A-Za-z0-9_]{3,20}$~i', $username);
        $email_check = preg_match('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9._-]+\.([a-zA-Z]{2,4})$~i', $email);
		
		if(strlen(trim($username))> 0 && strlen(trim($password))>0 && strlen(trim($email))>0 && $email_check>0 && $username_check>0) {
			$sql = "
				SELECT id
					FROM User
				WHERE username=:username or email=:email
			";
			
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("username", $username, PDO::PARAM_STR);
            $stmt->bindParam("email", $email, PDO::PARAM_STR);
            $stmt->execute();
            $mainCount=$stmt->rowCount();
			
            if($mainCount == 0) {
				$sql1 = "
					INSERT INTO User(username, password, email, name)
						VALUES(:username, :password, :email, :name)
				";
				
				$stmt1 = $this->db->prepare($sql1);
				$stmt1->bindParam("username", $username, PDO::PARAM_STR);
				$stmt1->bindParam("password", hash('sha256', $password), PDO::PARAM_STR);
				$stmt1->bindParam("email", $email, PDO::PARAM_STR);
				$stmt1->bindParam("name", $name, PDO::PARAM_STR);
				$stmt1->execute();
				
				try {
					$sql2 = "
						SELECT id, name, email, username
							FROM User
						WHERE username=:input or email=:input
					";
					
					$stmt2 = $this->db->prepare($sql2);
					
					$stmt2->bindParam("input", $email, PDO::PARAM_STR);
					$stmt2->execute();
					$userData = $stmt2->fetch(PDO::FETCH_OBJ);
				} catch(PDOException $e) {
					$userData = null;
				} 
			}
			
			if($userData){
				return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
					array( 'userData' => $userData )
				);
			} else {
				return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
					array( 'error' => array( 'text' => 'Información Invalida' ) )
				);
			}
		} else {
			return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
				array( 'error' => array( 'text' => 'Información Invalida' ) )
			);
		}
	} catch(PDOException $e) {
		return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
			array( 'error' => array( 'text' => $e->getMessage() ) )
		);
	}
	
	
});




$app->get('/getFeeds', function (Request $request, Response $response, array $args) {
	$data = $request->getQueryParams();
	$fk_user = $data['fk_user'];
	
	try  {
		$sql = "
			SELECT feed, DATE_FORMAT(date, '%Y-%m-%d') as date
				FROM Feed
			WHERE fk_user=:fk_user ORDER BY id DESC LIMIT 5
		";
		
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam("fk_user", $fk_user, PDO::PARAM_INT);
		$stmt->execute();
		$feeds = $stmt->fetchAll(PDO::FETCH_OBJ);
		
		if($feeds) {
			return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
				array( 'feeds' => $feeds )
			);
		} else {
			return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
				array( 'feeds' => '' )
			);
		}
	} catch(PDOException $e) {
		return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
			array( 'error' => array( 'text' => $e->getMessage() ) )
		);
	}
});

$app->get('/createFeed', function (Request $request, Response $response, array $args) {
	$data = $request->getQueryParams();
	$fk_user = $data['fk_user'];
	$feed = $data['feed'];
	
	try  {
		$sql = "
			INSERT INTO Feed(feed, date, fk_user)
				VALUES (:feed, FROM_UNIXTIME(:date), :fk_user)
		";
		
		$stmt = $this->db->prepare($sql);
		$stmt->bindParam("fk_user", $fk_user, PDO::PARAM_INT);
		$stmt->bindParam("date", time(), PDO::PARAM_INT);
		$stmt->bindParam("feed", $feed, PDO::PARAM_STR);
		$stmt->execute();
		
		try {
			$sql1 = "
				SELECT id, DATE_FORMAT(date, '%Y-%m-%d') as date, feed
					FROM Feed
				WHERE fk_user = :fk_user ORDER BY id DESC LIMIT 1
			";
			
			$stmt1 = $this->db->prepare($sql1);
			$stmt1->bindParam("fk_user", $fk_user, PDO::PARAM_INT);
			$stmt1->execute();
			$feedData = $stmt1->fetch(PDO::FETCH_OBJ);
			
			return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
				array( 'feedData' => $feedData )
			);
		} catch(PDOException $e) {
			return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
				array( 'error' => array( 'text' => $e->getMessage() ) )
			);
		}
	} catch(PDOException $e) {
		return $this->response->withHeader('Access-Control-Allow-Origin', '*')->withJson(
			array( 'error' => array( 'text' => $e->getMessage() ) )
		);
	}
});


?>