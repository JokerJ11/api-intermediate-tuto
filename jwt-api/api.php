<?php 

class Api extends Rest {
	public $dbConn;

	public function __construct() {
		parent::__construct();

		$db = new DbConnect;
		$this->dbConn = $db->connect();

	}

	public function generateToken() {
		// print_r($this->param);
		$email = $this->validateParameter('email', $this->param['email'], STRING);
		$pass = $this->validateParameter('pass', $this->param['pass'], STRING);
		// print_r($email);
		// print_r($pass);
		// exit();
			try {
				$stmt = $this->dbConn->prepare("SELECT * FROM users WHERE email = :email AND password = :pass");
				$stmt->bindParam(":email", $email);
				$stmt->bindParam(":pass", $pass);
				$stmt->execute();
				$user = $stmt->fetch(PDO::FETCH_ASSOC);
				// print_r($user);
				// exit();
				if(!is_array($user)) {
					$this->returnResponse(INVALID_USER_PASS, "email or password is incorrect.");
				}

				if($user['active'] == 0) {
					$this->returnResponse(USER_NOT_ACTIVE, "User is not activated. Please connect to admin.");
				}

				$payload = [
					'iat' => time(), //issue_at
					'iss' => 'localhost', // issue
					'exp' => time() + (15 * 60), //current time added 1 min expire
					'userId' => $user['id']
				];

				$token = JWT::encode($payload, SECRETE_KEY);
				// echo $token;
				$data = ['token' => $token];
				// print_r($data);
				$this->returnResponse(SUCCESS_RESPONSE, $data);
			} catch (Exception $e) {
				$this->throwError(JWT_PROCESSING_ERROR, $e->getMessage());
			}
	}

	public function addCustomer() {
		$name = $this->validateParameter('name', $this->param['name'], STRING);
		$email = $this->validateParameter('email', $this->param['email'], STRING);
		$mobile = $this->validateParameter('mobile', $this->param['mobile'], INTEGER);
		$addr = $this->validateParameter('addr', $this->param['addr'], STRING);

		try {
			$token = $this->getBearerToken();
			$payload = JWT::decode($token, SECRETE_KEY, ['HS256']);
			// print_r($payload);
		} catch (Exception $e) {
			$this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
		}

		$cust = new Customer;
		$cust->setName($name);
		$cust->setEmail($email);
		$cust->setAddress($addr);
		$cust->setMobile($mobile);
		$cust->setCreatedBy($payload->userId);
		$cust->setCreatedOn(date('Y-m-d'));

		if(!$cust->insert()) {
			$message = "Failed to Inserted";
		} else {
			$message = "Inserted Successfully";
		}

		$this->returnResponse(SUCCESS_RESPONSE, $message);

	}

	public function getCustomerDetails() {
		$customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
		// echo $customerId;
		$cust = new Customer;
		$cust->setId($customerId);
		$customer = $cust->getCustomerDetailsById();
		// print_r($customer);

		if(!is_array($customer)) {
			$this->returnResponse(ERR_RESPONSE, ['message' => 'Customer details not found.']);
		}

		$response['customerId'] = $customer['id'];
		$response['customerName'] = $customer['name'];
		$response['email'] = $customer['email'];
		$response['mobile'] = $customer['mobile'];
		$response['address'] = $customer['address'];
		$response['createdBy'] = $customer['created_user'];
		$response['lastUpdatedBy'] = $customer['updated_user'];

		$this->returnResponse(SUCCESS_RESPONSE, $response);
	}

		public function updateCustomer() {
		$customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);
		$name = $this->validateParameter('name', $this->param['name'], STRING);
		$mobile = $this->validateParameter('mobile', $this->param['mobile'], INTEGER);
		$addr = $this->validateParameter('addr', $this->param['addr'], STRING);

		try {
			$token = $this->getBearerToken();
			$payload = JWT::decode($token, SECRETE_KEY, ['HS256']);
			// print_r($payload);
		} catch (Exception $e) {
			$this->throwError(ACCESS_TOKEN_ERRORS, $e->getMessage());
		}

		$cust = new Customer;
		$cust->setId($customerId);
		$cust->setName($name);
		$cust->setAddress($addr);
		$cust->setMobile($mobile);
		$cust->setUpdatedBy($payload->userId);
		$cust->setUpdatedOn(date('Y-m-d'));

		if(!$cust->update()) {
			$message = "Failed to Updated";
		} else {
			$message = "Updated Successfully";
		}

		$this->returnResponse(SUCCESS_RESPONSE, $message);

	}

	public function deleteCustomer() {
		$customerId = $this->validateParameter('customerId', $this->param['customerId'], INTEGER);

		$cust = new Customer;
		$cust->setId($customerId);

		if(!$cust->delete()) {
			$message = "Failed to Deleted";
		} else {
			$message = "Deleted Successfully";
		}	

		$this->returnResponse(SUCCESS_RESPONSE, $message);

	}

}

?>