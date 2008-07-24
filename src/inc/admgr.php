<HTML>
<HEAD>
	<TITLE>Administrator :: Groups</TITLE>
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
			$cat = db_fetch_array(db_query("SELECT uniqid FROM {categories} WHERE id = %d", $frm['parent']));
			$result = db_query("
			UPDATE {groups} SET
			uid_cat     = '".$cat['uniqid']."',
			uniqid      = '".FrmDb($frm[uniqid])."',
			onum        = $frm[position],
			date_create = '".$frm[datecr]."',
			name        = '".FrmDb($frm[name])."',
			title       = '".FrmDb($frm[title])."',
			header      = '".FrmDb($frm[header])."',
			description = '".FrmDb($frm[desc])."',
			startnum    = $frm[first],
			quantity    = $frm[quantity],
			holes       = '".FrmDb($frm[holes])."',
			grdir       = '".FrmDb($frm[dirgr])."',
			picsubdir   = '".FrmDb($frm[dirimg])."',
			thumbsubdir = '".FrmDb($frm[dirth])."',
			pictemp     = '".FrmDb($frm[tempimg])."',
			thumbtemp   = '".FrmDb($frm[tempth])."',
			pgnumtemp   = '".FrmDb($frm[temppage])."',
			imgindex    = '".FrmDb($frm[introimg])."',
			icoindex    = '".FrmDb($frm[thumbimg])."',
			rows        = $frm[serrow],
			cols        = $frm[sercol],
			options     = '".join('|',$arr_opt)."'
			WHERE id = $page_id
			");
			echo 
			FramedTable1().
			'<BR> &nbsp; OK! Modify group "'.$frm[name].'" &nbsp; <BR><BR>'.
			FramedTable2();
		}
		else {
			// Tworzenie nowej grupy
			$result = db_query(		
			"INSERT INTO {groups} ( ".
			"uid_cat,uniqid,".
			"onum,date_create,".
			"name,title,header,description,".
			"startnum,quantity,holes,".
			"grdir,picsubdir,thumbsubdir,".
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
			'<BR> &nbsp; OK! Created new group "'.$frm[name].'" &nbsp; <BR><BR>'.
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
	DisplayGroupForm();
}

function DisplayGroupForm() {
	global $page_id, $frm;

	// Wartości domyślne
	if (!isset($frm)) $frm = array('sercol' => 5, 'serrow' => 4, 'datecr' => date('Y-m-d'));
	if ($page_id) {
		GetGroupData($page_id);
	}
	$hier_cat = GetCategoriesHierarchy($frm[parent], $page_id, 1);

	echo 
	FramedTable1().
	'<FORM METHOD="POST">'.
	'<TABLE BORDER=0 CELLSPACING=1 CELLPADDING=1>'.
	'<TR><TH> Group </TH><TD></TD></TR>'.
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
	'<TR><TD> Series: </TD><TD> '.
		FormSelect('sercol', range(1, 10), 0).
		' x '.
		FormSelect('serrow', range(1, 10), 0).
		' (columns x rows) '.
	'</TD></TR>'.
	'<TR><TD> Images: </TD><TD> First number '.FormInput('first', 4, 1).' Quantity '.FormInput('quantity', 4, 1).' </TD></TR>'.
	'<TR><TD> Number holes: </TD><TD> '.FormText('holes', 60, 2).' </TD></TR>'.
	'<TR><TD COLSPAN=2><HR></TD></TR>'.
	'<TR><TD> Group directory: </TD><TD> '.FormInput('dirgr').' </TD></TR>'.
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

	$item    = db_fetch_array(db_query("SELECT * FROM {groups} WHERE id = $group_id"));
	$imgdir  = GetDirForGroup($group_id);
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
