<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./user_options_modify.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    26-Oct-04, 20:57
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// This script validates user options selected within the form provided by 'user_options.php'.
	// If validation succeeds, it UPDATEs the corresponding table fields for that user and redirects to a receipt page;
	// if it fails, it creates error messages and these are later displayed by 'user_options.php'.
	// TODO: I18n


	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
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

	// Clear any errors that might have been found previously:
	$errors = array();

	// Write the (POST) form variables into an array:
	foreach($_POST as $varname => $value)
		$formVars[$varname] = $value;

	// Since checkbox form fields do only get included in the '$_POST' array if they were marked,
	// we have to add appropriate array elements for all checkboxes that weren't set:
	// (we deal with permission checkboxes separately below)
	if (!isset($formVars["export_cite_keys"]))
		$formVars["export_cite_keys"] = "no";

	if (!isset($formVars["autogenerate_cite_keys"]))
		$formVars["autogenerate_cite_keys"] = "no";

	if (!isset($formVars["prefer_autogenerated_cite_keys"]))
		$formVars["prefer_autogenerated_cite_keys"] = "no";

	if (!isset($formVars["use_custom_cite_key_format"]))
		$formVars["use_custom_cite_key_format"] = "no";

	// $formVars["use_custom_handling_of_nonascii_chars_in_cite_keys"] is handled (differently) below

	if (!isset($formVars["uniquify_duplicate_cite_keys"]))
		$formVars["uniquify_duplicate_cite_keys"] = "no";

	if (!isset($formVars["use_custom_text_citation_format"]))
		$formVars["use_custom_text_citation_format"] = "no";


	// --------------------------------------------------------------------

	// First of all, check if this script was called by something else than 'user_options.php':
	if (!preg_match("#/user_options\.php#i", $referer)) // variable '$referer' is globally defined in function 'start_session()' in 'include.inc.php'
	{
		// return an appropriate error message:
		$HeaderString = returnMsg($loc["Warning_InvalidCallToScript"] . " '" . scriptURL() . "'!", "warning", "strong", "HeaderString"); // functions 'returnMsg()' and 'scriptURL()' are defined in 'include.inc.php'

		header("Location: " . $referer); // redirect to calling page

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE
	connectToMySQLDatabase(); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// VALIDATE FORM DATA:

	// (Note: checking for missing/incorrect input of the language field isn't really necessary if a popup is used as input field -- as it is right now)
//	// Validate the language
//	if (empty($formVars["languageName"]))
//		// Language cannot be a null string
//		$errors["languageName"] = "The language field cannot be blank:";

	// Validate the number of records per page
	if (($_REQUEST['userID'] != 0) AND !preg_match("/^[1-9]+[0-9]*$/", $formVars["recordsPerPageNo"])) // this form element is disabled for anonymous users ('userID=0')
		$errors["recordsPerPageNo"] = "Please enter a number (positive integer greater than zero):";

	// Note: currently, the user must select at least one item within the type/style/format lists. Alternatively, we could grey out the corresponding interface elements
	//       if a user deselects all items. Or, hiding the corresponding interface elements *completely* would actually give the user the possibility to remove unwanted/unneeded "features"!

	// Validate the reference type selector
	if (empty($formVars["referenceTypeSelector"]))
		$errors["referenceTypeSelector"] = "You must choose at least one reference type:";

	// Validate the citation style selector
	if (empty($formVars["citationStyleSelector"]))
		$errors["citationStyleSelector"] = "You must choose at least one citation style:";

	// Validate the cite format selector
	if (empty($formVars["citationFormatSelector"]))
		$errors["citationFormatSelector"] = "You must choose at least one citation format:";

	// Validate the export format selector
	if (empty($formVars["exportFormatSelector"]))
		$errors["exportFormatSelector"] = "You must choose at least one export format:";

	// Validate the main fields selector
	if (($_REQUEST['userID'] != 0) AND empty($formVars["mainFieldsSelector"])) // this form element is disabled for anonymous users ('userID=0')
		$errors["mainFieldsSelector"] = "You must specify at least one field as \"main field\":";

	// --------------------------------------------------------------------

	// Now the script has finished the validation, check if there were any errors:
	if (count($errors) > 0)
	{
		// Write back session variables:
		saveSessionVariable("errors", $errors); // function 'saveSessionVariable()' is defined in 'include.inc.php'
		saveSessionVariable("formVars", $formVars);

		// There are errors. Relocate back to the client form:
		header("Location: user_options.php?userID=" . $_REQUEST['userID']); // 'userID' got included as hidden form tag by 'user_options.php'

		exit; // >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> !EXIT! <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<
	}

	// --------------------------------------------------------------------

	// If we made it here, then the data is considered valid!

	// CONSTRUCT SQL QUERY:
	// If a user is logged in and has submitted 'user_options.php' with a 'userID' parameter:
	// (while the admin has no restrictions, a normal user can only submit 'user_options.php' with his own 'userID' as parameter!)
	if (isset($_SESSION['loginEmail']) && ($_REQUEST['userID'] != "")) // -> perform an update:
	{
		if ($loginEmail != $adminLoginEmail) // if not admin logged in ('$adminLoginEmail' is specified in 'ini.inc.php')
			$userID = getUserID($loginEmail); // Get the 'user_id' using 'loginEmail' (function 'getUserID()' is defined in 'include.inc.php')
		else // if the admin is logged in he should be able to make any changes to account data/options of _other_ users...
			$userID = $_REQUEST['userID']; // ...in this case we accept 'userID' from the GET/POST request (it got included as hidden form tag by 'user_options.php')

		// UPDATE - construct queries to update the relevant table fields for this user

		// a) update the language field of the 'users' table:
		if ($userID != 0) // the 'languageName' form element is disabled for anonymous users ('userID=0'), and there isn't an entry with 'user_id=0' in table 'users'
			$queryArray[] = "UPDATE $tableUsers SET "
			              . "language = " . quote_smart($formVars["languageName"]) . " "
			              . "WHERE user_id = " . quote_smart($userID);


		if ($loginEmail == $adminLoginEmail) // if the admin is logged in
		{
			// b) update all entries for this user within the 'user_types' table:

			//     - first, get a list of IDs for all types within the 'user_types' table that are available and were enabled by the admin for the current user:
			$enabledUserTypesArray = getEnabledUserFormatsStylesTypes($userID, "type", "", true); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'

			$enabledUserTypesInSelectedTypesArray = array_intersect($enabledUserTypesArray, $formVars["referenceTypeSelector"]);

			$enabledUserTypesNOTInSelectedTypesArray = array_diff($enabledUserTypesArray, $formVars["referenceTypeSelector"]);

			$selectedTypesNOTInEnabledUserTypesArray = array_diff($formVars["referenceTypeSelector"], $enabledUserTypesArray);

			if (!empty($enabledUserTypesNOTInSelectedTypesArray))
			{
				// - remove types which do exist within the 'user_types' table but were deselected by the admin:
				$enabledUserTypesNOTInSelectedTypesString = implode("|", $enabledUserTypesNOTInSelectedTypesArray); // join array of type IDs using a pipe as separator

				$queryArray[] = "DELETE FROM $tableUserTypes "
				              . "WHERE user_id = " . quote_smart($userID) . " AND type_id RLIKE " . quote_smart("^(" . $enabledUserTypesNOTInSelectedTypesString . ")$");
			}

			if (!empty($selectedTypesNOTInEnabledUserTypesArray))
			{
				// - insert types that were selected by the admin but which do not yet exist within the 'user_types' table:
				$selectedTypesNOTInEnabledUserTypesString = implode("|", $selectedTypesNOTInEnabledUserTypesArray); // join array of type IDs using a pipe as separator

				$insertTypesQuery = "INSERT INTO $tableUserTypes VALUES ";

				foreach ($selectedTypesNOTInEnabledUserTypesArray as $newUserTypeID)
					$insertTypesQueryValues[] = "(NULL, " . quote_smart($newUserTypeID) . ", " . quote_smart($userID) . ", 'true')";

				$queryArray[] = $insertTypesQuery . implode(", ", $insertTypesQueryValues) . ";";
			}

			// ---------------------
			// c) update all entries for this user within the 'user_styles' table:

			//     - first, get a list of IDs for all styles within the 'user_styles' table that are available and were enabled by the admin for the current user:
			$enabledUserStylesArray = getEnabledUserFormatsStylesTypes($userID, "style", "", true); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'

			$enabledUserStylesInSelectedStylesArray = array_intersect($enabledUserStylesArray, $formVars["citationStyleSelector"]);

			$enabledUserStylesNOTInSelectedStylesArray = array_diff($enabledUserStylesArray, $formVars["citationStyleSelector"]);

			$selectedStylesNOTInEnabledUserStylesArray = array_diff($formVars["citationStyleSelector"], $enabledUserStylesArray);

			if (!empty($enabledUserStylesNOTInSelectedStylesArray))
			{
				// - remove styles which do exist within the 'user_styles' table but were deselected by the admin:
				$enabledUserStylesNOTInSelectedStylesString = implode("|", $enabledUserStylesNOTInSelectedStylesArray); // join array of style IDs using a pipe as separator

				$queryArray[] = "DELETE FROM $tableUserStyles "
				              . "WHERE user_id = " . quote_smart($userID) . " AND style_id RLIKE " . quote_smart("^(" . $enabledUserStylesNOTInSelectedStylesString . ")$");
			}

			if (!empty($selectedStylesNOTInEnabledUserStylesArray))
			{
				// - insert styles that were selected by the admin but which do not yet exist within the 'user_styles' table:
				$selectedStylesNOTInEnabledUserStylesString = implode("|", $selectedStylesNOTInEnabledUserStylesArray); // join array of style IDs using a pipe as separator

				$insertStylesQuery = "INSERT INTO $tableUserStyles VALUES ";

				foreach ($selectedStylesNOTInEnabledUserStylesArray as $newUserStyleID)
					$insertStylesQueryValues[] = "(NULL, " . quote_smart($newUserStyleID) . ", " . quote_smart($userID) . ", 'true')";

				$queryArray[] = $insertStylesQuery . implode(", ", $insertStylesQueryValues) . ";";
			}

			// ---------------------
			// d) update all cite entries for this user within the 'user_formats' table:

			//     - first, get a list of IDs for all cite formats within the 'user_formats' table that are available and were enabled by the admin for the current user:
			$enabledUserFormatsArray = getEnabledUserFormatsStylesTypes($userID, "format", "cite", true); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'

			$enabledUserFormatsInSelectedFormatsArray = array_intersect($enabledUserFormatsArray, $formVars["citationFormatSelector"]);

			$enabledUserFormatsNOTInSelectedFormatsArray = array_diff($enabledUserFormatsArray, $formVars["citationFormatSelector"]);

			$selectedFormatsNOTInEnabledUserFormatsArray = array_diff($formVars["citationFormatSelector"], $enabledUserFormatsArray);

			if (!empty($enabledUserFormatsNOTInSelectedFormatsArray))
			{
				// - remove cite formats which do exist within the 'user_formats' table but were deselected by the admin:
				$enabledUserFormatsNOTInSelectedFormatsString = implode("|", $enabledUserFormatsNOTInSelectedFormatsArray); // join array of format IDs using a pipe as separator

				$queryArray[] = "DELETE FROM $tableUserFormats "
				              . "WHERE user_id = " . quote_smart($userID) . " AND format_id RLIKE " . quote_smart("^(" . $enabledUserFormatsNOTInSelectedFormatsString . ")$");
			}

			if (!empty($selectedFormatsNOTInEnabledUserFormatsArray))
			{
				// - insert cite formats that were selected by the admin but which do not yet exist within the 'user_formats' table:
				$selectedFormatsNOTInEnabledUserFormatsString = implode("|", $selectedFormatsNOTInEnabledUserFormatsArray); // join array of format IDs using a pipe as separator

				$insertFormatsQuery = "INSERT INTO $tableUserFormats VALUES ";

				foreach ($selectedFormatsNOTInEnabledUserFormatsArray as $newUserFormatID)
					$insertFormatsQueryValues[] = "(NULL, " . quote_smart($newUserFormatID) . ", " . quote_smart($userID) . ", 'true')";

				$queryArray[] = $insertFormatsQuery . implode(", ", $insertFormatsQueryValues) . ";";
			}

			// ---------------------
			// e) update all export entries for this user within the 'user_formats' table:

			//     - first, get a list of IDs for all export formats within the 'user_formats' table that are available and were enabled by the admin for the current user:
			$enabledUserFormatsArray = getEnabledUserFormatsStylesTypes($userID, "format", "export", true); // function 'getEnabledUserFormatsStylesTypes()' is defined in 'include.inc.php'

			$enabledUserFormatsInSelectedFormatsArray = array_intersect($enabledUserFormatsArray, $formVars["exportFormatSelector"]);

			$enabledUserFormatsNOTInSelectedFormatsArray = array_diff($enabledUserFormatsArray, $formVars["exportFormatSelector"]);

			$selectedFormatsNOTInEnabledUserFormatsArray = array_diff($formVars["exportFormatSelector"], $enabledUserFormatsArray);

			if (!empty($enabledUserFormatsNOTInSelectedFormatsArray))
			{
				// - remove export formats which do exist within the 'user_formats' table but were deselected by the admin:
				$enabledUserFormatsNOTInSelectedFormatsString = implode("|", $enabledUserFormatsNOTInSelectedFormatsArray); // join array of format IDs using a pipe as separator

				$queryArray[] = "DELETE FROM $tableUserFormats "
				              . "WHERE user_id = " . quote_smart($userID) . " AND format_id RLIKE " . quote_smart("^(" . $enabledUserFormatsNOTInSelectedFormatsString . ")$");
			}

			if (!empty($selectedFormatsNOTInEnabledUserFormatsArray))
			{
				// - insert export formats that were selected by the admin but which do not yet exist within the 'user_formats' table:
				$selectedFormatsNOTInEnabledUserFormatsString = implode("|", $selectedFormatsNOTInEnabledUserFormatsArray); // join array of format IDs using a pipe as separator

				$insertFormatsQuery = "INSERT INTO $tableUserFormats VALUES ";

				foreach ($selectedFormatsNOTInEnabledUserFormatsArray as $newUserFormatID)
					$insertFormatsQueryValues[] = "(NULL, " . quote_smart($newUserFormatID) . ", " . quote_smart($userID) . ", 'true')";

				$queryArray[] = $insertFormatsQuery . implode(", ", $insertFormatsQueryValues) . ";";
			}

			// ---------------------
			// f) update all permission settings for this user within the 'user_permissions' table:

			// get all user permissions for the current user (as they were before submit of 'user_options.php'):
			$userPermissionsArray = getPermissions($userID, "user", false); // function 'getPermissions()' is defined in 'include.inc.php'

			// copy all array elements that deal with permission settings from the '$formVars' array to '$updatedUserPermissionsArray':
			// (note that, except hidden permission settings, only those permission settings were included in the '$formVars' array whose checkboxes were marked!)
			$updatedUserPermissionsArray = array();
			foreach($formVars as $itemKey => $itemValue)
				if (preg_match("/^allow/i", $itemKey))
					$updatedUserPermissionsArray[$itemKey] = $itemValue; // allow the particular feature ('$itemValue' will be 'yes' anyhow)

			// then, all permission settings that aren't contained within '$updatedUserPermissionsArray' must have been unchecked:
			// (note: this logic only works if all permission settings queried by function 'getPermissions()' are also made available by 'user_options.php' -- either as checkbox or as hidden form tag!) 
			foreach($userPermissionsArray as $permissionKey => $permissionValue)
				if (!isset($updatedUserPermissionsArray[$permissionKey]))
					$updatedUserPermissionsArray[$permissionKey] = 'no'; // disallow the particular feature

			// update all user permissions for the current user:
			$updateSucceeded = updateUserPermissions(array($userID), $updatedUserPermissionsArray); // function 'updateUserPermissions()' is defined in 'include.inc.php'
		}

		// ---------------------------------------------------------------

		else // if a normal user is logged in
		{
			// b) update all entries for this user within the 'user_types' table:
			$typeIDString = implode("|", $formVars["referenceTypeSelector"]); // join array of type IDs using a pipe as separator

			$queryArray[] = "UPDATE $tableUserTypes SET "
			              . "show_type = \"true\" "
			              . "WHERE user_id = " . quote_smart($userID) . " AND type_id RLIKE " . quote_smart("^(" . $typeIDString . ")$");

			$queryArray[] = "UPDATE $tableUserTypes SET "
			              . "show_type = \"false\" "
			              . "WHERE user_id = " . quote_smart($userID) . " AND type_id NOT RLIKE " . quote_smart("^(" . $typeIDString . ")$");

			// c) update all entries for this user within the 'user_styles' table:
			$styleIDString = implode("|", $formVars["citationStyleSelector"]); // join array of style IDs using a pipe as separator

			$queryArray[] = "UPDATE $tableUserStyles SET "
			              . "show_style = \"true\" "
			              . "WHERE user_id = " . quote_smart($userID) . " AND style_id RLIKE " . quote_smart("^(" . $styleIDString . ")$");

			$queryArray[] = "UPDATE $tableUserStyles SET "
			              . "show_style = \"false\" "
			              . "WHERE user_id = " . quote_smart($userID) . " AND style_id NOT RLIKE " . quote_smart("^(" . $styleIDString . ")$");

			// d) update all cite entries for this user within the 'user_formats' table:
			$citeFormatIDString = implode("|", $formVars["citationFormatSelector"]); // join array of format IDs using a pipe as separator

			$queryArray[] = "UPDATE $tableUserFormats SET "
			              . "show_format = \"true\" "
			              . "WHERE user_id = " . quote_smart($userID) . " AND format_id RLIKE " . quote_smart("^(" . $citeFormatIDString . ")$");

			$queryArray[] = "UPDATE $tableUserFormats SET "
			              . "show_format = \"false\" "
			              . "WHERE user_id = " . quote_smart($userID) . " AND format_id NOT RLIKE " . quote_smart("^(" . $citeFormatIDString . ")$");

			// e) update all export entries for this user within the 'user_formats' table:
			$exportFormatIDString = implode("|", $formVars["exportFormatSelector"]); // join array of format IDs using a pipe as separator

			$queryArray[] = "UPDATE $tableUserFormats SET "
			              . "show_format = \"true\" "
			              . "WHERE user_id = " . quote_smart($userID) . " AND format_id RLIKE " . quote_smart("^(" . $exportFormatIDString . ")$");

			$queryArray[] = "UPDATE $tableUserFormats SET "
			              . "show_format = \"false\" "
			              . "WHERE user_id = " . quote_smart($userID) . " AND format_id NOT RLIKE " . quote_smart("^(" . $exportFormatIDString . ")$") . " AND format_id NOT RLIKE " . quote_smart("^(" . $citeFormatIDString . ")$"); // we need to include '$citeFormatIDString' here, otherwise the user's selected cite formats would get deleted again
		}

		// ---------------------------------------------------------------

		// f) update the user's options in the 'user_options' table:
		if (!isset($formVars["use_custom_handling_of_nonascii_chars_in_cite_keys"]))
			$nonASCIICharsInCiteKeys = "NULL"; // use the site default given in '$handleNonASCIICharsInCiteKeysDefault' in 'ini.inc.php'
		else
			$nonASCIICharsInCiteKeys = quote_smart($formVars["nonascii_chars_in_cite_keys"]); // use the setting chosen by the user

		if ($userID != 0)
		{
			$recordsPerPage = $formVars["recordsPerPageNo"];
			$showAutoCompletions = $formVars["showAutoCompletionsRadio"];
			$mainFieldsString = implode(", ", $formVars["mainFieldsSelector"]); // join array of the user's preferred main fields using a comma (and whitespace) as separator
		}
		else // the 'recordsPerPageNo', 'showAutoCompletionsRadio' and 'mainFieldsSelector' form elements are disabled for anonymous users ('userID=0'), so we load the defaults:
		{
			$recordsPerPage = getDefaultNumberOfRecords(0); // function 'getDefaultNumberOfRecords()' is defined in 'include.inc.php'
			$showAutoCompletions = getPrefAutoCompletions(0); // function 'getPrefAutoCompletions()' is defined in 'include.inc.php'
			$mainFieldsString = implode(", ", getMainFields(0)); // function 'getMainFields()' is defined in 'include.inc.php'
		}

		// we account for the possibility that no entry in table 'user_options' exists for the current user
		// (in which case an entry will be added):

		// check if there's already an entry for the current user within the 'user_options' table:
		// CONSTRUCT SQL QUERY:
		$query = "SELECT option_id FROM $tableUserOptions WHERE user_id = " . quote_smart($userID);

		// RUN the query on the database through the connection:
		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

		if (mysql_num_rows($result) == 1) // if there's already an existing user_data entry, we perform an UPDATE action:
			$queryArray[] = "UPDATE $tableUserOptions SET "
			              . "export_cite_keys = " . quote_smart($formVars["export_cite_keys"])
			              . ", autogenerate_cite_keys = " . quote_smart($formVars["autogenerate_cite_keys"])
			              . ", prefer_autogenerated_cite_keys = " . quote_smart($formVars["prefer_autogenerated_cite_keys"])
			              . ", use_custom_cite_key_format = " . quote_smart($formVars["use_custom_cite_key_format"])
			              . ", cite_key_format = " . quote_smart($formVars["cite_key_format"])
			              . ", uniquify_duplicate_cite_keys = " . quote_smart($formVars["uniquify_duplicate_cite_keys"])
			              . ", nonascii_chars_in_cite_keys = " . $nonASCIICharsInCiteKeys // already quote_smart
			              . ", use_custom_text_citation_format = " . quote_smart($formVars["use_custom_text_citation_format"])
			              . ", text_citation_format = " . quote_smart($formVars["text_citation_format"])
			              . ", records_per_page = " . quote_smart($recordsPerPage)
			              . ", show_auto_completions = " . quote_smart($showAutoCompletions)
			              . ", main_fields = " . quote_smart($mainFieldsString)
			              . " WHERE user_id = " . quote_smart($userID);

		else // otherwise we perform an INSERT action:
			$queryArray[] = "INSERT INTO $tableUserOptions SET "
			              . "export_cite_keys = " . quote_smart($formVars["export_cite_keys"])
			              . ", autogenerate_cite_keys = " . quote_smart($formVars["autogenerate_cite_keys"])
			              . ", prefer_autogenerated_cite_keys = " . quote_smart($formVars["prefer_autogenerated_cite_keys"])
			              . ", use_custom_cite_key_format = " . quote_smart($formVars["use_custom_cite_key_format"])
			              . ", cite_key_format = " . quote_smart($formVars["cite_key_format"])
			              . ", uniquify_duplicate_cite_keys = " . quote_smart($formVars["uniquify_duplicate_cite_keys"])
			              . ", nonascii_chars_in_cite_keys = " . $nonASCIICharsInCiteKeys // already quote_smart
			              . ", use_custom_text_citation_format = " . quote_smart($formVars["use_custom_text_citation_format"])
			              . ", text_citation_format = " . quote_smart($formVars["text_citation_format"])
			              . ", records_per_page = " . quote_smart($recordsPerPage)
			              . ", show_auto_completions = " . quote_smart($showAutoCompletions)
			              . ", main_fields = " . quote_smart($mainFieldsString)
			              . ", user_id = " . quote_smart($userID)
			              . ", option_id = NULL"; // inserting 'NULL' into an auto_increment PRIMARY KEY attribute allocates the next available key value
	}

	// --------------------------------------------------------------------

	// (3) RUN the queries on the database through the connection:
	foreach($queryArray as $query)
		$result = queryMySQLDatabase($query); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	// ----------------------------------------------

	// we'll only update the appropriate session variables if either a normal user is logged in -OR- the admin is logged in AND the updated user data are his own:
	if (($loginEmail != $adminLoginEmail) | (($loginEmail == $adminLoginEmail) && ($userID == getUserID($loginEmail))))
	{
		// Write back session variables:
		saveSessionVariable("userLanguage", $formVars["languageName"]); // function 'saveSessionVariable()' is defined in 'include.inc.php'

		// Note: the user's types/styles/formats will be written to their corresponding session variables in function 'getVisibleUserFormatsStylesTypes()'
		//       which will be called by the following receipt page ('user_receipt.php') anyhow, so we won't call the function here...
		//       The same is true for the user's preferred number of records per page, the user's pref setting to show auto-completions and for the
		//       list of "main fields" which will be saved to session variables from within 'user_receipt.php' thru functions 'getMainFields()',
		//       'getDefaultNumberOfRecords()' and 'getPrefAutoCompletions()', respectively.
	}

	// Clear the 'errors' and 'formVars' session variables so a future <form> is blank:
	deleteSessionVariable("errors"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	deleteSessionVariable("formVars");

	// ----------------------------------------------

	// (4) Now show the user RECEIPT:
	header("Location: user_receipt.php?userID=$userID");

	// (5) CLOSE the database connection:
	disconnectFromMySQLDatabase(); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------
?>
