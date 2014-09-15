<?php

require_once('includes/header.php');

$result = mysqli_query($mysqli, 'SELECT * FROM m_users ORDER BY created_at DESC');
while ($row = mysqli_fetch_assoc($result)) {
	if ($row['email']) {
		echo "$row[id] ($row[created_at]): $row[name] &lt;$row[email]&gt;<br>";
	}
	else {
		echo "$row[id] ($row[created_at]): $row[name]<br>";
	}
}