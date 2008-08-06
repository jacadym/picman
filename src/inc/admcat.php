<HTML>
<HEAD>
	<TITLE>Administrator :: Categories</TITLE>
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

if (isset($frm[action])) {

	switch (strtolower($frm[action])) {

	case 'mod' :

		$option_hidden = (isset($frm[cathid]) && $frm[cathid] ? 1 : 0);
		$parent_id     = isset($frm[parent]) ? $frm[parent] : 0;

		if (isset($frm[addbr]) && !empty($frm[desc])) {
			$out = '';
			foreach(split("\n", trim($frm[desc])) as $line) $out .= trim($line)."<BR>\n";
			$frm[desc] = $out;
		}

		if ($page_id) {
		
			$parent_lnum = $parent_rnum = 0;
			$oldparent_lnum = $oldparent_rnum = 0;
			$result = db_query("SELECT id_parent, lnum, rnum FROM {categories} WHERE id = $page_id");
			if (db_num_rows($result)) {
				$item = db_fetch_array($result);
				$oldparent_id = $item['id_parent'];
				$cat_lnum     = $item['lnum'];
				$cat_rnum     = $item['rnum'];
			}
			if ($oldparent_id != 0) {
				$result = db_query("SELECT lnum, rnum FROM {categories} WHERE id = $oldparent_id");
				if (db_num_rows($result)) {
					$item = db_fetch_array($result);
					$oldparent_lnum = $item['lnum'];
					$oldparent_rnum = $item['rnum'];
				}
			}

			if ($parent_id) {
				$result = db_query("SELECT lnum, rnum FROM {categories} WHERE id = $parent_id");
				if (db_num_rows($result)) {
					$item = db_fetch_array($result);
					$parent_lnum = $item['lnum'];
					$parent_rnum = $item['rnum'];
				}
			}
			if (($parent_lnum > $cat_lnum && $parent_rnum < $cat_rnum) || ($parent_id == $page_id)) {
				echo "ERROR!!! Podpięcie pod swojego potomka (lub pod siebie samego)!!!";
				$parent_id = $oldparent_id;
			}

			if ($parent_id != $oldparent_id) {
			
			$cat_items = ($cat_rnum - $cat_lnum) + 1;

			switch ($frm[inspos]) {
				case 1: // First in cat
					$new_lnum = ($cat_lnum < $parent_lnum) ? ($parent_lnum - $cat_items) + 1 : $parent_lnum + 1;
				break;
				case 2: // Last in cat
					$new_lnum = ($cat_rnum < $parent_rnum) ? $parent_rnum - $cat_items : $parent_rnum;
				break;
				default: // Sort
					$result = db_query_range("
					SELECT rnum + 1 AS new_rnum FROM categories
					WHERE id_parent = $parent_id AND lower(name) < lower('".FrmDb($frm[name])."')
					ORDER BY name DESC
					", 1, 0);
					if (db_num_rows($result)) {
						$item = db_fetch_array($result);
						$parent_rnum = $item['new_rnum'];
					}
					$new_lnum = ($cat_rnum < $parent_rnum) ? $parent_rnum - $cat_items : $parent_rnum;
				break;
			}
/**/
			db_query("BEGIN");
			// Zmniejszamy wartości lnum, rnum dla obiektu i jego potomków
			// Numeracja będzie zaczynała się od 0 dla lnum
			db_query("UPDATE {categories} SET lnum = lnum - $cat_lnum, rnum = rnum - $cat_lnum, mod_struct = 1 WHERE id IN (SELECT C.id FROM {categories} C, {categories} PARENT WHERE PARENT.id = $page_id AND (C.id = PARENT.id OR (C.lnum BETWEEN PARENT.lnum AND PARENT.rnum)))");

			// Przenumerowujemy wszystkie pozostałe obiekty (tak jakby nie było modyfikowanego)
			db_query("UPDATE {categories} SET lnum = lnum - $cat_items WHERE mod_struct = 0 AND lnum > $cat_lnum");
			db_query("UPDATE {categories} SET rnum = rnum - $cat_items WHERE mod_struct = 0 AND rnum > $cat_rnum");

			// Przesuwamy wszystkie numery aby można było wprowadzić modyfikowany obiekt
			db_query("UPDATE {categories} SET lnum = lnum + $cat_items WHERE mod_struct = 0 AND lnum >= $new_lnum");
			db_query("UPDATE {categories} SET rnum = rnum + $cat_items WHERE mod_struct = 0 AND rnum >= $new_lnum");

			// Wsztawiny w odpowiednie miejsce obiekt wraz z jego potomkami
			db_query("UPDATE {categories} SET lnum = lnum + $new_lnum, rnum = rnum + $new_lnum, mod_struct = 0 WHERE mod_struct = 1");
			db_query("COMMIT");
/**/
/**/
			echo 
			'<BR><BR>'.
			FramedTable1().
			'<BR> &nbsp; Wymagana jest zmiana struktury kategorii dla "'.$frm[name].'" &nbsp; <BR><BR>'.
			"OldParent :: ID:$oldparent_id ($oldparent_lnum,$oldparent_rnum)<BR>".
			"NewParent :: ID:$parent_id ($parent_lnum,$parent_rnum)<BR>".
			"Category :: ID:$page_id ($cat_lnum,$cat_rnum) ITEMS:$cat_items <BR>".
			"NEW LNUM :: $new_lnum MODE:$frm[inspos]".
			'<BR><BR>'.
			FramedTable2();
/**/
			}
/**/
			$result = db_query("
			UPDATE {categories} SET
			uniqid      = '".FrmDb($frm[uniqid])."',
			id_parent   = $parent_id,
			date_create = '".$frm[datecr]."',
			name        = '".FrmDb($frm[name])."',
			title       = '".FrmDb($frm[title])."',
			header      = '".FrmDb($frm[header])."',
			description = '".FrmDb($frm[desc])."',
			hidden      = $option_hidden,
			catdir      = '".FrmDb($frm[dircat])."',
			template    = '".FrmDb($frm[dirtem])."',
			catrows     = $frm[catrow],
			catcols     = $frm[catcol],
			colrows     = $frm[grrow],
			colcols     = $frm[grcol]
			WHERE id = $page_id
			");
/**/
			echo 
			'<BR><BR>'.
			FramedTable1().
			'<BR> &nbsp; OK! Modify category "'.$frm[name].'" &nbsp; <BR><BR>'.
			FramedTable2();

		} else { // Tworzenie nowej kategorii

			$parent_lnum = 0;
			$parent_rnum = 0;
			// Wyszukanie informacji o przodku
			$result = db_query("SELECT lnum, rnum FROM {categories} WHERE id = $parent_id");
			if (db_num_rows($result)) {
				$item = db_fetch_array($result);
				$parent_lnum = $item['lnum'];
				$parent_rnum = $item['rnum'];
			}
			switch ($frm[inspos]) {
				case 1: // First in cat
					$cat_lnum = $parent_lnum + 1;
				break;
				case 2: // Last in cat
					$cat_lnum = $parent_rnum;
					if ($parent_id == 0) {
						$result = db_query("SELECT max(rnum) AS max FROM categories");
						if (db_num_rows($result)) {
							$item = db_fetch_array($result);
							$cat_lnum = $item['max'] + 1;
						}
					}
				break;
				default: // Sort
					// Znalezienie największego ale mniejszego elementu od obecnie wstawianego
					$result = db_query_range("
					SELECT rnum FROM categories
					WHERE id_parent = $parent_id AND lower(name) < lower('".FrmDb($frm[name])."')
					ORDER BY name DESC
					", 1, 0);
					if (db_num_rows($result)) {
						$item = db_fetch_array($result);
						$cat_lnum = $item['rnum'] + 1;
					} else {
						// Nie znaleziono mniejszych więc jest pierwszy
						$cat_lnum = $parent_lnum + 1;
					}
				break;
			}
			$cat_rnum = $cat_lnum + 1;

/*
			echo 
			'<BR><BR>'.
			FramedTable1().
			"Parent :: ID:$parent_id ($parent_lnum,$parent_rnum)<BR>".
			"Category :: ID:$page_id ($cat_lnum,$cat_rnum)<BR>".
			"NEW :: MODE:$frm[inspos]".
			'<BR><BR>'.
			FramedTable2();
*/
			db_query("BEGIN");
			db_query("UPDATE {categories} SET lnum = lnum + 2 WHERE lnum >= $cat_lnum");
			db_query("UPDATE {categories} SET rnum = rnum + 2 WHERE rnum >= $cat_lnum");
			db_query("INSERT INTO {categories} (".
			"uniqid,id_parent,date_create,".
			"name,title,header,description,".
			"hidden,catdir,template,options,".
			"catrows,catcols,colrows,colcols,".
			"lnum, rnum) VALUES (
			'".FrmDb($frm[uniqid])."', $parent_id, '".$frm[datecr]."',
			'".FrmDb($frm[name])."', '".FrmDb($frm[title])."', '".FrmDb($frm[header])."','".FrmDb($frm[desc])."',
			$option_hidden, '".FrmDb($frm[dircat])."', '".FrmDb($frm[dirtem])."', '',
			$frm[catrow], $frm[catcol], $frm[grrow], $frm[grcol],
			$cat_lnum, $cat_rnum
			)");
			db_query("COMMIT");

			echo 
			'<BR><BR>'.
			FramedTable1().
			'<BR> &nbsp; OK! Created new category "'.$frm[name].'" &nbsp; <BR><BR>'.
			FramedTable2();
		
		}
	break;

	case 'del' :

		$result = db_query("SELECT uniqid, name, id_parent, lnum, rnum FROM {categories} WHERE id = $page_id");
		if (db_num_rows($result)) {
			$item     = db_fetch_array($result);
			$quantity = db_fetch_array(db_query("SELECT count(*) FROM {collections} WHERE uid_cat = '%s'", $item['uniqid']));
			if ($quantity['count'] > 0 || ($item['rnum'] - $item['lnum']) > 1) {
				echo 
				'<BR><BR>'.
				FramedTable1().
				'<BR> &nbsp; Sorry! Category is not empty! &nbsp; <BR><BR>'.
				FramedTable2();
			}
			else {

				db_query("BEGIN");
				db_query("UPDATE {categories} SET lnum = lnum - 2  WHERE lnum > %d", $item['rnum']);
				db_query("UPDATE {categories} SET rnum = rnum - 2  WHERE rnum > %d", $item['rnum']);
				db_query("DELETE FROM {categories}                 WHERE id = %d", $page_id);
				db_query("COMMIT");

				echo 
				'<BR><BR>'.
				FramedTable1().
				'<BR> &nbsp; OK! Category remove: "'.$item['name'].'"! &nbsp; <BR><BR>'.
				FramedTable2();
			}
		}
		else {
			echo 
			'<BR><BR>'.
			FramedTable1().
			'<BR> &nbsp; Sorry! But not found category to delete! &nbsp; <BR><BR>'.
			FramedTable2();
		}

	break;
	
	default:
		echo 
		'<BR><BR>'.
		FramedTable1().
		'<BR> &nbsp; Sorry! But incorrect action "'.$frm[action].'" &nbsp; <BR><BR>'.
		FramedTable2();
	break;
	
	}

} else {

	DisplayCategoryForm();

}

function DisplayCategoryForm() {
	global $page_id, $page_pg, $frm;

	// Wartości domyślne
	if (!isset($frm)) $frm = array('catcol' => 1, 'catrow' => 8, 'grcol'  => 4, 'grrow'  => 4, 'datecr' => date('Y-m-d'));
	if ($page_id) GetCategoryData($page_id);
	
	$cat_id   = $page_id;
	// Jeżeli chcemy dodać nową kategorię to miejsce gdzie jesteśmy wskazuje page_pg
	if (!$cat_id && $page_pg) $cat_id = $page_pg;
	$hier_cat = GetCategoriesHierarchy($frm[parent], $cat_id, 0);

	echo 
	FramedTable1().
	'<FORM METHOD="POST">'.
	'<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=1>'.
	'<TR><TH> Category </TH><TD> '.FormCheckbox('cathid').'Hidden </TD></TR>'.
	'<TR><TD></TD><TD ALIGN=right> '.
		FormSelect('action', array('mod' => 'Modify', 'del' => 'Delete', 'sort' => 'Sort')).
		' <INPUT TYPE=submit VALUE="    Send    "> '.
	'</TD></TR>'.
	'<TR><TD> Parent: </TD><TD> '.
		'<SELECT NAME="frm[parent]">'.$hier_cat.'</SELECT>'.
		FormSelect('inspos', array(1 => 'First', 2 => 'Last', 3 => 'Sort')).
	'</TD></TR>'.
	'<TR><TD> Uniqid & Date: </TD><TD> '.FormInput('uniqid').' '.FormInput('datecr',12).' </TD></TR>'.
	'<TR><TD> Name: </TD><TD> '.FormInput('name').' </TD></TR>'.
	'<TR><TD> Title: </TD><TD> '.FormInput('title').' </TD></TR>'.
	'<TR><TD> Header: </TD><TD> '.FormInput('header').' </TD></TR>'.
	'<TR><TD> Description: </TD><TD> '. FormText('desc', 80, 5).'<BR>'.FormCheckbox('addbr').' Auto add "break line" </TD></TR>'.
	'<TR><TD COLSPAN=2><HR></TD></TR>'.
	'<TR><TD> Subcats: </TD><TD> '.
		FormSelect('catcol', range(1, 10), 0).
		' x '.
		FormSelect('catrow', range(1, 20), 0).
		' (columns x rows) '.
	'</TD></TR>'.
	'<TR><TD> Collections: </TD><TD> '.
		FormSelect('grcol', range(1, 10), 0).
		' x '.
		FormSelect('grrow', range(1, 20), 0).
		' (columns x rows) '.
	'</TD></TR>'.
	'<TR><TD> Category directory: </TD><TD> '.FormInput('dircat').' </TD></TR>'.
	'<TR><TD> Template directory: </TD><TD> '.FormInput('dirtem', 40, PICMAN_DEFAULT_TEMP).' </TD></TR>'.
	'</TABLE>'.
	'</FORM>'.
	FramedTable2();
}

?>
</DIV>
</BODY></HTML>
