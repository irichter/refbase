<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./simple_search.php
	// Created:    29-Jul-02, 16:39
	// Modified:   13-Feb-05, 21:12

	// Search form providing access to the main fields of the database.
	// It offers some output options (like how many records to display per page)
	// and let's you specify the output sort order (up to three levels deep).

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/
	
	// Incorporate some include files:
	include 'initialize/db.inc.php'; // 'db.inc.php' is included to hide username and password
	include 'includes/header.inc.php'; // include header
	include 'includes/footer.inc.php'; // include footer
	include 'includes/include.inc.php'; // include common functions
	include 'initialize/ini.inc.php'; // include common variables
	include 'includes/locales.inc.php'; // include the locales

	// --------------------------------------------------------------------

	// START A SESSION:
	// call the 'start_session()' function (from 'include.inc.php') which will also read out available session variables:
	start_session(true);

	// --------------------------------------------------------------------

	// (1) Open the database connection and use the literature database:
	connectToMySQLDatabase(""); // function 'connectToMySQLDatabase()' is defined in 'include.inc.php'

	// If there's no stored message available:
	if (!isset($_SESSION['HeaderString']))
		$HeaderString = "Search the main fields of the database:"; // Provide the default message
	else
	{
		$HeaderString = $_SESSION['HeaderString']; // extract 'HeaderString' session variable (only necessary if register globals is OFF!)

		// Note: though we clear the session variable, the current message is still available to this script via '$HeaderString':
		deleteSessionVariable("HeaderString"); // function 'deleteSessionVariable()' is defined in 'include.inc.php'
	}

	// Extract the view type requested by the user (either 'Print', 'Web' or ''):
	// ('' will produce the default 'Web' output style)
	if (isset($_REQUEST['viewType']))
		$viewType = $_REQUEST['viewType'];
	else
		$viewType = "";

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc.php')

	// (2a) Display header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc.php'):
	displayHTMLhead(encodeHTML($officialDatabaseName) . " -- Simple Search", "index,follow", "Search the " . encodeHTML($officialDatabaseName), "", false, "", $viewType);
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks, "");

	// (2b) Start <form> and <table> holding the form elements:
	echo "\n<form action=\"search.php\" method=\"POST\">";
	echo "\n<input type=\"hidden\" name=\"formType\" value=\"simpleSearch\">"
			. "\n<input type=\"hidden\" name=\"showQuery\" value=\"0\">";
	echo "\n<table align=\"center\" border=\"0\" cellpadding=\"0\" cellspacing=\"10\" width=\"95%\" summary=\"This table holds the search form\">"
			. "\n<tr>"                
			. "\n\t<th align=\"left\">".$loc["show"]."</th>\n\t<th align=\"left\">".$loc["Field"]."</th>\n\t<th align=\"left\">&nbsp;</th>\n\t<th align=\"left\">".$loc["That..."]."</th>\n\t<th align=\"left\">".$loc["Searchstring"]."</th>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td width=\"20\" valign=\"middle\"><input type=\"checkbox\" name=\"showAuthor\" value=\"1\" checked></td>"
			. "\n\t<td width=\"40\"><b>".$loc["author"].":</b></td>\n\t<td width=\"10\">&nbsp;</td>"
			. "\n\t<td width=\"125\">\n\t\t<select name=\"authorSelector\">\n\t\t\t<option>".$loc["contains"]."</option>\n\t\t\t<option>".$loc["contains not"]." </option>\n\t\t\t<option>".$loc["equal to"]."</option>\n\t\t\t<option>".$loc["equal to not"]."</option>\n\t\t\t<option>".$loc["starts with"]."</option>\n\t\t\t<option>".$loc["ends with"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"authorName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showTitle\" value=\"1\" checked></td>"
			. "\n\t<td><b>".$loc["title"].":</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"titleSelector\">\n\t\t\t<option>".$loc["contains"]."</option>\n\t\t\t<option>".$loc["contains not"]."</option>\n\t\t\t<option>".$loc["equal to"]."</option>\n\t\t\t<option>".$loc["equal to not"]."</option>\n\t\t\t<option>".$loc["starts with"]."</option>\n\t\t\t<option>".$loc["ends with"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"titleName\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showYear\" value=\"1\" checked></td>"
			. "\n\t<td><b>".$loc["year"].":</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"yearSelector\">\n\t\t\t<option>".$loc["contains"]."</option>\n\t\t\t<option>".$loc["contains not"]."</option>\n\t\t\t<option>".$loc["equal to"]."</option>\n\t\t\t<option>".$loc["equal to not"]."</option>\n\t\t\t<option>".$loc["starts with"]."</option>\n\t\t\t<option>".$loc["ends with"]."</option>\n\t\t\t<option>".$loc["is greater than"]."</option>\n\t\t\t<option>".$loc["is less than"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"yearNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showPublication\" value=\"1\" checked></td>"
			. "\n\t<td><b>".$loc["Publication"].":</b></td>\n\t<td align=\"center\"><input type=\"radio\" name=\"publicationRadio\" value=\"1\" checked></td>"
			. "\n\t<td>\n\t\t<select name=\"publicationSelector\">\n\t\t\t<option>".$loc["contains"]."</option>\n\t\t\t<option>".$loc["contains not"]."</option>\n\t\t\t<option>".$loc["equal to"]."</option>\n\t\t\t<option>".$loc["equal to not"]."</option>\n\t\t\t<option>".$loc["starts with"]."</option>\n\t\t\t<option>".$loc["ends with"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>";

	// (3) Run the query on the literature database through the connection:
	//     (here by use of the 'selectDistinct' function)
	// Produce the select list
	// Parameters:
	// 1: Database connection
	// 2. Table that contains values
	// 3. Attribute that contains values
	// 4. <SELECT> element name
	// 5. An additional non-database value
	// 6. Optional <OPTION SELECTED>
	selectDistinct($connection,
				 $tableRefs,
				 "publication",
				 "publicationName",
				 "All",
				 "All");

	echo "\n\t</td>"
			. "\n</tr>";

	echo "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td align=\"right\">".$loc["or"].":</td>\n\t<td align=\"center\"><input type=\"radio\" name=\"publicationRadio\" value=\"0\"></td>"
			. "\n\t<td>\n\t\t<select name=\"publicationSelector2\">\n\t\t\t<option>".$loc["contains"]."</option>\n\t\t\t<option>".$loc["contains not"]."</option>\n\t\t\t<option>".$loc["equal to"]."</option>\n\t\t\t<option>".$loc["equal to not"]."</option>\n\t\t\t<option>".$loc["starts with"]."</option>\n\t\t\t<option>".$loc["ends with"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"publicationName2\" size=\"42\"></td>"
			. "\n</tr>";

	// (4) Complete the form:
	echo "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showVolume\" value=\"1\" checked></td>"
			. "\n\t<td><b>".$loc["Volume"].":</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"volumeSelector\">\n\t\t\t<option>".$loc["contains"]."</option>\n\t\t\t<option>".$loc["contains not"]."</option>\n\t\t\t<option>".$loc["equal to"]."</option>\n\t\t\t<option>".$loc["equal to not"]."</option>\n\t\t\t<option>".$loc["starts with"]."</option>\n\t\t\t<option>".$loc["ends with"]."</option>\n\t\t\t<option>".$loc["is greater than"]."</option>\n\t\t\t<option>".$loc["is less than"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"volumeNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showPages\" value=\"1\" checked></td>"
			. "\n\t<td><b>".$loc["Pages"].":</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"pagesSelector\">\n\t\t\t<option>".$loc["contains"]."</option>\n\t\t\t<option>".$loc["contains not"]."</option>\n\t\t\t<option>".$loc["equal to"]."</option>\n\t\t\t<option>".$loc["equal to not"]."</option>\n\t\t\t<option>".$loc["starts with"]."</option>\n\t\t\t<option>".$loc["ends with"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td><input type=\"text\" name=\"pagesNo\" size=\"42\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"top\"><b>".$loc["DisplayOptions"].":</b></td>\n\t<td>&nbsp;</td>"
			. "\n\t<td valign=\"middle\"><input type=\"checkbox\" name=\"showLinks\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;".$loc["display"]." ".$loc["Links"]."</td>"
			. "\n\t<td valign=\"middle\">".$loc["show"]."&nbsp;&nbsp;&nbsp;<input type=\"text\" name=\"showRows\" value=\"10\" size=\"4\">&nbsp;&nbsp;&nbsp;".$loc["Records"]." ".$loc["per page"]
			. "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type=\"submit\" value=\"".$loc["Search"]."\"></td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>\n\t<td>&nbsp;</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>1.&nbsp;".$loc["sort by"].":</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector1\">\n\t\t\t<option selected>".$loc["author"]."</option>\n\t\t\t<option>".$loc["title"]."</option>\n\t\t\t<option>".$loc["year"]."</option>\n\t\t\t<option>".$loc["Publication"]."</option>\n\t\t\t<option>".$loc["Volume"]."</option>\n\t\t\t<option>".$loc["Pages"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio1\" value=\"0\" checked>&nbsp;&nbsp;&nbsp;".$loc["ascending"]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio1\" value=\"1\">&nbsp;&nbsp;&nbsp;".$loc["descending"]."\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>2.&nbsp;".$loc["sort by"].":</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector2\">\n\t\t\t<option>".$loc["author"]."</option>\n\t\t\t<option>".$loc["title"]."</option>\n\t\t\t<option selected>".$loc["year"]."</option>\n\t\t\t<option>".$loc["Publication"]."</option>\n\t\t\t<option>".$loc["Volume"]."</option>\n\t\t\t<option>".$loc["Pages"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio2\" value=\"0\">&nbsp;&nbsp;&nbsp;".$loc["ascending"]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio2\" value=\"1\" checked>&nbsp;&nbsp;&nbsp;".$loc["descending"]."\n\t</td>"
			. "\n</tr>"
			. "\n<tr>"
			. "\n\t<td>&nbsp;</td>\n\t<td>3.&nbsp;".$loc["sort by"].":</td>\n\t<td>&nbsp;</td>"
			. "\n\t<td>\n\t\t<select name=\"sortSelector3\">\n\t\t\t<option>".$loc["author"]."</option>\n\t\t\t<option>".$loc["title"]."</option>\n\t\t\t<option>".$loc["year"]."</option>\n\t\t\t<option selected>".$loc["Publication"]."</option>\n\t\t\t<option>".$loc["Volume"]."</option>\n\t\t\t<option>".$loc["Pages"]."</option>\n\t\t</select>\n\t</td>"
			. "\n\t<td>\n\t\t<input type=\"radio\" name=\"sortRadio3\" value=\"0\" checked>&nbsp;&nbsp;&nbsp;".$loc["ascending"]."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
			. "\n\t\t<input type=\"radio\" name=\"sortRadio3\" value=\"1\">&nbsp;&nbsp;&nbsp;".$loc["descending"]."\n\t</td>"
			. "\n</tr>"
			. "\n</table>"
			. "\n</form>";
	
	// (5) Close the database connection:
	disconnectFromMySQLDatabase(""); // function 'disconnectFromMySQLDatabase()' is defined in 'include.inc.php'

	// --------------------------------------------------------------------

	// THE SELECTDISTINCT FUNCTION:
	function selectDistinct ($connection,
							$tableName,
							$columnName,
							$pulldownName,
							$additionalOption,
							$defaultValue)
	{
	 $defaultWithinResultSet = FALSE;

	 // Query to find distinct values of $columnName
	 // in $tableName
	 // Note: in order to avoid book names we'll restrict the query to records whose record type contains 'journal'!
	 $distinctQuery = "SELECT DISTINCT $columnName FROM $tableName WHERE type RLIKE \"journal\" ORDER BY $columnName";

	 // Run the distinctQuery on the database through the connection:
	$resultId = queryMySQLDatabase($distinctQuery, ""); // function 'queryMySQLDatabase()' is defined in 'include.inc.php'

	 // Retrieve all distinct values
	 $i = 0;
	 while ($row = @ mysql_fetch_array($resultId))
		$resultBuffer[$i++] = $row[$columnName];

	 // Start the select widget
	 echo "\n\t\t<select name=\"$pulldownName\">";		 

	 // Is there an additional option?
	 if (isset($additionalOption))
		// Yes, but is it the default option?
		if ($defaultValue == $additionalOption)
			// Show the additional option as selected
			echo "\n\t\t\t<option selected>$additionalOption</option>";
		else
			// Just show the additional option
			echo "\n\t\t\t<option>$additionalOption</option>";

	 // check for a default value
	 if (isset($defaultValue))
	 {
		// Yes, there's a default value specified

		// Check if the defaultValue is in the 
		// database values
		foreach ($resultBuffer as $result)
			if ($result == $defaultValue)
				// Yes, show as selected
				echo "\n\t\t\t<option selected>$result</option>";
			else
				// No, just show as an option
				echo "\n\t\t\t<option>$result</option>";
	 }	// end if defaultValue
	 else 
	 { 
		// No defaultValue
		
		// Show database values as options
		foreach ($resultBuffer as $result)
			echo "\n\t\t\t<option>$result</option>";
	 }
	 echo "\n\t\t</select>";
	} // end of function

	// --------------------------------------------------------------------

	// DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc.php')
	displayfooter("");

	// --------------------------------------------------------------------
?>

</body>
</html> 