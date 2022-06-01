<html>
<head>
	<title>Logging out...</title>
<?PHP
require __DIR__ . "/db_accessor.php";
require __DIR__ . "/session_manager.php";

session_start();

mmm_end_session();
redirect_to("/login.php");
die();
?>
</head>
<body>
<h2><u>Logging out</u></h2><br />
<h4><i>Please wait...</i></h4>
</body>
</html>
