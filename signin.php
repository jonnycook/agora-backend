<?php

require_once('includes/header.php');
require_once('includes/user.php');

if ($_POST['submit']) {
	$email = mysqli_real_escape_string($mysqli, $_POST['email']);
	$password = passHash($_POST['password']);
	$row = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT id FROM m_users WHERE email = '$email' && password = '$password'"));

	if ($row) {
		setUserId($row['id']);
		echo 'Signed in.';
	}
	else {
		echo 'No user.';
	}
	exit;
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
	</ul>
	<input type="submit" name="submit" value="Sign Up">
</form>