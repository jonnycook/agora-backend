<?php

require_once('includes/header.php');
require_once('includes/user.php');

if ($_POST['submit']) {
	if ($_POST['password'] == $_POST['confirmPassword']) {
		$email = mysqli_real_escape_string($mysqli, $_POST['email']);
		$password = passHash($_POST['password']);
		mysqli_query($mysqli, "INSERT INTO users SET email = '$email', password = '$password', created_at = UTC_TIMESTAMP()") or die(mysqli_error($mysqli));
		$id = mysqli_insert_id($mysqli);
		setUserId($id);
		echo 'Successfully created account.';
		exit;
	}
	else {
		$error = 'Passwords do not match.';
	}
}

?>

<?php if ($error): ?>
	<span style="color:red"><?php echo $error ?></span>
<?php endif ?>

<form method="post">
	<ul>
		<li>
			<label>Email:</label> <input type="text" name="email" value="<?php echo $_POST['email'] ?>">
		</li>
		<li>
			<label>Password:</label> <input type="password" name="password">
		</li>
		<li>
			<label>Confirm Password:</label> <input type="password" name="confirmPassword">
		</li>
	</ul>
	<input type="submit" name="submit" value="Sign Up">
</form>