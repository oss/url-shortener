<?php

require_once('secure_sessions.inc.php');

class session {

	function
	__construct() {

		// set our custom session functions
		session_set_save_handler(
			array($this, 'open'),
				array($this, 'close'),
					array($this, 'read'),
						array($this, 'write'),
							array($this, 'destroy'),
								array($this, 'gc'));
		// prevent unexpected effects when using objects as save handlers
		register_shutdown_function('session_write_close');
	}

	function
	start_session($session_name, $secure) {

		// make sure the session cookie is not accessable via javascript
		$httponly = true;
			
		// hash algorithm to use for the sessionid
		$session_hash = 'sha512';			

		// if the hash is available, then set the hash function
		if (in_array($session_hash, hash_algos()))
			ini_set('session.hash_function', $session_hash);
			
		// set the hash to 5 bits per character
		ini_set('session.hash_bits_per_character', 5);

		// force the session to only use cookies, not url variables
		ini_set('session.use_only_cookies', 1);
	
		// get the session cookie parameters then set them
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params(
			$cookieParams['lifetime'],
				$cookieParams['path'],
					$cookieParams['domain'],
						$secure,
							$httponly);
		// change the session name
		session_name($session_name);
	
		// start the session
		session_start();
			
		// regenerate the session and delete the old one
		// and generate new encryption key in database
		session_regenerate_id(true);
	}

	function
	open() {
			
		$this->db = new mysqli($db_host,$db_user,$db_pass,$db_name);
		return true;
	}

	function
	close() {
		$this->db->close();
		return true;
	}
	
	function
	read($id) {

		// we rely on prepared statements for security and performance benefits
		if (!isset($this->read_stmt)) {
			$prepared_stmt = "SELECT data FROM sessions WHERE id = ? LIMIT 1");
			$this->read_stmt = $this->db->prepare($prepared_stmt);
		}
		$this->read_stmt->bind_param('s', $id);
		$this->read_stmt->execute();
		$this->read_stmt->store_result();
		$this->read_stmt->bind_result($data);
		$this->read_stmt->fetch();
		$key = $this->getkey($id);
		$data = $this->decrypt($data, $key);
		return $data;
	}
	
	function 
	write($id, $data) {

		// get unique key and encrypt the data
		$key = $this->getkey($id);
		$data = $this->encrypt($data, $key);
		
		$time = time();
		if(!isset($this->w_stmt)) {
			$prepared_stmt = "REPLACE INTO sessions (";
			$prepared_stmt = $prepared_stmt."id, set_time, data, session_key";
			$prepared_stmt = $prepared_stmt.") VALUES (?, ?, ?, ?)";
			$this->w_stmt = $this->db->prepare($prepare_stmt);
		}
		$this->w_stmt->bind_param('siss', $id, $time, $data, $key);
		$this->w_stmt->execute();
		return true;
	}

	function
	destroy($id) {
		if (!isset($this->delete_stmt)) {
			$prepared_statement = "DELETE FROM sessions WHERE id = ?";
			$this->delete_stmt = $this->db->prepare($prepared_statement);
		}
		$this->delete_stmt->bind_param('s', $id);
		$this->delete_stmt->execute();
		return true;
	}

	// garbage collection
	function
	gc($max) {
		if(!isset($this->gc_stmt)) {
			$prepared_statement = "DELETE FROM session WHERE set_time < ?";
			$this->gc_stmt = $this->db->prepare($prepared_statement);
		}
		$old = time() - $max;
		$this->gc_stmt->bind_param('s', $old);
		$this->gc_stmt->execute();
		return true;
	}

	private function
	getkey($id) {
		if(!isset($this->key_stmt)) {
			$prepared_statement = "SELECT session_key FROM sessions ";
			$prepared_statement = $prepared_statement."WHERE id = ? LIMIT 1";
		}
		$this->key_stmt->bind_param('s', $id);
		$this->key_stmt->execute();
		$this->key_stmt->store_result();
		if ($this->key_stmt->num_rows == 1) {
			$this->key_stmt->bind_result($key);
			$this->key_stmt->fetch();
			return $key;
		}
		else {
			$random_key=hash('sha512',uniqid(mt_rand(1,mt_getrandmax()),true));
		}
	}
	
	private function
	encrypt($data, $key) {

		$salt='cH!swe!retReGu7W6bEDRup7usuDUh9THeD2CHeGE*ewr4n39=E@rAsp7c-Ph@pH';
		$key = substr(hash('sha256', $salt.$key.$salt), 0, 32);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$encrypted = base64_encode(mcrypt_encrypt(
									MCRYPT_RIJNDAEL_256, 
										$key, $data, 
											MCRYPT_MODE_ECB, $iv));
		return $encrypted;
	}

	private function
	decrypt($data, $key) {
		$salt='cH!swe!retReGu7W6bEDRup7usuDUh9THeD2CHeGE*ewr4n39=E@rAsp7c-Ph@pH';
		$key = substr(hash('sha256', $salt.$key.$salt), 0, 32);
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256,
						$key, base64_decode($data),
							MCRYPT_MODE_ECB, $iv);
		return $decrypted;
	}
}
?>
