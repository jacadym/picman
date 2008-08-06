<HTML>
<HEAD>
	<TITLE>Administrator :: Search</TITLE>
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

td span {
	white-space: nowrap;
}

//--></STYLE>
</HEAD>

<BODY BGCOLOR="silver">
<DIV ALIGN=center>
<?php

echo AdminMenu();
echo FramedTable1();

$result = db_query("SELECT * FROM {categories} WHERE id = $page_id");
if (db_num_rows($result)) {
	$item = db_fetch_array($result);

	$frm['#tags'] = GetTags();

	echo '<form method="post"><table border="0">';

	echo '<tr><td width="50"></td><td><b>'.$item['name'].'</b>:</td><td>';
	echo FormInput('search', 25).' <input type="submit" value="    Search    ">';
	echo '</td><td width="50"></td></tr>';

	echo '<tr><td width="50"></td><td>Tags: '.FormSelect('cond', array('and' => 'and', 'or' => 'or')).'</td><td>';
	echo FormCheckarea('tags');
	echo '</td><td width="50"></td></tr>';

	echo '</table></form>';

	$cond = array(
		"C.lnum BETWEEN $item[lnum] AND $item[rnum]",
		"P.id = C.id_parent",
		"C.uniqid = G.uid_cat"
	);
	if (!empty($frm['search'])) {
		$cond[] = "(upper(G.name) LIKE upper('%$frm[search]%') OR upper(G.description) LIKE upper('%$frm[search]%'))";
	}
	if (isset($frm['tags']) && count($frm['tags'])) {
		if ($frm['cond'] == 'or') {
			$cond[] = "G.id IN (SELECT id_col FROM {tag_collections} T WHERE id_tag IN (".join(',', $frm['tags'])."))";
		}
		else {
			foreach ($frm['tags'] as $tagid) {
				$cond[] = "G.id IN (SELECT id_col FROM {tag_collections} T WHERE id_tag = $tagid)";
			}
		}
	}
	
	if (count($cond) > 3) {
		$qid = db_query("
		SELECT DISTINCT
			C.id AS cid, C.name AS catname, C.catdir AS catdir, P.name AS parent,
			G.id AS id, G.name AS name, G.coldir AS coldir, G.icoindex AS icoindex,
			G.thumbsubdir AS thumbsubdir, G.thumbtemp AS thumbtemp,
			G.quantity AS quantity, G.date_create AS date_create
		FROM {collections} G, {categories} C, {categories} P
		WHERE ".join(" AND ", $cond)."
		ORDER BY G.date_create DESC
		");
		echo '<table border="0">';
		if (db_num_rows($qid) > 200) {
			echo '<tr><td>More then 200 results!</td></tr>';
		}
		else {
		$o = array();
		while ($out = db_fetch_array($qid)) {
			$o[] = $out;
			printf("\n<!-- %d:%d: %s -->\n", $out['cid'], $out['id'], $out['catname']);
		}
		foreach ($o as $out) {
			$icon   = GetDirForCat($out['cid']).$out['coldir'].'/';
			$icoarr = split(':', $out['icoindex']);
			if ($icoarr[0] == 'T') {
				$icon .= sprintf($out['thumbsubdir'].'/'.$out['thumbtemp'], $icoarr[1]);
			}
			else {
				$icon .= $out['icoindex'];
			}
			echo
			'<tr valign="top">'.
				'<td>'.
					'<a href="'.sprintf(PICMAN_COLLECTION."i%03d.html", $out['id']).'">'.
					'<img src="'.PICMAN_IMAGE.$icon.'" border="1" />'.
					'</a>'.
				'</td>'.
				'<td>'.
					'<b>'.$out['parent'].' / '.$out['catname'].'</b><br />'.
					'Name: '.$out['name'].'<br />'.
					'Pictures: '.$out['quantity'].'<br />'.
					'Date: '.date('Y-m-d', strtotime($out['date_create'])).'<br />'.
				'</td>'.
			'</tr>';
		}
		}
		echo '</table>';
	}

}
echo FramedTable2();

?>
</DIV>
</BODY></HTML>
