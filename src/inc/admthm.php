<HTML>
<HEAD>
	<TITLE>Administrator :: Default Icon (Thumbnail)</TITLE>
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

	$link = db_fetch_array(db_query("SELECT uid_col FROM {links} WHERE id = $page_id"));
	if ($link['uid_col']) {
		$item = db_fetch_array(db_query("SELECT * FROM {collections} WHERE uniqid = '".$link['uid_col']."'"));
	}
	else {
		$item = db_fetch_array(db_query("SELECT * FROM {collections} WHERE id = $page_id"));
	}
	$temp = db_fetch_array(db_query("
	SELECT P.template AS template
	FROM {categories} P, {categories} C
	WHERE C.uniqid = '%s' AND P.lnum <= C.lnum AND P.rnum >= C.rnum AND P.template != ''
	ORDER BY P.lnum DESC
	LIMIT 1",
		$item['uid_cat']
	));

	$imgdir  = GetDirForCollection($page_id);
	$icondir = preg_replace('/[\/]+/', '/',  PICMAN_IMAGE."$imgdir/".$item['thumbsubdir']."/");

	$picholes = array();
	if (!empty($item['holes'])) {
		foreach(split(',', $item['holes']) as $range) {
			list($range_from, $range_to) = split('-', $range);
			if (isset($range_to) && $range_to) {
				for ($i = $range_from; $i <= $range_to; $i++) $picholes[] = $i;
			} else {
				$picholes[] = $range_from;
			}
		}
	}


	if ($page_pg > $item['quantity']) $page_pg = $item['quantity'];
	$picperpage  = $item['rows'] * $item['cols'];
	$page_num    = (int) ($page_pg / $picperpage);
	if ($page_pg % $picperpage) $page_num++;

	$mov_holes = 0;
	// Dodanie numerów których nie ma
	if (count($picholes)) {
		$iter = 1;
		$nr   = $page_pg;
		while ($iter <= $nr) {
			if (in_array($iter + $item['startnum'] - 1, $picholes)) {
				$mov_holes++;
				$nr++;
			}
			$iter++;
			
		}
	}

	$icon_num  = $page_pg + $mov_holes + $item['startnum'] - 1;
	$icon_name = sprintf($item['thumbtemp'], $icon_num);
	$icon_add  = $icondir . $icon_name;

echo 
	FramedTable1().
	'<TABLE BORDER=0><TR VALIGN=top>'.
	'<TD><IMG SRC="'.$icon_add.'"></TD>'.
	'<TD> Set Default icon (thumbnail) for group<BR>'.
		'<LI>Icon order: '.$page_pg.
		'<LI>Icon number: '.$icon_num.
		'<LI>Icon name: '.$icon_name.
	'</TD>'.
	'</TR></TABLE>'.
	FramedTable2();

$result = db_query("UPDATE {collections} SET icoindex = 'T:%s' WHERE id = %s", $icon_num, $page_id);

?>
</DIV>
</BODY></HTML>
