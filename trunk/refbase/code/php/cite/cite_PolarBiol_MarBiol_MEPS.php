<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de>
	//             This code is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY.
	//             Please see the GNU General Public License for more details.
	// File:       ./cite/cite_PolarBiol_MarBiol_MEPS.php
	// Created:    28-Sep-04, 22:14
	// Modified:   08-Sep-05, 13:30

	// This is a citation style file (which must reside within the 'cite/' sub-directory of your refbase root directory). It contains a
	// version of the 'citeRecord()' function that outputs a reference list from selected records according to the citation style used by
	// the journals "Polar Biology", "Marine Biology" (both Springer-Verlag, springeronline.com) and "MEPS" (Inter-Research, int-res.com).

	/*
	Code adopted from example code by Hugh E. Williams and David Lane, authors of the book
	"Web Database Application with PHP and MySQL", published by O'Reilly & Associates.
	*/

	// --------------------------------------------------------------------


	// --- BEGIN CITATION STYLE ---

	function citeRecord($row, $citeStyle)
	{
		$record = ""; // make sure that our buffer variable is empty

		// --- BEGIN TYPE = JOURNAL ARTICLE ----------------------------------------------------------------------------------------------------

		if ($row['type'] == "Journal Article")
			{
				if (!empty($row['author']))			// author
					{
						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. for all authors except the last author: new delimiter that separates different authors
						//   3. for the last author: new delimiter that separates the last author from all other authors
						//   4. pattern describing old delimiter that separates author name & initials (within one author)
						//   5. for the first author: new delimiter that separates author name & initials (within one author)
						//   6. for all authors except the first author: new delimiter that separates author name & initials (within one author)
						//   7. new delimiter that separates multiple initials (within one author)
						//   8. for the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   9. for all authors except the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//  10. contents of the author field
						$author = reArrangeAuthorContents(" *; *", // 1.
															", ", // 2.
															", ", // 3.
															" *, *", // 4.
															" ", // 5.
															" ", // 6.
															"", // 7.
															false, // 8.
															false, // 9.
															$row['author']); // 10.

						$record .= $author . " ";
					}

				if (!empty($row['year']))				// year
					$record .= "(" . $row['year'] . ") ";

				if (!empty($row['title']))			// title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

				if (!empty($row['abbrev_journal']))		// abbreviated journal name
					$record .= $row['abbrev_journal'] . " ";

				// if there's no abbreviated journal name, we'll use the full journal name
				elseif (!empty($row['publication']))	// publication (= journal) name
					$record .= $row['publication'] . " ";

				if (!empty($row['volume']))			// volume
					$record .= $row['volume'];

				if (!empty($row['issue']))			// issue
					$record .= "(" . $row['issue'] . ")";

				if ($row['online_publication'] == "yes") // this record refers to an online article
				{
					// instead of any pages info (which normally doesn't exist for online publications) we append
					// an optional string (given in 'online_citation') plus the DOI:

					if (!empty($row['online_citation']))			// online_citation
					{
						if (!empty($row['volume'])||!empty($row['issue']))		// only add ":" if either volume or issue isn't empty
							$record .= ":";

						$record .= " " . $row['online_citation'];
					}

					if (!empty($row['doi']))			// doi
						$record .= " doi:" . $row['doi'];
				}
				else // $row['online_publication'] == "no" -> this record refers to a printed article, so we append any pages info instead:
				{
					if (!empty($row['pages']))			// pages
						{
							if (!empty($row['volume'])||!empty($row['issue']))		// only add ":" if either volume or issue isn't empty
								$record .= ":";
							if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
								$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
							else
								$pagesDisplay = $row['pages'];
							$record .= $pagesDisplay;
						}
				}
			}

		// --- BEGIN TYPE = BOOK CHAPTER -------------------------------------------------------------------------------------------------------

		elseif ($row['type'] == "Book Chapter")
			{
				if (!empty($row['author']))			// author
					{
						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. for all authors except the last author: new delimiter that separates different authors
						//   3. for the last author: new delimiter that separates the last author from all other authors
						//   4. pattern describing old delimiter that separates author name & initials (within one author)
						//   5. for the first author: new delimiter that separates author name & initials (within one author)
						//   6. for all authors except the first author: new delimiter that separates author name & initials (within one author)
						//   7. new delimiter that separates multiple initials (within one author)
						//   8. for the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   9. for all authors except the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//  10. contents of the author field
						$author = reArrangeAuthorContents(" *; *", // 1.
															", ", // 2.
															", ", // 3.
															" *, *", // 4.
															" ", // 5.
															" ", // 6.
															"", // 7.
															false, // 8.
															false, // 9.
															$row['author']); // 10.

						$record .= $author . " ";
					}

				if (!empty($row['year']))				// year
					$record .= "(" . $row['year'] . ") ";

				if (!empty($row['title']))			// title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

				if (!empty($row['editor']))			// editor
					{
						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. for all authors except the last author: new delimiter that separates different authors
						//   3. for the last author: new delimiter that separates the last author from all other authors
						//   4. pattern describing old delimiter that separates author name & initials (within one author)
						//   5. for the first author: new delimiter that separates author name & initials (within one author)
						//   6. for all authors except the first author: new delimiter that separates author name & initials (within one author)
						//   7. new delimiter that separates multiple initials (within one author)
						//   8. for the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   9. for all authors except the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//  10. contents of the author field
						$editor = reArrangeAuthorContents(" *; *", // 1.
															", ", // 2.
															", ", // 3.
															" *, *", // 4.
															" ", // 5.
															" ", // 6.
															"", // 7.
															false, // 8.
															false, // 9.
															$row['editor']); // 10.

						$record .= "In: " . $editor;
						if (ereg("^[^;\r\n]+(;[^;\r\n]+)+$", $row['editor'])) // there are at least two editors (separated by ';')
							$record .= " (eds)";
						else // there's only one editor (or the editor field is malformed with multiple editors but missing ';' separator[s])
							$record .= " (ed)";
					}

				$publication = ereg_replace("[ \r\n]*\(Eds?:[^\)\r\n]*\)", "", $row['publication']);
				if (!empty($publication))			// publication
					$record .= " " . $publication . ". ";
				else
					if (!empty($row['editor']))
						$record .= ". ";

				if (!empty($row['abbrev_series_title']) OR !empty($row['series_title'])) // if there's either a full or an abbreviated series title, series information will replace the publisher & place information
					{
						if (!empty($row['abbrev_series_title']))
							$record .= $row['abbrev_series_title'];	// abbreviated series title

						// if there's no abbreviated series title, we'll use the full series title instead:
						elseif (!empty($row['series_title']))
							$record .= $row['series_title'];	// full series title

						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= " ";

						if (!empty($row['series_volume']))	// series volume
							$record .= $row['series_volume'];

						if (!empty($row['series_issue']))	// series issue
							$record .= "(" . $row['series_issue'] . ")";

						if (!empty($row['pages']))
							$record .= ", ";

					}
				else // if there's NO series title at all (neither full nor abbreviated), we'll insert the publisher & place instead:
					{
						if (!empty($row['publisher']))		// publisher
							{
								$record .= $row['publisher'];
								if (!empty($row['place']))
									$record .= ", ";
								else
								{
									if (!ereg(",$", $row['publisher']))
										$record .= ",";
									$record .= " ";
								}
							}

						if (!empty($row['place']))			// place
							{
								$record .= $row['place'];
								if (!empty($row['pages']))
									{
										if (!ereg(",$", $row['place']))
											$record .= ",";
										$record .= " ";
									}
							}
					}

				if (!empty($row['pages']))			// pages
					{
						if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
							$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
						else
							$pagesDisplay = $row['pages'];
						$record .= "pp " . $pagesDisplay;
					}
			}

		// --- BEGIN TYPE = BOOK WHOLE / MAP / MANUSCRIPT / JOURNAL ----------------------------------------------------------------------------

		elseif (ereg("Book Whole|Map|Manuscript|Journal", $row['type']))
			{
				if (!empty($row['author']))			// author
					{
						$author = ereg_replace("[ \r\n]*\(eds?\)", "", $row['author']);

						// Call the 'reArrangeAuthorContents()' function in order to re-order contents of the author field. Required Parameters:
						//   1. pattern describing old delimiter that separates different authors
						//   2. for all authors except the last author: new delimiter that separates different authors
						//   3. for the last author: new delimiter that separates the last author from all other authors
						//   4. pattern describing old delimiter that separates author name & initials (within one author)
						//   5. for the first author: new delimiter that separates author name & initials (within one author)
						//   6. for all authors except the first author: new delimiter that separates author name & initials (within one author)
						//   7. new delimiter that separates multiple initials (within one author)
						//   8. for the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//   9. for all authors except the first author: boolean value that specifies if initials follow *before* the author's name ['true'], or *after* the author's name ['false'] (which is the default in the db)
						//  10. contents of the author field
						$author = reArrangeAuthorContents(" *; *", // 1.
															", ", // 2.
															", ", // 3.
															" *, *", // 4.
															" ", // 5.
															" ", // 6.
															"", // 7.
															false, // 8.
															false, // 9.
															$author); // 10.

						$record .= $author . " ";
					}

				if (!empty($row['year']))				// year
					$record .= "(" . $row['year'] . ") ";

				if (!empty($row['title']))			// title
					{
						$record .= $row['title'];
						if (!ereg("[?!.]$", $row['title']))
							$record .= ".";
						$record .= " ";
					}

				if (!empty($row['thesis']))			// thesis
					$record .= $row['thesis'] . ". ";

				if (!empty($row['publisher']))		// publisher
					{
						$record .= $row['publisher'];
						if (!empty($row['place']))
							$record .= ", ";
						else
						{
							if (!ereg(",$", $row['publisher']))
								$record .= ",";
							$record .= " ";
						}
					}

				if (!empty($row['place']))			// place
					{
						$record .= $row['place'];
						if (!empty($row['abbrev_series_title']) || !empty($row['series_title']) || !empty($row['pages']))
							{
								if (!ereg(",$", $row['place']))
									$record .= ",";
								$record .= " ";
							}
					}

				if (!empty($row['abbrev_series_title']) OR !empty($row['series_title']))	// add either abbreviated or full series title
					{
						if (!empty($row['abbrev_series_title']))
							$record .= $row['abbrev_series_title'];	// abbreviated series title

						// if there's no abbreviated series title, we'll use the full series title instead:
						elseif (!empty($row['series_title']))
							$record .= $row['series_title'];	// full series title

						// series volume & series issue will get appended only if there's also either the full or an abbreviated series title(!):
						if (!empty($row['series_volume'])||!empty($row['series_issue']))
							$record .= " ";

						if (!empty($row['series_volume']))	// series volume
							$record .= $row['series_volume'];

						if (!empty($row['series_issue']))	// series issue
							$record .= "(" . $row['series_issue'] . ")";

						if (!empty($row['pages']))
							{
								if (!ereg(",$", $row['series_volume']))
									$record .= ",";
								$record .= " ";
							}
					}

				if (!empty($row['pages']))			// pages
					{
						if (ereg("[0-9] *- *[0-9]", $row['pages'])) // if the 'pages' field contains a page range (like: "127-132")
							// Note that we'll check for page ranges here although for whole books the 'pages' field should NOT contain a page range but the total number of pages! (like: "623 pp")
							$pagesDisplay = (ereg_replace("([0-9]+) *- *([0-9]+)", "\\1&#8211;\\2", $row['pages']));
						else
							$pagesDisplay = $row['pages'];
						$record .= $pagesDisplay;
					}
			}

		// --- BEGIN POST-PROCESSING -----------------------------------------------------------------------------------------------------------

		// do some further cleanup:
		$record = ereg_replace("[.,][ \r\n]*$", "", $record); // remove '.' or ',' at end of line
		if ($citeStyle == "MEPS") // if '$citeStyle' = 'MEPS' ...
			$record = ereg_replace("pp ([0-9]+)", "p \\1", $record); // ... replace 'pp' with 'p' in front of (book chapter) page numbers


		return $record;
	}

	// --- END CITATION STYLE ---

