<?php
	// This script manages the login process. It should only be called when the user is not logged in.
	// If the user is logged in, it will redirect back to the calling page.
	// If the user is not logged in, it will show a login <form>

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include 'header.inc'; // include header
	include 'footer.inc'; // include footer
	include 'include.inc'; // include common functions

	// --------------------------------------------------------------------

	// Initialize the session
	session_start();

	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

	// --------------------------------------------------------------------

	$referer = $_REQUEST['referer']; // get the referring URL (if any)
	if (empty($referer))
		$referer = $HTTP_REFERER;

	if (isset($HTTP_POST_VARS["loginEmail"]))
		$loginEmail = $HTTP_POST_VARS["loginEmail"];
//		$loginEmail = clean($HTTP_POST_VARS["loginEmail"], 30); // using the clean function would be secure!

	if (isset($HTTP_POST_VARS["loginPassword"]))
		$loginPassword = $HTTP_POST_VARS["loginPassword"];
//		$loginPassword = clean($HTTP_POST_VARS["loginPassword"], 8); // using the clean function would be secure!

	// Check if the user is already logged in
	if (session_is_registered("loginEmail"))
	{
		if (!ereg("error\.php\?.+|user_login\.php$", $referer))
			header("Location: $referer"); // redirect the user to the calling page
		else
			header("Location: index.php"); // back to main page

		// a more smart solution would be something like the code below:
		// (but '$referer' isn't registered yet across all pages!)

//		// If they are, then just bounce them back where they came from
//		if (session_is_registered("referer"))
//		{  
//			// Delete the redirection session variable
//			session_unregister("referer");
//			
//			// Then, use it to redirect to the calling page
//			header("Location: $referer");
//			exit;
//		}
//		else
//			header("Location: index.php"); // back to main page
//			exit;
	}

	// Have they provided none or only one of the two required values: email address AND password?
	if ((empty($HTTP_POST_VARS["loginEmail"]) && !empty($HTTP_POST_VARS["loginPassword"])) || (!empty($HTTP_POST_VARS["loginEmail"]) && empty($HTTP_POST_VARS["loginPassword"])))
	{		 
		// Register an error message
		session_register("HeaderString");
		$HeaderString = "<b><span class=\"warning\">In order to login you must supply both, email address and password!</span></b>";
	}

	// Have they not provided an email address/password, or was there an error?
	if (!isset($loginEmail) || !isset($loginPassword) || session_is_registered("HeaderString"))
		login_page();
	else
		// They have provided a login. Is it valid?
		check_login($loginEmail, $loginPassword);

	// --------------------------------------------------------------------

	function check_login($loginEmail, $loginPassword)
	{
		global $referer;
		global $username;
		global $password;
		global $hostName;
		global $databaseName;
		global $HeaderString;
		global $loginUserID;
		global $loginFirstName;
		global $loginLastName;
		global $abbrevInstitution;

		// Get the two character salt from the email address collected from the challenge
		$salt = substr($loginEmail, 0, 2); 

		// Encrypt the loginPassword collected from the challenge (so that we can compare it to the encrypted passwords that stored in the 'auth' table)
		$crypted_password = crypt($loginPassword, $salt);

		// CONSTRUCT SQL QUERY:
		$query = "SELECT user_id FROM auth WHERE email = '$loginEmail' AND password = '$crypted_password'";

		// -------------------

		// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (5) CLOSE CONNECTION

		// (1) OPEN the database connection:
		//      (variables are set by include file 'db.inc'!)
		if (!($connection = @ mysql_connect($hostName, $username, $password)))
			showErrorMsg("The following error occurred while trying to connect to the host:", "");

		// (2) SELECT the database:
		//      (variables are set by include file 'db.inc'!)
		if (!(mysql_select_db($databaseName, $connection)))
			showErrorMsg("The following error occurred while trying to connect to the database:", "");

		// (3) RUN the query on the database through the connection:
		if (!($result = @ mysql_query($query, $connection)))
			showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

		// (4) EXTRACT results:
		if (mysql_num_rows($result) == 1) // Interpret query result: Do we have exactly one row?
			{
				$foundUser = true; // then we have found the user
				$row = mysql_fetch_array($result); //fetch the one row into the array $row
			}
		else
			$foundUser = false;

		// (5) CLOSE the database connection:
		if (!(mysql_close($connection)))
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

		// -------------------

		if ($foundUser == true)
		{
			// Register the loginEmail to show the user is logged in
			session_register("loginEmail");
	
			// Clear any other session variables
			if (session_is_registered("errors"))
				// Delete the form errors session variable
				session_unregister("errors");

			if (session_is_registered("formVars"))
				// Delete the formVars session variable
				session_unregister("formVars");


			$userID = $row["user_id"]; // extract the user's userID from the last query

			// Now we need to get the user's first name and last name (e.g., in order to display them within the login welcome message)
			$query = "SELECT user_id, first_name, last_name, abbrev_institution FROM users WHERE user_id = " . $userID; // CONSTRUCT SQL QUERY
	
			if (!($connection = @ mysql_connect($hostName, $username, $password))) // (1) OPEN the database connection (variables are set by include file 'db.inc'!)
				showErrorMsg("The following error occurred while trying to connect to the host:", "");

			if (!(mysql_select_db($databaseName, $connection))) // (2) SELECT the database (variables are set by include file 'db.inc'!)
				showErrorMsg("The following error occurred while trying to connect to the database:", "");

			if (!($result = @ mysql_query($query, $connection))) // (3) RUN the query on the database through the connection:
				showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");

			$row2 = mysql_fetch_array($result); // (4) EXTRACT results: fetch the one row into the array $row2

			if (!(mysql_close($connection))) // (5) CLOSE the database connection
				showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

			// Save the fetched user details to the session file:
			$loginUserID = $row2["user_id"];
			$loginFirstName = $row2["first_name"];
			$loginLastName = $row2["last_name"];
			$abbrevInstitution = $row2["abbrev_institution"];
	
			session_register("loginUserID");
			session_register("loginFirstName");
			session_register("loginLastName");
			session_register("abbrevInstitution");


			header("Location: $referer"); // redirect the user to the calling page

			// a more smart solution would be something like the code below:
			// (but '$referer' isn't registered yet across all pages!)

//			// Do we need to redirect to a calling page?
//			if (session_is_registered("referer"))
//			{		 
//				// Delete the referer session variable
//				session_unregister("referer");
//	
//				// Then, use it to redirect
//				header("Location: $referer");
//				exit;
//			}
//			else // there's no referer available
//			{
//				header("Location: index.php"); // back to main page (or, alternatively, rout them to their user account page: "Location: user_details.php?userID=$userID")
//				exit;
//			}
		}
		else
		{
		// Ensure loginEmail is not registered, so the user is not logged in
			if (session_is_registered("loginEmail"))
				session_unregister("loginEmail");

			// Register an error message
			session_register("HeaderString");
			$HeaderString = "<b><span class=\"warning\">Login failed! You provided an incorrect email address or password.</span></b>";

			login_page();
		}				 
	}

	// --------------------------------------------------------------------

	// Function that shows the HTML <form> that is used to collect the email address and password
	function login_page()
	{
		global $HeaderString;
		global $loginWelcomeMsg;
		global $loginStatus;
		global $loginLinks;

		// Show login status (should be logged out!)
		showLogin(); // (function 'showLogin()' is defined in 'include.inc')

		// If there's no stored message available:
		if (!session_is_registered("HeaderString"))
			$HeaderString = "You need to login in order to make any changes to the database:"; // Provide the default welcome message
		else
			session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

		// Call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
		displayHTMLhead("IP&Ouml; Literature Database -- User Login", "index,follow", "User login page. You must be logged in to the IP&Ouml; Literature Database in order to add, edit or delete records", "", false, "");
		showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks);

		// Build the login form:
?>

<form method="POST" action="user_login.php">
<table align="center" border="0" cellpadding="2" cellspacing="5" width="95%" summary="This table holds a login form for the IP&Ouml; literature database">
	<tr>
		<td width="174" valign="bottom">
			<b>Email Address:</b>
		</td>
		<td valign="bottom">
			<input type="text" name="loginEmail" size="30">
		</td>
	</tr>
	<tr>
		<td valign="bottom">
			<b>Password:</b>
		</td>
		<td valign="bottom">
			<input type="password" name="loginPassword" size="30">
		</td>
	</tr>
	<tr>
		<td valign="bottom">
			&nbsp;
		</td>
		<td valign="bottom">
			<input type="submit" value="Login">
		</td>
	</tr>
</table>
</form><?php
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter("");

	// --------------------------------------------------------------------
?>
</body>
</html>
