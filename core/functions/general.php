<?php
function email($to, $subject, $body) {
	mail($to, $subject, $body, 'From: hello@phpacademy.org');
}

function logged_in_redirect() {
	if (logged_in() === true) {
		header('Location: index.php');
		exit();
	}
}

function protect_page() {
	if (logged_in() === false) {
		header('Location: protected.php');
		exit();
	}
}

function admin_protect($pdo) {
	global $user_data;
	if (has_access($user_data['user_id'], 1, $pdo) === false) {
		header('Location: index.php');
		exit();
	}
}

function array_sanitize(&$item, $key, $prefix='') {

        if($prefix){
            $item = "$item=:$item";
        }else{
            $item = ":$item";
        }
    
}

function sanitize($data) {
	return htmlentities(strip_tags(mysql_real_escape_string($data)));
}

function output_errors($errors) {
	return '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
}
?>