<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./user_details.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    16-Apr-02, 10:55
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script shows the user a user <form>. It can be used both for INSERTing a new user and for UPDATE-ing an existing user.
	// If the user is logged in, then it is an UPDATE; otherwise, an INSERT. The script also shows error messages above widgets that
	// contain erroneous data; errors are generated by 'user_validation.php'.


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// Initialize preferred display language:
	// (note that 'locales.inc.php' has to be included *after* the call to the 'start_session()' function)
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// Extract session variables (only necessary if register globals is OFF!):
	if (isset($_SESSION['errors']))
		$errors = $_SESSION['errors'];
	else
		$errors = array(); // initialize variable (in order to prevent 'Undefined index/variable...' messages)

	if (isset($_SESSION['formVars']))
		$formVars = $_SESSION['formVars'];
	else
		$formVars = array(); // initialize variable (in order to prevent 'Undefined index/variable...' messages)

	// The current values of the session variables 'errors' and 'formVars' get stored in '$errors' or '$formVars', respectively. (either automatically if
	// register globals is ON, or explicitly if register globals is OFF [by uncommenting the code above]).
	// We need to clear these session variables here, since they would otherwise be there even if 'user_details.php' gets called with a different userID!
	// Note: though we clear the session variables, the current error message (or form variables) is still available to this script via '$errors' (or '$formVars', respectively).
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// Set the '$userID' variable:
	if (isset($_REQUEST['userID'])) // for normal users NOT being logged in -OR- for the admin:
		$userID = $_REQUEST['userID'];
	else
		$userID = NULL; // '$userID = ""' wouldn't be correct here, since then any later 'isset($userID)' statement would resolve to true!

	if (isset($_SESSION['loginEmail']) && ($loginEmail != $adminLoginEmail)) // a normal user IS logged in ('$adminLoginEmail' is specified in 'ini.inc.php')
		// Check this user matches the userID (viewing and modifying user account details is only allowed to the admin)
		if ($userID != getUserID($loginEmail)) // (function 'getUserID()' is defined in 'include.inc.php')
		{
			// save an error message:
			$HeaderString = "<b><span class=\"warning\">You can only edit your own user data!</span></b>";

			// Write back session variables:
			saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
	
			$userID = getUserID($loginEmail); // re-establish the user's correct user_id
		}

	// --------------------------------------------------------------------

	// A user must be logged in in order to call 'user_details.php' WITH the 'userID' parameter:
	if (!isset($_SESSION['loginEmail']) && ($userID != 0))
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You must login to view your user account details!</span></b>";

		// save the URL of the currently displayed page:
		$referer = $_SERVER['HTTP_REFERER'];

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("referer", $referer);

		header("Location: user_login.php");
		exit;
	}

	// --------------------------------------------------------------------

	// Check if the logged-in user is allowed to modify his account details:
	if (isset($_SESSION['loginEmail']) AND ($userID != 0) AND isset($_SESSION['user_permissions']) AND !ereg("allow_modify_options", $_SESSION['user_permissions'])) // if a user is logged in but the 'user_permissions' session variable does NOT contain 'allow_modify_options'...
	{
		// save an error message:
		$HeaderString = "<b><span class=\"warning\">You have no permission to modify your user account details!</span></b>";

		// Write back session variables:
		saveSessionVariable("HeaderString", $HeaderString); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Redirect the browser back to the main page
		header("Location: index.php");
		exit;
	}

	// --------------------------------------------------------------------

	// Prepare meaningful instructions for UPDATE or INSERT:
	if (!isset($_SESSION['HeaderString'])) // if there's no stored message available
	{
		if (empty($errors)) // provide one of the default messages:
		{
			if (isset($_SESSION['loginEmail']) && isset($userID) && !empty($userID)) // -> the user is logged in and views a user entry
				$HeaderString = "Please amend your details below as required. Fields shown in <b>bold</b> are mandatory.";
			else // -> the user is NOT logged in (OR: the admin is logged in and wants to add a new user, by calling 'user_details.php' w/o any 'userID')
			{
				if ((!isset($_SESSION['loginEmail']) && ($addNewUsers == "everyone") && ($userID == "")) | (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail) && ($userID == "")))
					$HeaderString = "Add a new user. Fields shown in <b>bold</b> are mandatory.";
				else // ask a user to submit its user details for approval by the database admin:
					$HeaderString = "Please fill in the details below to join. Fields shown in <b>bold</b> are mandatory.";
			}
		}
		else // -> there were errors validating the user's details
			$HeaderString = "<b><span class=\"warning\">There were validation errors regarding the details you entered. Please check the comments above the respective fields:</span></b>";
	}
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// Extract the view type requested by the user (either 'Mobile', 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// Is the user logged in and were there no errors from a previous validation? If so, look up the user for editing:
	if (isset($_SESSION['loginEmail']) && empty($errors) && isset($userID) && !empty($userID))
	{
		// CONSTRUCT SQL QUERY:
		$query = "SELECT * FROM $tableUsers WHERE user_id = " . quote_smart($userID);

		// --------------------------------------------------------------------

		// (3a) RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		// (3b) EXTRACT results:
		$row = mysql_fetch_array($result); //fetch the current row into the array $row

		// If the admin is logged in AND the displayed user data are NOT his own, we overwrite the default header message:
		// (Since the admin is allowed to view and edit account data from other users, we have to provide a dynamic header message in that case)
		if (($loginEmail == $adminLoginEmail) && ($userID != getUserID($loginEmail))) // ('$adminLoginEmail' is specified in 'ini.inc.php')
			if (!isset($_SESSION['HeaderString']))
				$HeaderString = "Edit account details for <b>" . encodeHTML($row["first_name"]) . " " . encodeHTML($row["last_name"]) . " (" . $row["email"] . ")</b>:";
	}

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- User Details", "noindex,nofollow", "User details required for use of the " . encodeHTML($officialDatabaseName), "\n\t<meta http-equiv=\"expires\" content=\"0\">", false, "", $viewType, array());
	showPageHeader($HeaderString);

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	if (isset($_SESSION['loginEmail']) && empty($errors) && isset($userID) && !empty($userID))
	{
		// Reset the '$formVars' variable (since we're loading from the user table):
		$formVars = array();

		// Reset the '$errors' variable:
		$errors = array();

		// Load all the form variables with user data:
		$formVars["firstName"] = $row["first_name"];
		$formVars["lastName"] = $row["last_name"];
		$formVars["title"] = $row["title"];
		$formVars["institution"] = $row["institution"];
		$formVars["abbrevInstitution"] = $row["abbrev_institution"];
		$formVars["corporateInstitution"] = $row["corporate_institution"];
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

		if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // if the admin is logged in
		{
			$formVars["keywords"] = $row["keywords"];
			$formVars["notes"] = $row["notes"];
			$formVars["marked"] = $row["marked"];
		}

		$formVars["language"] = $row["language"];
	}
	elseif (empty($errors) && (!isset($userID) OR ($userID == ""))) // no userID specified
	{
		// Reset the '$formVars' variable:
		$formVars = array();

		// Reset the '$errors' variable:
		$errors = array();

		// Set all form variables to "" (in order to prevent 'Undefined variable...' messages):
		$formVars["firstName"] = "";
		$formVars["lastName"] = "";
		$formVars["title"] = "";
		$formVars["institution"] = "";
		$formVars["abbrevInstitution"] = "";
		$formVars["corporateInstitution"] = "";
		$formVars["address1"] = "";
		$formVars["address2"] = "";
		$formVars["address3"] = "";
		$formVars["zipCode"] = "";
		$formVars["city"] = "";
		$formVars["state"] = "";
		$formVars["country"] = "";
		$formVars["phone"] = "";
		$formVars["email"] = "";
		$formVars["url"] = "";

		if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail)) // if the admin is logged in
		{
			$formVars["keywords"] = "";
			$formVars["notes"] = "";
			$formVars["marked"] = "no";
		}

		$formVars["language"] = "en";
	}

	// Start <form> and <table> holding all the form elements:
?>

<form method="POST" action="user_validation.php">
<input type="hidden" name="userID" value="<?php echo encodeHTML($userID) ?>">
<input type="hidden" name="email" value="<?php echo encodeHTML($formVars["email"]) ?>">
<table align="center" border="0" cellpadding="0" cellspacing="10" width="95%" summary="This table holds a form with user details">
<tr>
	<td align="left" width="169">Title:</td>
	<td>
		<select name="title">
			<option <?php if ($formVars["title"]=="Mr") echo "selected"; ?>>Mr</option>
			<option <?php if ($formVars["title"]=="Mrs") echo "selected"; ?>>Mrs</option>
			<option <?php if ($formVars["title"]=="Ms") echo "selected"; ?>>Ms</option>
			<option <?php if ($formVars["title"]=="Dr") echo "selected"; ?>>Dr</option>
		</select>
		<br>
	</td>
</tr>
<tr>
	<td align="left"><b>First Name:</b></td>
	<td><?php echo fieldError("firstName", $errors); ?>

		<input type="text" name="firstName" value="<?php echo encodeHTML($formVars["firstName"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left"><b>Last Name:</b></td>
	<td><?php echo fieldError("lastName", $errors); ?>

		<input type="text" name="lastName" value="<?php echo encodeHTML($formVars["lastName"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Institution:</td>
	<td><?php echo fieldError("institution", $errors); ?>

		<input type="text" name="institution" value="<?php echo encodeHTML($formVars["institution"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left"><b>Institutional Abbreviation:</b></td>
	<td><?php echo fieldError("abbrevInstitution", $errors); ?>

		<input type="text" name="abbrevInstitution" value="<?php echo encodeHTML($formVars["abbrevInstitution"]); ?>" size="12">
	</td>
</tr>
<tr>
	<td align="left">Corporate Institution:</td>
	<td><?php echo fieldError("corporateInstitution", $errors); ?>

		<input type="text" name="corporateInstitution" value="<?php echo encodeHTML($formVars["corporateInstitution"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Work Address:</td>
	<td><?php echo fieldError("address1", $errors); ?>

		<input type="text" name="address1" value="<?php echo encodeHTML($formVars["address1"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td><?php echo fieldError("address2", $errors); ?>

		<input type="text" name="address2" value="<?php echo encodeHTML($formVars["address2"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left"></td>
	<td><?php echo fieldError("address3", $errors); ?>

		<input type="text" name="address3" value="<?php echo encodeHTML($formVars["address3"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Zip Code:</td>
	<td><?php echo fieldError("zipCode", $errors); ?>

		<input type="text" name="zipCode" value="<?php echo encodeHTML($formVars["zipCode"]); ?>" size="12">
	</td>
</tr>
<tr>
	<td align="left">City:</td>
	<td><?php echo fieldError("city", $errors); ?>

		<input type="text" name="city" value="<?php echo encodeHTML($formVars["city"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">State:</td>
	<td><?php echo fieldError("state", $errors); ?>

		<input type="text" name="state" value="<?php echo encodeHTML($formVars["state"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Country:</td>
	<td><?php echo fieldError("country", $errors); ?>

		<input type="text" name="country" value="<?php echo encodeHTML($formVars["country"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Phone:</td>
	<td><?php echo fieldError("phone", $errors); ?>

		<input type="text" name="phone" value="<?php echo encodeHTML($formVars["phone"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">URL:</td>
	<td><?php echo fieldError("url", $errors); ?>

		<input type="text" name="url" value="<?php echo encodeHTML($formVars["url"]); ?>" size="50">
	</td>
</tr>
<?php

	// if the admin is logged in, we'll show additional fields:
	if (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail))
	{
		if ($formVars["marked"] == "yes")
		{
			$markedRadioYesChecked = "checked";
			$markedRadioNoChecked = "";
		}
		else // $formVars["marked"] == "no"
		{
			$markedRadioYesChecked = "";
			$markedRadioNoChecked = "checked";
		}
?>
<tr>
	<td align="left">Keywords:</td>
	<td><?php echo fieldError("keywords", $errors); ?>

		<input type="text" name="keywords" value="<?php echo encodeHTML($formVars["keywords"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Notes:</td>
	<td><?php echo fieldError("notes", $errors); ?>

		<input type="text" name="notes" value="<?php echo encodeHTML($formVars["notes"]); ?>" size="50">
	</td>
</tr>
<tr>
	<td align="left">Marked:</td>
	<td><?php echo fieldError("marked", $errors); ?>

		<input type="radio" name="marked" value="yes"<?php echo $markedRadioYesChecked; ?>>&nbsp;&nbsp;yes&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="marked" value="no"<?php echo $markedRadioNoChecked; ?>>&nbsp;&nbsp;no
	</td>
</tr>
<?php
	}

	// Only show the username/email and password widgets to new users (or the admin, since he's allowed to call 'user_details.php' w/o any 'userID' when logged in):
	if (!isset($_SESSION['loginEmail']) | (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail) && ($userID == "")))
	{
?>
<tr>
	<td align="left"><b>Email:</b></td>
	<td><?php echo fieldError("email", $errors); ?>

		<input type="text" name="email" value="<?php echo encodeHTML($formVars["email"]); ?>" size="30">
	</td>
</tr>
<tr>
	<td align="left"><b>Password:</b></td>
	<td><?php echo fieldError("loginPassword", $errors); ?>

		<input type="password" name="loginPassword" value="" size="30">
	</td>
</tr>
<tr>
	<td align="left"><b>Verify Password:</b></td>
	<td><?php echo fieldError("loginPasswordRetyped", $errors); ?>

		<input type="password" name="loginPasswordRetyped" value="" size="30">
	</td>
</tr>
<?php
	}

	// if a user is logged in, we also show the password field (but with a different label text) so that the user is able to change his password later on:
	// (just keep the password field empty, if you don't want to change your password)
	elseif (isset($_SESSION['loginEmail']) && isset($userID))
	{
?>
<tr>
	<td align="left">New Password:</td>
	<td><?php echo fieldError("loginPassword", $errors); ?>

		<input type="password" name="loginPassword" value="" size="30">
	</td>
</tr>
<tr>
	<td align="left">Verify New Password:</td>
	<td><?php echo fieldError("loginPasswordRetyped", $errors); ?>

		<input type="password" name="loginPasswordRetyped" value="" size="30">
	</td>
</tr>
<?php
	}
?>
<tr>
	<td align="left"></td>
	<td>
<?php

	// The submit button reads 'Add' if an authorized user uses 'user_details.php' to add a new user (-> 'userID' is empty!)
	// This should make it more clear that submitting the form is going to add a new user without any further approval!
	// INSERTs are allowed to:
	//         1. EVERYONE who's not logged in (but ONLY if variable '$addNewUsers' in 'ini.inc.php' is set to "everyone"!)
	//            (Note that this feature is actually only meant to add the very first user to the users table.
	//             After you've done so, it is highly recommended to change the value of '$addNewUsers' to 'admin'!)
	//   -or-  2. the ADMIN only (if variable '$addNewUsers' in 'ini.inc.php' is set to "admin")
	if ((!isset($_SESSION['loginEmail']) && ($addNewUsers == "everyone") && ($userID == "")) | (isset($_SESSION['loginEmail']) && ($loginEmail == $adminLoginEmail) && ($userID == "")))
	{
?>
		<input type="submit" value="Add User">
<?php
	}
	else // ...otherwise the submit button reads (guess what) 'Submit' (i.e., solely an email will be sent to the admin for further approval):
	{
?>
		<input type="submit" value="Submit">
<?php
	}
?>
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
	// call the 'showPageFooter()' and 'displayHTMLfoot()' functions (which are defined in 'footer.inc.php')
	showPageFooter($HeaderString);

	displayHTMLfoot();

	// --------------------------------------------------------------------
?>
