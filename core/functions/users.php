<?php
function change_profile_image($user_id, $file_temp, $file_extn, $pdo) {
	$file_path = 'images/profile/' . substr(md5(time()), 0, 10) . '.' . $file_extn;
	move_uploaded_file($file_temp, $file_path);
        $statement = $pdo->prepare("UPDATE users SET profile=:filepath WHERE user_id=:user_id");
        $statement->execute(array(':filepath' => $file_path, ':user_id' => $user_id));
}

function mail_users($subject, $body, $pdo) {
        $statement = $pdo->prepare("SELECT email, first_name FROM users WHERE allow_email =1");
        $statement->execute();
	while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
		email($row['email'], $subject, "Hello " . $row['first_name'] . ",\n\n" . $body);
	}
}

function has_access($user_id, $type, $pdo) {
	$user_id 	= (int)$user_id;
	$type 		= (int)$type;
	
        $statement = $pdo->prepare("SELECT * FROM users WHERE user_id=:user_id AND type=:type");
        $statement->execute(array(':user_id' => $user_id, ':type' => $type));
        return $statement->rowCount() ? True : False;
}

function recover($mode, $email, $pdo) {
	$mode 		= sanitize($mode);
	$email		= sanitize($email);
	
	$user_data 	= user_data(user_id_from_email($email, $pdo), $pdo, 'user_id', 'first_name', 'username');
	
	if ($mode == 'username') {
		email($email, 'Your username', "Hello " . $user_data['first_name'] . ",\n\nYour username is: " . $user_data['username'] . "\n\n-phpacademy");
	} else if ($mode == 'password') {
		$generated_password = substr(md5(rand(999, 999999)), 0, 8);
		change_password($user_data['user_id'], $generated_password, $pdo);
		
		update_user($user_data['user_id'], array('password_recover' => '1'), $pdo);
		
		email($email, 'Your password recovery', "Hello " . $user_data['first_name'] . ",\n\nYour new password is: " . $generated_password . "\n\n-phpacademy");
	}
}

function update_user($user_id, $update_data, $pdo) {
	
        $field_mask = array_keys($update_data);
        array_walk($field_mask, 'array_sanitize');
        $field_mask_prefix = array_keys($update_data);
        array_walk($field_mask_prefix, 'array_sanitize', $prefix = True);
        
        $statement = $pdo->prepare("UPDATE users SET " . implode(', ', $field_mask_prefix) . " WHERE user_id=:user_id");
     
        $update_data = array_combine($field_mask, $update_data);
        $update_data[':user_id'] = $user_id;
        $statement->execute($update_data);
        
}

function activate($email, $email_code, $pdo) {
	
        $statement = $pdo->prepare("SELECT * FROM users WHERE email=:email AND email_code=:email_code AND active=0");
        $statement->execute(array(':email' => $email, ':email_code' => $email_code));
        if ($statement->rowCount()){
            $statement = $pdo->prepare("UPDATE users SET active=1 WHERE email=:email");
            $statement->execute(array(':email'=> $email));
            return True;
        } else {
            return False;
        }
        
}

function change_password($user_id, $password, $pdo) {
	$user_id = (int)$user_id;
	$password = md5($password);
	$statement = $pdo->prepare("UPDATE users SET password=:password, password_recover=0 WHERE user_id=:user_id");
        $statement->execute(array(':password' => $password, ':user_id' => $user_id));
}

function register_user($register_data, $pdo) {
        $register_data['password'] = md5($register_data['password']);
        $fields = implode(',', array_keys($register_data));
        
        $masks_field = array_keys($register_data);
        array_walk($masks_field, 'array_sanitize');
        $values = implode(',', $masks_field);
        
        $sql = "INSERT INTO users ($fields) VALUES ($values)";
       
        $statement = $pdo->prepare($sql);
        $statement->execute(array_combine($masks_field, array_values($register_data)));
	email($register_data['email'], 'Activate your account', "Hello " . $register_data['first_name'] . ",\n\nYou need to activate your account, so use the link below:\n\nhttp://localhost/lr/activate.php?email=" . $register_data['email'] . "&email_code=" . $register_data['email_code'] . "\n\n - phpacademy");
}

function user_count($pdo) {
        $statement = $pdo->prepare("SELECT * FROM users WHERE active=1");
        $statement->execute();
        return $statement->rowCount();
}

function user_data($user_id, $pdo) {
	$data = array();
	$user_id = (int)$user_id;
	
	$func_num_args = func_num_args();
	$func_get_args = func_get_args();
	
	if ($func_num_args > 2) {
		unset($func_get_args[0]);
		unset($func_get_args[1]);
                $fields = implode(',', $func_get_args);
                
                $statement = $pdo->prepare("SELECT $fields FROM users WHERE user_id=:user_id");
                $statement->execute(array(':user_id' => $user_id));
		
		return $statement->fetch(PDO::FETCH_ASSOC);
	}
}

function logged_in() {
	return (isset($_SESSION['user_id'])) ? true : false;
}

function user_exists($username, $pdo) {
        $sql = "SELECT * FROM users WHERE username = :username";
        $statement = $pdo->prepare($sql);
        $statement->execute(array(':username' => $username));
        return $statement->rowCount() ? True : False;
        
}

function email_exists($email, $pdo) {
        $statement = $pdo->prepare("SELECT * FROM users WHERE email=:email");
        $statement->execute(array(':email' => $email));
        return $statement->rowCount() ? True: False;
}

function user_active($username, $pdo) {
        $statement = $pdo->prepare("SELECT * FROM users WHERE username=:username AND active=1");
        $statement->execute(array(':username'=> $username));
        return $statement->rowCount() ? True : False;
}

function user_id_from_username($username, $pdo) {
        $statement = $pdo->prepare("SELECT user_id FROM users WHERE username=:username");
        $statement->execute(array(':username' => $username));
        return $statement->fetchColumn();
}

function user_id_from_email($email, $pdo) {
        $statement = $pdo->prepare("SELECT user_id FROM users WHERE email=:email");
        $statement->execute(array(':email' => $email));
        return $statement->fetchColumn();
}

function login($username, $password, $pdo) {
	$user_id = user_id_from_username($username, $pdo);
        $statement = $pdo->prepare("SELECT user_id FROM users WHERE username=:username AND password=:password");
        $statement->execute(array(':username' => $username, ':password' => md5($password)));
        return $statement->rowCount() ? $statement->fetchColumn() : False;
}
?>