<?php
	// This script shows the user a user <form>. It can be used both for INSERTing a new user and for UPDATE-ing an existing user.
	// If the user is logged in, then it is an UPDATE; otherwise, an INSERT. The script also shows error messages above widgets that
	// contain erroneous data; errors are generated by user_validation.php

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// Incorporate some include files:
	include 'db.inc'; // 'db.inc' is included to hide username and password
	include 'header.inc'; // include header
	include 'footer.inc'; // include footer
	include 'include.inc'; // include common functions
	include "ini.inc.php"; // include common variables

	// --------------------------------------------------------------------

	// Connect to a session
	session_start();
	
	// CAUTION: Doesn't work with 'register_globals = OFF' yet!!

//	// Read session variable (only necessary if register globals is OFF!)
//	$errors = $HTTP_SESSION_VARS['errors'];

	// --------------------------------------------------------------------

	if (session_is_registered("loginEmail") && ($loginEmail != $adminLoginEmail)) // ('$adminLoginEmail' is specified in 'ini.inc.php')
		// Check this user matches the userID (viewing and modifying user account details is only allowed to the admin)
		if ($userID != getUserID($loginEmail, NULL)) // (function 'getUserID()' is defined in 'include.inc')
		{
			session_register("HeaderString"); // save an error message
			$HeaderString = "<b><span class=\"warning\">You can only edit your own user data!</span></b>";
	
			$userID = getUserID($loginEmail, NULL); // re-establish the user's correct user_id
		}
	// CAUTION: currently, there will be an error, when admin is logged in and calls 'user_details.php' w/o any params
	//          (userID is not defined but SQL Query gets executed!)

	// --------------------------------------------------------------------

	// Prepare meaningful instructions for UPDATE or INSERT:
	if (!session_is_registered("HeaderString")) // if there's no stored message available
//		if (empty($HeaderString)) // and if there wasn't any message generated by the code above
			if (empty($errors)) // provide one of the default messages:
				if (session_is_registered("loginEmail") && isset($userID)) // -> the user is logged in and views a user entry
					$HeaderString = "Please amend your details below as required. Fields shown in <b>bold</b> are mandatory.";
				else // -> the user is NOT logged in (or: the admin is logged in and wants to add a new user)
					$HeaderString = "Please fill in the details below to join. Fields shown in <b>bold</b> are mandatory.";
			else // -> there were errors validating the user's details
				$HeaderString = "There were validation errors regarding the details you entered. Please check the comments above the respective fields.";
	else
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

//	if (session_is_registered("errors"))
//		// Read session variable (only necessary if register globals is OFF!)
//		$errors = $HTTP_SESSION_VARS['errors'];

	// Is the user logged in and were there no errors from a previous validation? If so, look up the user for editing:
	if (session_is_registered("loginEmail") && empty($errors) && isset($userID))
	{
		// CONSTRUCT SQL QUERY:
		$query = "SELECT * FROM users WHERE user_id = " . $userID;

		// --------------------------------------------------------------------
	
		// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY HEADER & RESULTS, (5) CLOSE CONNECTION
	
		// (1) OPEN the database connection:
		//      (variables are set by include file 'db.inc'!)
		if (!($connection = @ mysql_connect($hostName, $username, $password)))
			showErrorMsg("The following error occurred while trying to connect to the host:", "");
	
		// (2) SELECT the database:
		//      (variables are set by include file 'db.inc'!)
		if (!(mysql_select_db($databaseName, $connection)))
			showErrorMsg("The following error occurred while trying to connect to the database:", "");
	
		// (3a) RUN the query on the database through the connection:
		if (!($result = @ mysql_query($query, $connection)))
			showErrorMsg("Your query:\n<br>\n<br>\n<code>$query</code>\n<br>\n<br>\n caused the following error:", "");
	
		// (3b) EXTRACT results:
		$row = mysql_fetch_array($result); //fetch the current row into the array $row

		// If the admin is logged in AND the displayed user data are NOT his own, we overwrite the default header message:
		// (Since the admin is allowed to view and edit account data from other users, we have to provide a dynamic header message in that case)
		if (($loginEmail == $adminLoginEmail) && ($userID != getUserID($loginEmail, $connection))) // ('$adminLoginEmail' is specified in 'ini.inc.php')
			if (!session_is_registered("HeaderString"))
				$HeaderString = "Edit account details for <b>" . htmlentities($row["first_name"]) . " " . htmlentities($row["last_name"]) . " (" . $row["email"] . ")</b>:";
	}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead("IP&Ouml; Literature Database -- User Details", "noindex,nofollow", "User details required for use of the IP&Ouml; Literature Database", "\n\t<meta http-equiv=\"expires\" content=\"0\">", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks);

	if (session_is_registered("loginEmail") && empty($errors) && isset($userID))
	{
		// (5) CLOSE the database connection:
		if (!(mysql_close($connection)))
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");
	
		// --------------------------------------------------------------------

		// Reset the '$formVars' variable (since we're loading from the user table):
		$formVars = array();

		// Reset the '$errors' variable:
		$errors = array();

		// Load all the form variables with user data:
//		$formVars["user"] = $row["user"];
		$formVars["firstName"] = $row["first_name"];
		$formVars["lastName"] = $row["last_name"];
		$formVars["title"] = $row["title"];
		$formVars["institution"] = $row["institution"];
		$formVars["abbrevInstitution"] = $row["abbrev_institution"];
		$formVars["corporateInstitution"] = $row["corporate_institution"];
//		$formVars["address"] = $row["address"];
		$formVars["address1"] = $row["address_line_1"];
		$formVars["address2"] = $row["address_line_2"];
		$formVars["address3"] = $row["address_line_3"];
		$formVars["zipCode"] = $row["zip_code"];
		$formVars["city"] = $row["city"];
		$formVars["state"] = $row["state"];
		$formVars["country"] = $row["country"];
		$formVars["phone"] = $row["phone"];
		$formVars["email"] = $row["email"];
		$formVars["url"] = $row["url"];
//		$formVars["keywords"] = $row["keywords"];
//		$formVars["notes"] = $row["notes"];
//		$formVars["lastLogin"] = $row["last_login"];
//		$formVars["logins"] = $row["logins"];
//		$formVars["language"] = $row["language"];
//		$formVars["records"] = $row["records"];
//		$formVars["queries"] = $row["queries"];
//		$formVars["serial"] = $row["serial"];
//		$formVars["marked"] = $row["marked"];
	}

	// Start <form> and <table> holding all the form elements:
?>

<form method="POST" action="user_validation.php">
<input type="hidden" name="userID" value="<? echo $userID ?>">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds a form with user details">
<tr>
	<td align="left" width="169">Title:</td>
	<td>
		<select name="title">
			<option <? if ($formVars["title"]=="Mr") echo "selected"; ?>>Mr</option>
			<option <? if ($formVars["title"]=="Mrs") echo "selected"; ?>>Mrs</option>
			<option <? if ($formVars["title"]=="Ms") echo "selected"; ?>>Ms</option>
			<option <? if ($formVars["title"]=="Dr") echo "selected"; ?>>Dr</option>
		</select>
		<br>
	</td>
</tr>
<tr>
	<td align="left"><b>First Name:</b></td>
	<td><? echo fieldError("firstName", $errors); ?>

		<input type="text" name="firstName" value="<? echo $formVars["firstName"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left"><b>Last Name:</b></td>
	<td><? echo fieldError("lastName", $errors); ?>

		<input type="text" name="lastName" value="<? echo $formVars["lastName"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Institution:</td>
	<td><? echo fieldError("institution", $errors); ?>

		<input type="text" name="institution" value="<? echo $formVars["institution"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left"><b>Institutional Abbreviation:</b></td>
	<td><? echo fieldError("abbrevInstitution", $errors); ?>

		<input type="text" name="abbrevInstitution" value="<? echo $formVars["abbrevInstitution"]; ?>" size="12">
	</td>
</tr>
<tr>
	<td align="left">Corporate Institution:</td>
	<td><? echo fieldError("corporateInstitution", $errors); ?>

		<input type="text" name="corporateInstitution" value="<? echo $formVars["corporateInstitution"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Work Address:</td>
	<td><? echo fieldError("address1", $errors); ?>

		<input type="text" name="address1" value="<? echo $formVars["address1"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td><? echo fieldError("address2", $errors); ?>

		<input type="text" name="address2" value="<? echo $formVars["address2"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td><? echo fieldError("address3", $errors); ?>

		<input type="text" name="address3" value="<? echo $formVars["address3"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Zip Code:</td>
	<td><? echo fieldError("zipCode", $errors); ?>

		<input type="text" name="zipCode" value="<? echo $formVars["zipCode"]; ?>" size="12">
	</td>
</tr>
<tr>
	<td align="left">City:</td>
	<td><? echo fieldError("city", $errors); ?>

		<input type="text" name="city" value="<? echo $formVars["city"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">State:</td>
	<td><? echo fieldError("state", $errors); ?>

		<input type="text" name="state" value="<? echo $formVars["state"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Country:</td>
	<td><? echo fieldError("country", $errors); ?>

		<input type="text" name="country" value="<? echo $formVars["country"]; ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Phone:</td>
	<td><? echo fieldError("phone", $errors); ?>

		<input type="text" name="phone" value="<? echo $formVars["phone"]; ?>" size="30">
	</td>
</tr>
<tr>
	<td align="left">URL:</td>
	<td><? echo fieldError("url", $errors); ?>

		<input type="text" name="url" value="<? echo $formVars["url"]; ?>" size="30">
	</td>
</tr>
<?php
// Only show the username/email and password widgets to new users (or the admin, since he's allowed to call 'user_details.php' w/o any 'userID' when logged in):
	if (!session_is_registered("loginEmail") | (session_is_registered("loginEmail") && ($loginEmail == $adminLoginEmail) && !isset($userID)))
	{
?>
<tr>
	<td align="left"><b>Email:</b></td>
	<td><? echo fieldError("email", $errors); ?>

		<input type="text" name="email" value="<? echo $formVars["email"]; ?>" size="30">
	</td>
</tr>
<tr>
	<td align="left"><b>Password:</b></td>
	<td><? echo fieldError("loginPassword", $errors); ?>

		<input type="password" name="loginPassword" value="<? echo $formVars["loginPassword"]; ?>" size="30">
	</td>
</tr>
<?php
	}
?>
<tr>
	<td align="left"></td>
	<td>
		<input type="submit" value="Submit">
	</td>
</tr>
</table>
</form><?php

	// --------------------------------------------------------------------

	// SHOW ERROR IN RED:
	function fieldError($fieldName, $errors)
	{
		if (isset($errors[$fieldName]))
			echo "\n\t\t<b><span class=\"warning\">" . $errors[$fieldName] . "</span></b>\n\t\t<br>";
	}

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter("");

	// --------------------------------------------------------------------
?>
</body>
</html>
