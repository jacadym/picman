<HTML>
<HEAD>
	<TITLE>Administrator :: Collections</TITLE>
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

div.thumbnails {
	overflow-x: auto !important;
	padding: 10px 8px;
	white-space: nowrap;
}
div.thumbnails img {
	padding: 0 2px;
	cursor: pointer;
}
img.thumb-sel {
	border: solid 2px red;
	padding: 4px !important;
	margin: 0 2px;
}

td span {
	white-space: nowrap;
}

//--></STYLE>
<script type="text/javascript" language="javascript">

function selImg(num) {
	document.getElementById('form-thumbimg').value = 'T:' + num;
	var children = document.body.getElementsByTagName('img');
	for (var i = 0, length = children.length; i < length; i++) {
    	child = children[i];
		child.className = 'thumb-img';
	}
	document.getElementById('img-' + num).className = 'thumb-sel';
}

</script>
</HEAD>

<BODY BGCOLOR="silver">
<DIV ALIGN=center>
<?php

echo AdminMenu();

if (isset($frm[action])) {

	switch (strtolower($frm[action])) {

	case 'mod' :
		// Ustalenie opcji
		$arr_opt = array();
		if (isset($frm[intro])) $arr_opt[] = 'HOME';
		if (isset($frm[options]) && !empty($frm[options])) $arr_opt[] = $frm[options];

		if (isset($frm[addbr]) && !empty($frm[desc])) {
			$out = '';
			foreach(split("\n", trim($frm[desc])) as $line) $out .= trim($line)."<BR>\n";
			$frm[desc] = $out;
		}

		if ($page_id) {
			// Sprawdzanie tagów
			$tags   = GetTags();
			$tagids = array();
			if (isset($frm['tags']) && count($frm['tags'])) {
				$tagids = $frm['tags'];
			}
			if (!empty($frm['newtags'])) {
				foreach (split(',', $frm['newtags']) as $_tag) {
					$tag = strtolower(trim($_tag));
					if (!in_array($tag, $tags)) {
						$id = db_next_id('seq_tag');
						db_query("INSERT INTO {tags} (id, name) VALUES (%d, '%s')", $id, $tag);
						$tags[$id] = $tag;
						array_push($tagids, $id);
					}
				}
			}
			db_query("DELETE FROM {tag_collections} WHERE id_col = %d", $page_id);
			if (count($tagids)) {
				db_query("INSERT INTO {tag_collections} (id_tag,id_col) SELECT id, %d FROM {tags} WHERE id IN (%s)",
					$page_id, join(',', $tagids)
				);
			}

			$cat = db_fetch_array(db_query("SELECT uniqid FROM {categories} WHERE id = %d", $frm['parent']));
			$result = db_query("
			UPDATE {collections} SET
			uid_cat     = '%s',
			uniqid      = '%s',
			weight      = %d,
			date_create = '%s',
			name        = '%s',
			title       = '%s',
			header      = '%s',
			description = '%s',
			startnum    = %d,
			quantity    = %d,
			holes       = '%s',
			coldir      = '%s',
			picsubdir   = '%s',
			thumbsubdir = '%s',
			pictemp     = '%s',
			thumbtemp   = '%s',
			pgnumtemp   = '%s',
			imgindex    = '%s',
			icoindex    = '%s',
			rows        = %d,
			cols        = %d,
			options     = '%s'
			WHERE id = %d",
				$cat['uniqid'],
				FrmDb($frm['uniqid']),
				$frm['position'],
				$frm['datecr'],
				FrmDb($frm['name']),
				FrmDb($frm['title']),
				FrmDb($frm['header']),
				FrmDb($frm['desc']),
				$frm['first'],
				$frm['quantity'],
				FrmDb($frm['holes']),
				FrmDb($frm['dirgr']),
				FrmDb($frm['dirimg']),
				FrmDb($frm['dirth']),
				FrmDb($frm['tempimg']),
				FrmDb($frm['tempth']),
				FrmDb($frm['temppage']),
				FrmDb($frm['introimg']),
				FrmDb($frm['thumbimg']),
				$frm['serrow'],
				$frm['sercol'],
				join('|',$arr_opt),
				$page_id
			);
			echo 
			FramedTable1().
			'<BR> &nbsp; OK! Modify Collection "'.$frm[name].'" &nbsp; <BR><BR>'.
			FramedTable2();
		}
		else {
			// Tworzenie nowej grupy
			$result = db_query(		
			"INSERT INTO {collections} ( ".
			"uid_cat,uniqid,".
			"weight,date_create,".
			"name,title,header,description,".
			"startnum,quantity,holes,".
			"coldir,picsubdir,thumbsubdir,".
			"pictemp,thumbtemp,pgnumtemp,".
			"imgindex,icoindex,".
			"rows,cols,options".
			") SELECT
			C.uniqid, '".FrmDb($frm[uniqid])."',
			$frm[position], '".$frm[datecr]."',
			'".FrmDb($frm[name])."', '".FrmDb($frm[title])."', '".FrmDb($frm[header])."', '".FrmDb($frm[desc])."',
			$frm[first], $frm[quantity], '".FrmDb($frm[holes])."',
			'".FrmDb($frm[dirgr])."', '".FrmDb($frm[dirimg])."', '".FrmDb($frm[dirth])."',
			'".FrmDb($frm[tempimg])."', '".FrmDb($frm[tempth])."', '".FrmDb($frm[temppage])."',
			'".FrmDb($frm[introimg])."', '".FrmDb($frm[thumbimg])."',
			$frm[serrow], $frm[sercol], '".join('|',$arr_opt)."'
			FROM {categories} C
			WHERE C.id = $frm[parent]"
			);
			echo 
			FramedTable1().
			'<BR> &nbsp; OK! Created new collection "'.$frm[name].'" &nbsp; <BR><BR>'.
			FramedTable2();
		}
	break;

	case 'del' :
			echo 
			FramedTable1().
			'<BR> &nbsp; Sorry! But delete is not ready yet! &nbsp; <BR><BR>'.
			FramedTable2();
	break;
	
	default:
		echo 
		FramedTable1().
		'<BR> &nbsp; Sorry! But incorrect action "'.$frm[action].'" &nbsp; <BR><BR>'.
		FramedTable2();
	break;
	
	}

}
else {
	DisplayCollectionForm();
}

function DisplayCollectionForm() {
	global $page_id, $frm;

	// Wartości domyślne
	if (!isset($frm)) $frm = array('sercol' => 5, 'serrow' => 4, 'datecr' => date('Y-m-d'));
	if ($page_id) {
		GetCollectionData($page_id);
	}
	$hier_cat = GetCategoriesHierarchy($frm[parent], $page_id, 1);

	// Odczytanie tagów	
	$frm['#tags'] = GetTags();
	SetCollectionTags($page_id, 'tags');

	echo 
	FramedTable1().
	'<FORM METHOD="POST">'.
	'<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=1>'.
	'<TR><TH> Collection </TH><TD></TD></TR>'.
	'<TR><TD></TD><TD ALIGN=right> '.
		FormSelect('action', array('mod' => 'Modify', 'del' => 'Delete')).
		' <INPUT TYPE="submit" VALUE="    Send    "> '.
	'</TD></TR>'.
	'<TR><TD> Category: </TD><TD> '.
		'<SELECT NAME="frm[parent]">'.$hier_cat.'</SELECT>'.
		' Position '.
		FormInput('position', 4, 1).
	' </TD></TR>'.
	'<TR><TD> Uniqid & Date: </TD><TD> '.FormInput('uniqid').' '.FormInput('datecr',12).' </TD></TR>'.
	'<TR><TD> Name: </TD><TD> '.FormInput('name').' </TD></TR>'.
	'<TR><TD> Title: </TD><TD> '.FormInput('title').' </TD></TR>'.
	'<TR><TD> Header: </TD><TD> '.FormInput('header').' </TD></TR>'.
	'<TR><TD> Description: </TD><TD> '. FormText('desc', 80, 5).'<BR>'.FormCheckbox('addbr').' Auto add "break line" </TD></TR>'.
	'<TR><TD COLSPAN=2><HR></TD></TR>'.
	'<TR><TD> Tags: </TD><TD> '.FormCheckarea('tags').' </TD></TR>'.
	'<TR><TD> New tags: </TD><TD> '.FormText('newtags', 80, 2).' </TD></TR>'.
	'<TR><TD COLSPAN=2><HR></TD></TR>'.
	'<TR><TD> Series: </TD><TD> '.
		FormSelect('sercol', range(1, 10), 0).
		' x '.
		FormSelect('serrow', range(1, 10), 0).
		' (columns x rows) '.
	'</TD></TR>'.
	'<TR><TD> Images: </TD><TD> First number '.FormInput('first', 4, 1).' Quantity '.FormInput('quantity', 4, 1).' </TD></TR>'.
	'<TR><TD> Number holes: </TD><TD> '.FormText('holes', 60, 2).' </TD></TR>'.
	'<TR><TD COLSPAN=2><HR></TD></TR>'.
	'<TR><TD> Collection directory: </TD><TD> '.FormInput('dirgr').' </TD></TR>'.
	'<TR><TD> Image directory: </TD><TD> '.FormInput('dirimg').' </TD></TR>'.
	'<TR><TD> Thumbnails directory: </TD><TD> '.FormInput('dirth').' </TD></TR>'.
	'<TR><TD COLSPAN=2><HR></TD></TR>'.
	'<TR><TD> Pages template: </TD><TD> '.FormInput('temppage', 50, '%02d').' </TD></TR>'.
	'<TR><TD> Images template: </TD><TD> '.FormInput('tempimg').' </TD></TR>'.
	'<TR><TD> Thumbnails template: </TD><TD> '.FormInput('tempth').' </TD></TR>'.
	'<TR><TD COLSPAN=2><HR></TD></TR>'.
	'<TR><TD> Options: </TD><TD> Links to '.
		FormSelect('options', array('' => 'None', 'LP' => 'Page', 'LI' => 'Image')).
		' '.
		FormCheckbox('intro').
		' Exist intro page '.
	'</TD></TR>'.
	'<TR><TD> Image on intro page: </TD><TD> '.FormInput('introimg').' </TD></TR>'.
	'<TR><TD> Thumbnail for group: </TD><TD> '.FormInput('thumbimg').' </TD></TR>'.
	'</TABLE>'.
	'</FORM>'.
	FramedTable2();

	if ($page_id) {
		DisplayThumbForm($page_id);
	}
}

function DisplayThumbForm($group_id) {

	$item    = db_fetch_array(db_query("SELECT * FROM {collections} WHERE id = $group_id"));
	$imgdir  = GetDirForCollection($group_id);
	$icondir = preg_replace('/[\/]+/', '/', PICMAN_IMAGE."$imgdir/".$item['thumbsubdir']."/");
	$content = '';

	$picholes = array();
	if (!empty($item['holes'])) {
		foreach(split(',', $item['holes']) as $range) {
			list($range_from, $range_to) = split('-', $range);
			if (isset($range_to) && $range_to) {
				for ($i = $range_from; $i <= $range_to; $i++) {
					$picholes[] = $i;
				}
			}
			else {
				$picholes[] = $range_from;
			}
		}
	}
	$ico       = split(':', $item['icoindex']);
	$img_add   = $item['startnum'] - 1;
	$mov_holes = 0;
	for ($inum = 1; $inum <= $item['quantity']; $inum++) {
		if (count($picholes)) while (in_array($inum + $img_add, $picholes)) $img_add++;
		$content .= sprintf('<img id="img-%d" src="%s" class="%s" onClick="selImg(\'%d\')" />',
			$inum,
			$icondir . sprintf($item['thumbtemp'], $inum + $img_add),
			(($ico[0] == 'T') && ($ico[1] == $inum)) ? 'thumb-sel' : 'thumb-img',
			$inum
		);
	}

	echo
	FramedTable1().
	'<div class="thumbnails">'.
	$content.
	'</div>'.
	FramedTable2();
}

?>
</DIV>
</BODY></HTML>
