<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./index.php
	// Created:    29-Jul-02, 16:45
	// Modified:   20-Jan-03, 23:29

	// This script builds the main page.
	// It provides login and quick search forms
	// as well as links to various search forms.

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

	// --------------------------------------------------------------------

	// If there's no stored message available:
	if (!session_is_registered("HeaderString"))
		$HeaderString = "Welcome! This database provides access to polar &amp; marine literature."; // Provide the default welcome message
	else
		session_unregister("HeaderString"); // Note: though we clear the session variable, the current message is still available to this script via '$HeaderString'

	// CONSTRUCT SQL QUERY:
	$query = "SELECT COUNT(serial) FROM refs"; // query the total number of records

	// --------------------------------------------------------------------

	// (1) OPEN CONNECTION, (2) SELECT DATABASE, (3) RUN QUERY, (4) DISPLAY HEADER, (5) CLOSE CONNECTION

	// (1) OPEN the database connection:
	//      (variables are set by include file 'db.inc'!)
	if (!($connection = @ mysql_connect($hostName, $username, $password)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the host:", "");

	// (2) SELECT the database:
	//      (variables are set by include file 'db.inc'!)
	if (!(mysql_select_db($databaseName, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to connect to the database:", "");

	// (3a) RUN the query on the database through the connection:
	if (!($result = @ mysql_query ($query, $connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to query the database:", "");

	// (3b) EXTRACT results:
	$row = mysql_fetch_row($result); //fetch the current row into the array $row (it'll be always *one* row, but anyhow)
	$recordCount = $row[0]; // extract the contents of the first (and only) row

	// Show the login status:
	showLogin(); // (function 'showLogin()' is defined in 'include.inc')

	// (4) DISPLAY header:
	// call the 'displayHTMLhead()' and 'showPageHeader()' functions (which are defined in 'header.inc'):
	displayHTMLhead("IP&Ouml; Literature Database -- Home", "index,follow", "Search the IP&Ouml; Literature Database", "", false, "");
	showPageHeader($HeaderString, $loginWelcomeMsg, $loginStatus, $loginLinks);

	// (5) CLOSE the database connection:
	if (!(mysql_close($connection)))
		if (mysql_errno() != 0) // this works around a stupid(?) behaviour of the Roxen webserver that returns 'errno: 0' on success! ?:-(
			showErrorMsg("The following error occurred while trying to disconnect from the database:", "");

	// --------------------------------------------------------------------
?>
<table align="center" border="0" cellpadding="2" cellspacing="5" width="90%" summary="This table explains features, goals and usage of the IP&Ouml; literature database">
	<tr>
		<td colspan="2"><h3>Goals &amp; Features</h3></td>
		<td width="80" valign="bottom"><?php
if (!session_is_registered("loginEmail"))
	{
?><div class="header"><b>Login:</b></div><?php
	}
?></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>This web database is an attempt to provide a comprehensive and platform-independent literature resource for scientists working in the field of polar &amp; marine sciences.
			<br>
			<br>
			This database offers:
			<ul type="circle">
				<li>a comprehensive dataset on polar &amp; marine literature<?php
	// report the total number of records:
	echo ", currently featuring " . $recordCount . " records";
?></li>
				<li>a clean &amp; standardized interface</li>
				<li>a multitude of search options, including both, simple &amp; advanced as well as powerful SQL search options</li>
				<li>various display &amp; export options</li>
			</ul>
		</td>
		<td width="80" valign="top">
<?php
if (!session_is_registered("loginEmail"))
	{
?>
			<form action="user_login.php" method="POST">
				Email Address:
				<br>
				<input type="text" name="loginEmail" size="12">
				<br>
				Password:
				<br>
				<input type="password" name="loginPassword" size="12">
				<br>
				<input type="submit" value="Login">
			</form><?php
	}
?>
		</td>
	</tr>
	<tr>
		<td colspan="2"><h3>Search</h3></td>
		<td width="80" valign="bottom"><div class="header"><b>Quick Search:</b></div></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>Search the literature database:
			<ul type="circle">
				<li><a href="simple_search.php">Simple Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the main fields of the database</li>
				<li><a href="advanced_search.php">Advanced Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search all fields of the database</li>
				<li><a href="sql_search.php">SQL Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the database by use of a SQL query</li>
				<li><a href="library_search.php">Library Search</a>&nbsp;&nbsp;&nbsp;&#8211;&nbsp;&nbsp;&nbsp;search the library of the Institut f&uuml;r Polar&ouml;kologie</li>
			</ul>
			<br>
			Or, alternatively:
<?php
	// Get the current year in order to include it into the query URL:
	$CurrentYear = date(Y);
	echo "\t\t\t<ul type=\"circle\">\n";
	echo "\t\t\t\t<li>view the 10 database entries that were <a href=\"search.php?sqlQuery=SELECT+author%2C+title%2C+year%2C+publication%2C+volume%2C+created_by+FROM+refs+ORDER+BY+created_date+DESC%2C+created_time+DESC%2C+serial+DESC+LIMIT+10&amp;showQuery=0&amp;showLinks=1&amp;formType=sqlSearch&amp;showRows=10\">added</a> / <a href=\"search.php?sqlQuery=SELECT+author%2C+title%2C+year%2C+publication%2C+volume%2C+modified_by+FROM+refs+ORDER+BY+modified_date+DESC%2C+modified_time+DESC%2C+serial+DESC+LIMIT+10&amp;showQuery=0&amp;showLinks=1&amp;formType=sqlSearch&amp;showRows=10\">edited</a> most recently.</li>";
	echo "\n\t\t\t\t<li>view all database entries that were <a href=\"search.php?sqlQuery=SELECT+author%2C+title%2C+year%2C+publication%2C+volume+FROM+refs+WHERE+year+%3D+$CurrentYear+ORDER+BY+author%2C+publication%2C+volume&amp;showQuery0=&amp;showLinks=1&amp;formType=sqlSearch&amp;showRows=20\">published in $CurrentYear</a>.</li>";
	echo "\n\t\t\t\t<li><a href=\"extract.php\">extract literature</a> cited within a text and build an appropriate reference list.</li>";
	echo "\n\t\t\t</ul>\n";
?>
		</td>
		<td width="80" valign="top">
			<form action="search.php" method="POST">
				<input type="hidden" name="formType" value="quickSearch">
				<input type="hidden" name="showQuery" value="0">
				<input type="hidden" name="showLinks" value="1">
				<select name="quickSearchSelector">
					<option selected>author</option>
					<option>title</option>
					<option>year</option>
					<option>keywords</option>
					<option>abstract</option>
				</select>
				<br>
				<input type="text" name="quickSearchName" size="12">
				<br>
				<input type="submit" value="Search">
			</form>
		</td>
	</tr>
	<tr>
		<td colspan="3"><h3>About</h3></td>
	</tr>
	<tr>
		<td width="15">&nbsp;</td>
		<td>This literature database is maintained by the <a href="http://www.uni-kiel.de/ipoe/">Institut f&uuml;r Polar&ouml;kologie</a> (IP&Ouml;), Kiel. You're welcome to send any questions or suggestions to our <a href="mailto:&#105;&#112;&#111;&#101;&#108;&#105;&#116;&#64;&#105;&#112;&#111;&#101;&#46;&#117;&#110;&#105;&#45;&#107;&#105;&#101;&#108;&#46;&#100;&#101;">feedback</a> address. The database is powered by <a href="http://www.refbase.net">refbase</a>, an open source database front-end for managing scientific literature &amp; citations that was initiated at IP&Ouml;.</td>
		<td width="80" valign="top"><a href="http://www.refbase.net/"><img src="img/refbase_credit.gif" alt="powered by refbase" width="80" height="44" hspace="0" border="0"></a></td>
	</tr>
</table><?php
	// --------------------------------------------------------------------

	//	DISPLAY THE HTML FOOTER:
	// call the 'displayfooter()' function from 'footer.inc')
	displayfooter("");

	// --------------------------------------------------------------------
?>
</body>
</html> 
