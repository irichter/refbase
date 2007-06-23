<?php
	// Project:    Web Reference Database (refbase) <http://www.refbase.net>
	// Copyright:  Matthias Steffens <mailto:refbase@extracts.de> and the file's
	//             original author(s).
	//
	//             This code is distributed in the hope that it will be useful,
	//             but WITHOUT ANY WARRANTY. Please see the GNU General Public
	//             License for more details.
	//
	// File:       ./includes/transtab_refbase_rtf.inc.php
	// Repository: $HeadURL$
	// Author(s):  Matthias Steffens <mailto:refbase@extracts.de>
	//
	// Created:    28-May-06, 18:21
	// Modified:   $Date$
	//             $Author$
	//             $Revision$

	// Search & replace patterns for conversion from refbase markup to RTF markup & entities. Converts refbase fontshape markup (italic, bold) and
	// super- and subscript into RTF commands, greek letters get converted into the respective Unicode character codes.
	// Search & replace patterns must be specified as perl-style regular expression and search patterns must include the leading & trailing slashes.

	$transtab_refbase_rtf = array(

		"/_(.+?)_/"            =>  "{\\i \\1}", // italic
		"/\\*\\*(.+?)\\*\\*/"  =>  "{\\b \\1}", // bold
		"/\\[super:(.+?)\\]/i" =>  "{\\super \\1}", // superscript (or use '\up6')
		"/\\[sub:(.+?)\\]/i"   =>  "{\\sub \\1}", // subscript (or use '\dn6')
		"/\\[permil\\]/"       =>  "\\uc0\\u8240 ",
		"/\\[infinity\\]/"     =>  "\\uc0\\u8734 ",
		"/\\[alpha\\]/"        =>  "\\uc0\\u945 ",
		"/\\[beta\\]/"         =>  "\\uc0\\u946 ",
		"/\\[gamma\\]/"        =>  "\\uc0\\u947 ",
		"/\\[delta\\]/"        =>  "\\uc0\\u948 ",
		"/\\[epsilon\\]/"      =>  "\\uc0\\u949 ",
		"/\\[zeta\\]/"         =>  "\\uc0\\u950 ",
		"/\\[eta\\]/"          =>  "\\uc0\\u951 ",
		"/\\[theta\\]/"        =>  "\\uc0\\u952 ",
		"/\\[iota\\]/"         =>  "\\uc0\\u953 ",
		"/\\[kappa\\]/"        =>  "\\uc0\\u954 ",
		"/\\[lambda\\]/"       =>  "\\uc0\\u955 ",
		"/\\[mu\\]/"           =>  "\\uc0\\u956 ",
		"/\\[nu\\]/"           =>  "\\uc0\\u957 ",
		"/\\[xi\\]/"           =>  "\\uc0\\u958 ",
		"/\\[omicron\\]/"      =>  "\\uc0\\u959 ",
		"/\\[pi\\]/"           =>  "\\uc0\\u960 ",
		"/\\[rho\\]/"          =>  "\\uc0\\u961 ",
		"/\\[sigmaf\\]/"       =>  "\\uc0\\u962 ",
		"/\\[sigma\\]/"        =>  "\\uc0\\u963 ",
		"/\\[tau\\]/"          =>  "\\uc0\\u964 ",
		"/\\[upsilon\\]/"      =>  "\\uc0\\u965 ",
		"/\\[phi\\]/"          =>  "\\uc0\\u966 ",
		"/\\[chi\\]/"          =>  "\\uc0\\u967 ",
		"/\\[psi\\]/"          =>  "\\uc0\\u968 ",
		"/\\[omega\\]/"        =>  "\\uc0\\u969 ",
		"/\\[Alpha\\]/"        =>  "\\uc0\\u913 ",
		"/\\[Beta\\]/"         =>  "\\uc0\\u914 ",
		"/\\[Gamma\\]/"        =>  "\\uc0\\u915 ",
		"/\\[Delta\\]/"        =>  "\\uc0\\u916 ",
		"/\\[Epsilon\\]/"      =>  "\\uc0\\u917 ",
		"/\\[Zeta\\]/"         =>  "\\uc0\\u918 ",
		"/\\[Eta\\]/"          =>  "\\uc0\\u919 ",
		"/\\[Theta\\]/"        =>  "\\uc0\\u920 ",
		"/\\[Iota\\]/"         =>  "\\uc0\\u921 ",
		"/\\[Kappa\\]/"        =>  "\\uc0\\u922 ",
		"/\\[Lambda\\]/"       =>  "\\uc0\\u923 ",
		"/\\[Mu\\]/"           =>  "\\uc0\\u924 ",
		"/\\[Nu\\]/"           =>  "\\uc0\\u925 ",
		"/\\[Xi\\]/"           =>  "\\uc0\\u926 ",
		"/\\[Omicron\\]/"      =>  "\\uc0\\u927 ",
		"/\\[Pi\\]/"           =>  "\\uc0\\u928 ",
		"/\\[Rho\\]/"          =>  "\\uc0\\u929 ",
		"/\\[Sigma\\]/"        =>  "\\uc0\\u931 ",
		"/\\[Tau\\]/"          =>  "\\uc0\\u932 ",
		"/\\[Upsilon\\]/"      =>  "\\uc0\\u933 ",
		"/\\[Phi\\]/"          =>  "\\uc0\\u934 ",
		"/\\[Chi\\]/"          =>  "\\uc0\\u935 ",
		"/\\[Psi\\]/"          =>  "\\uc0\\u936 ",
		"/\\[Omega\\]/"        =>  "\\uc0\\u937 ",
		"/([{}])/"             =>  '\\\\\\1', // escape curly brackets
		"/\"(.+?)\"/"          =>  "\\uc0\\u8220 \\1\\uc0\\u8221 ",
		"/ +- +/"              =>  " \\uc0\\u8211  ",
		"/�/"                  =>  "\\uc0\\u8211 "

	);

?>
