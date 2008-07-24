<HTML>
<HEAD>
	<TITLE>Administrator :: Links</TITLE>
<STYLE TYPE="text/css"><!--

body {
	font-family: arial,helvetica,sans-serif;
	font-size: 10pt;
}
a {
	font-family: arial,helvetica,sans-serif;
	text-decoration: none;
}
a.menu {
	font-weight: bold;
	color: #6699cc;
	font-size: 8pt;
}

td {
	font-size: 10pt;
}
th {
	font-size: 14pt;
	font-weight: bold;
	color: #000000;
	text-align: left;
}
td.menu, th.menu {
	font-size: 10pt;
	text-align: left;
	color: #000000;
}
div.frame {
	background: white;
	border: solid 1px black;
	width: 700px;
}

//--></STYLE>
</HEAD>

<BODY BGCOLOR="silver">
<DIV ALIGN=center>
<?php

echo AdminMenu();

// Wartości domyślne
if (!isset($frm)) $frm = array('catcol' => 1, 'catrow' => 8, 'grcol'  => 4, 'grrow'  => 4);
//if ($page_id) GetLinkData($page_id);
if ($page_id) {
	$result = db_query("SELECT G.id AS id, G.name AS name, C.id AS cid FROM {groups} G, {categories} C WHERE G.id = $page_id AND G.uid_cat = C.uniqid");
	if (db_num_rows($result)) {
		$item = db_fetch_array($result);
		$frm[idgr]   = $item['id'];
		$frm[name]   = $item['name'];
		$frm[parent] = $item['cid'];
	}
}
$hier_cat = GetCategoriesHierarchy($frm[parent], 0, 2);

echo 
	'<BR><BR>'.
	FramedTable1().
	'<FORM METHOD="POST">'.
	'<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=1>'.
	'<TR><TH> New Link </TH><TD> </TD></TR>'.
	'<TR><TD></TD><TD ALIGN=right> '.
		' <INPUT TYPE=submit VALUE="    Send    "> '.
	'</TD></TR>'.
	'<TR><TD> Group name: </TD><TD> '.
		$frm[name].
		FormHidden('idgr').
	' </TD></TR>'.
	'<TR><TD> Category: </TD><TD> '.
		'<SELECT NAME="frm[parent]">'.$hier_cat.'</SELECT>'.
		' Position '.
		FormInput('position', 4, 1).
	' </TD></TR>'.
	'</TABLE>'.
	'</FORM>'.
	FramedTable2();


?>
</DIV>
</BODY></HTML>
