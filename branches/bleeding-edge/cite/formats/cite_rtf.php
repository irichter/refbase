<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./cite/formats/cite_rtf.php
	// Created:    10-Jun-06, 02:04
	// Modified:   12-Feb-07, 22:00

	// This is a citation format file (which must reside within the 'cite/formats/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecords()' function that outputs a reference list from selected records in RTF format.
	// RTF 1.0 format specification is available at <http://latex2rtf.sourceforge.net/RTF-Spec-1.0.txt>, more info at <http://en.wikipedia.org/wiki/RTF>

	// --------------------------------------------------------------------


	// Include the MINIMALRTF Package
	require_once 'includes/classes/org/bibliophile/MINIMALRTF.php';


	// --- BEGIN CITATION FORMAT ---

	// Requires the MINIMALRTF Package (by Mark Grimshaw), which is available
	// under the GPL from: <http://bibliophile.sourceforge.net>

	function citeRecords($result, $rowsFound, $query, $oldQuery, $showQuery, $showLinks, $rowOffset, $showRows, $previousOffset, $nextOffset, $citeStyle, $citeOrder, $citeType, $orderBy, $headerMsg, $userID, $viewType)
	{
		global $contentTypeCharset; // defined in 'ini.inc.php'

		global $client;

		// The array '$transtab_refbase_rtf' contains search & replace patterns for conversion from refbase markup to RTF markup & entities
		global $transtab_refbase_rtf; // defined in 'transtab_refbase_rtf.inc.php'

		// Initialize array variables:
		$yearsArray = array();
		$typeTitlesArray = array();

		// Define inline text markup to be used by the 'citeRecord()' function:
		$markupPatternsArray = array("bold-prefix"     => "{\\b ",
									"bold-suffix"      => "}",
									"italic-prefix"    => "{\\i ",
									"italic-suffix"    => "}",
									"underline-prefix" => "{\\ul ",
									"underline-suffix" => "}",
									"endash"           => "\\endash ",
									"emdash"           => "\\emdash ");

		// Defines search & replace 'actions' that will be applied upon RTF output to all those refbase fields that are listed
		// in the corresponding 'fields' element:
		$rtfSearchReplaceActionsArray = array(
												array(
														'fields'  => array("title", "address", "keywords", "abstract", "orig_title", "series_title", "abbrev_series_title", "notes", "publication"),
														'actions' => $transtab_refbase_rtf
													)
											);

		// For CLI queries, we'll allow paging thru the result set, i.e. we honour the values of the CLI options '-S|--start' ('$rowOffset')
		// and '-R|--rows' ('$showRows') ('$rowOffset' and '$showRows' are re-assigned in function 'seekInMySQLResultsToOffset()' in 'include.inc.php')
		if (eregi("^cli", $client)) // if the query originated from a command line client such as the "refbase" CLI client ("cli-refbase-1.0")
			$showMaxRows = $showRows; // show only rows up to the value given in '$showRows'
		else
			$showMaxRows = $rowsFound; // otherwise show all rows


		// Setup the basic RTF document structure (RTF functions defined in 'MINIMALRTF.php'):
		$rtf = new MINIMALRTF(); // initialize RTF object
		$rtfData = $rtf->openRtf(); // create RTF opening tag

		$rtf->createFontBlock(0, "Arial"); // create & set RTF font blocks
		$rtf->createFontBlock(1, "Times New Roman");
		$rtfData .= $rtf->setFontBlock();

		// Header
		if (!empty($headerMsg))
				$rtfData .= "{\header\pard\qc $headerMsg\par}\n";

		$rtfData .= $rtf->justify("full", 0.5, 0, -0.5); // by default, we'll justify text and set a hanging indent (left indent: 0.5, right indent: 0, first-line indent: -0.5)


		// LOOP OVER EACH RECORD:
		// Fetch one page of results (or less if on the last page)
		// (i.e., upto the limit specified in $showMaxRows) fetch a row into the $row array and ...
		for ($rowCounter=0; (($rowCounter < $showMaxRows) && ($row = @ mysql_fetch_array($result))); $rowCounter++)
		{
			foreach ($row as $rowFieldName => $rowFieldValue)
				// Apply search & replace 'actions' to all fields that are listed in the 'fields' element of the arrays contained in '$rtfSearchReplaceActionsArray':
				foreach ($rtfSearchReplaceActionsArray as $fieldActionsArray)
					if (in_array($rowFieldName, $fieldActionsArray['fields']))
						$row[$rowFieldName] = searchReplaceText($fieldActionsArray['actions'], $row[$rowFieldName], true); // function 'searchReplaceText()' is defined in 'include.inc.php'


			// Order attributes according to the chosen output style & record type:
			$record = citeRecord($row, $citeStyle, $citeType, $markupPatternsArray, false); // function 'citeRecord()' is defined in the citation style file given in '$citeStyleFile' (which, in turn, must reside in the 'cite' directory of the refbase root directory), see function 'generateCitations()'


			// Print out the current record:
			if (!empty($record)) // unless the record buffer is empty...
			{
				// Print any section heading(s):
				if (eregi("year|type", $citeOrder))
				{
					$headingPrefix = $rtf->justify("left", 0, 0, 0) // left-align the current heading without any indents
									. $rtf->paragraph(0, 12); // create empty paragraph in front of heading using "Arial" (font block 0) and a font size of 12pt

					$headingSuffix = $rtf->justify("full", 0.5, 0, -0.5); // justify any following text and set a hanging indent (left indent: 0.5, right indent: 0, first-line indent: -0.5)

					if ($citeOrder == "type") // for 'citeOrder=type' we'll always print an empty paragraph after the heading
						$headingSuffix .= $rtf->paragraph(0, 12); // create empty paragraph using "Arial" (font block 0) and a font size of 12pt

					list($yearsArray, $typeTitlesArray, $sectionHeading) = generateSectionHeading($yearsArray, $typeTitlesArray, $row, $citeOrder, $headingPrefix, $headingSuffix, "{\f0\fs28 {\b ", "}\par}\n", "{\f0\fs24 {\b ", "}\par}\n");

					// Note that we pass raw RTF commands to the above function instead of using the 'textBlock()' function from 'MINIMALRTF.php'. This is due to a current limitation of the 'generateSectionHeading()' function.
					// For 'citeOrder=year', the appropriate call to the 'textBlock()' function would look like this:
					// $rtfData .= $rtf->textBlock(0, 14, $rtf->bold($row['year'])); // create major heading with the current year using "Arial" (font block 0) and a font size of 14pt, printed in bold

					$rtfData .= $sectionHeading;
				}

				// If character encoding is not UTF-8 already, convert record text to UTF-8:
				if ($contentTypeCharset != "UTF-8")
					$record = convertToCharacterEncoding("UTF-8", "IGNORE", $record); // function 'convertToCharacterEncoding()' is defined in 'include.inc.php'

				// Encode characters with an ASCII value of >= 128 in RTF 1.16 unicode format:
				$recordUnicodeCharEncoded = $rtf->utf8_2_unicode($record); // converts UTF-8 chars to unicode character codes

				// Write RTF paragraph:
				$rtfData .= $rtf->textBlock(1, 12, $recordUnicodeCharEncoded); // create text block with encoded record text using "Times New Roman" (font block 1) and a font size of 12pt
			}
		}

		$rtfData .= $rtf->closeRtf(); // create RTF closing tag

		return $rtfData;
	}

	// --- END CITATION FORMAT ---
?>