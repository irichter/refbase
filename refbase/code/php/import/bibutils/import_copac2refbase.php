<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./import/bibutils/import_copac2refbase.php
	// Created:    24-Feb-06, 02:07
	// Modified:   25-Feb-06, 19:30

	// This is an import format file (which must reside within the 'import/' sub-directory of your refbase root directory). It contains a version of the
	// 'importRecords()' function that imports records from 'COPAC'-formatted data, i.e. data that were exported from the COPAC Internet Database
	// Service (http://www.copac.ac.uk/), a union catalogue that provides free access to academic and national libraries in the UK and Ireland.
	
	// --------------------------------------------------------------------

	// --- BEGIN IMPORT FORMAT ---

	// Import records from COPAC-formatted source data:

	// Requires the following packages (available under the GPL):
	//    - bibutils <http://www.scripps.edu/~cdputnam/software/bibutils/bibutils.html>

	include 'includes/execute.inc.php';

	function importRecords($sourceText, $importRecordsRadio, $importRecordNumbersArray)
	{
		// convert COPAC format to MODS XML format:
		$sourceText = importBibutils($sourceText,"copac2xml"); // function 'importBibutils()' is defined in 'execute.inc.php'

		// convert MODS XML format to RIS format:
		$sourceText = importBibutils($sourceText,"xml2ris"); // function 'importBibutils()' is defined in 'execute.inc.php'

		// parse RIS format:
		return risToRefbase($sourceText, $importRecordsRadio, $importRecordNumbersArray); // function 'risToRefbase()' is defined in 'import.inc.php'
	}

	// --- END IMPORT FORMAT ---

	// --------------------------------------------------------------------
?>
