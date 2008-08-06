<?php

// -------------------------------------------------------------------------

$PICMAN_CONF = 'picman.cfg';
if (!is_file($PICMAN_CONF)) {
	echo "Not found CONF file ($PICMAN_CONF)!!!\n";
	exit;
}
include $PICMAN_CONF;
global  $PICMAN_INFO;

// -------------------------------------------------------------------------

function debug_info($level, $info, $message) {
	global $debug_level, $debug_file;
	static $init, $time, $uniq, $no = 1;

	if ($debug_level < $level || empty($debug_file)) {
		return;
	}
	if (!isset($init)) {
		$init = true;
		$uniq = uniqid('LOG_');
		$time = GetMicrotime();
		error_log(sprintf("\n\n%s :: %s :: %s\n", date('ymd H:i:s'), $uniq, $message), 3, $debug_file);
		return;
	}
	$newtime = GetMicrotime();
	error_log(
		sprintf("%s :: %s,%-2d :: %04.2f :: %8s :: %s\n", date('ymd H:i:s'), $uniq, $no++, ($newtime - $time), $info, $message),
		3,
		$debug_file
	);
	$time = $newtime;
}

function GetMicrotime() {
   list ($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}

function LoadTemplateFile($filename) {
	$txt = '';
	if (is_file($filename)) {
		debug_info(4, 'LoadFile', $filename);
		$txt = join('', file($filename));
	} else {
		echo "ERROR!!! Not found file $filename!!!<BR>";
	}
	return $txt;
}


function UpdateTemplate($text, $options) {
	$txt = $text;
	if (is_array($options)) {
		foreach($options as $name => $value) {
			$txt = preg_replace("|%%$name%%|i", $value, $txt);
		}
	}
	return $txt;
}

function ParsePage() {
	global $PATH_INFO;
	
	$page = 'main.php';
	if (!empty($PATH_INFO)) {
		if (preg_match("|\/([^/]*?)\/|", $PATH_INFO, $regs)) {
			$PAGE_IN_PATH = $regs[1];
			if (is_file(PICMAN_INCLUDE . $PAGE_IN_PATH . '.php')) {
				$page = $PAGE_IN_PATH . '.php';
			}
		}
	}
	return $page;
}

function SetPageParams() {
    global $PATH_INFO, $page_id, $page_pg;

    $page_id = 0;
    $page_pg = 0;
    if (preg_match("|i(\d+)|i", $PATH_INFO, $regs)) $page_id = 0 + $regs[1];
    if (preg_match("|p(\d+)|i", $PATH_INFO, $regs)) $page_pg = 0 + $regs[1];
}

function GetDirForCollection($id_group) {
	$link = db_fetch_array(db_query("SELECT uid_col FROM {links} WHERE id = $id_group"));
	if ($link['uid_col']) {
		$group = db_fetch_array(db_query("SELECT * FROM {collections} WHERE uniqid = '".$link['uid_col']."'"));
	}
	else {
		$group = db_fetch_array(db_query("SELECT * FROM {collections} WHERE id = $id_group"));
	}
	$result = db_query("
	SELECT DISTINCT P.id AS id, P.catdir AS catdir, P.lnum AS plnum
	FROM {categories} P, {categories} C
	WHERE C.uniqid = '%s' AND C.lnum BETWEEN P.lnum AND P.rnum
	ORDER BY plnum",
		$group['uid_cat']
	);
	if (db_num_rows($result)) {
		$dirs = array();
		while ($level = db_fetch_array($result)) {
			$dirs[] = $level['catdir'];
		}
		$dirs[] = $group['coldir'];
		return preg_replace('/[\/]+/', '/', join('/', $dirs).'/');
	}
}

function GetDirForCat($id_obj, $id_group = 0) {
	static $dir = array();
	
	if ($id_group) {
		$group  = db_fetch_array(db_query("SELECT * FROM {collections} WHERE id = $id_group"));
		$id_dir = 'G_'.$id_group;
		$sql = "
		SELECT DISTINCT P.id AS id, P.catdir AS catdir, P.lnum AS plnum
		FROM {categories} C, {categories} P
		WHERE C.uniqid = '".$group['uid_cat']."' AND C.lnum BETWEEN P.lnum AND P.rnum
		ORDER BY plnum
		";
	}
	else {
		$id_dir = 'C_'.$id_obj;
		$sql = "
		SELECT DISTINCT P.id AS id, P.catdir AS catdir, P.lnum AS plnum
		FROM {categories} C, {categories} P
		WHERE C.id = $id_obj AND C.lnum BETWEEN P.lnum AND P.rnum
		ORDER BY plnum
		";
	}
	if (!isset($dir[$id_dir])) {
		$result = db_query($sql);
		if (db_num_rows($result)) {
			$dirs = array();
			while ($level = db_fetch_array($result)) {
				$dirs[] = $level['catdir'];
			}
			$dir[$id_dir] = preg_replace('/[\/]+/', '/', join('/', $dirs).'/');
		}
	}
	return $dir[$id_dir];
}

function GetTopCatsForCat($id_obj) {
	$result = db_query("
	SELECT DISTINCT P.id AS id, P.name AS name, P.lnum AS plnum
	FROM {categories} P, {categories} C
	WHERE C.id = $id_obj AND C.lnum BETWEEN P.lnum AND P.rnum
	ORDER BY plnum
	");
	$txt = '<a href="'.PICMAN_INDEX.'">Category</a> :: ';
	if (db_num_rows($result)) {
		$cat_arr = array();
		while ($cat = db_fetch_array($result)) {
			if ($cat['id'] == $id_obj) {
				$cat_arr[] = '<b>'.$cat['name'].'</b>';
			}
			else {
				$cat_arr[] = sprintf('<a href="'.PICMAN_MAIN."i%03dp%03d.html\">%s</a>", $cat['id'], 1, $cat['name']);
			}
		}
		if (PICMAN_ADMINISTRATION) {
			$cat_arr[] = sprintf('<a href="'.PICMAN_ADMIN_CAT."i%03dp%03d.html\">[::]</a>", $id_obj, 1);
			$cat_arr[] = sprintf('<a href="'.PICMAN_ADMIN_SEARCH."i%03dp%03d.html\">[??]</a>", $id_obj, 1);
		}
		$txt .= join(' / ', $cat_arr);
	}
	return $txt;
}

function GetTopCatsForCollection($id_obj, $display = 0) {
	$txt = '<a href="'.PICMAN_INDEX.'">Category</a> :: ';

	$link = db_fetch_array(db_query("SELECT uid_col FROM {links} WHERE id = $id_obj"));
	if ($link['uid_col']) {
		$group = db_fetch_array(db_query("SELECT * FROM {collections} WHERE uniqid = '".$link['uid_col']."'"));
	}
	else {
		$group = db_fetch_array(db_query("SELECT * FROM {collections} WHERE id = $id_obj"));
	}
	$result = db_query("
	SELECT DISTINCT P.id AS id, P.name AS name, P.lnum AS plnum
	FROM {categories} P, {categories} C
	WHERE C.uniqid = '%s' AND C.lnum >= P.lnum AND C.lnum <= P.rnum
	ORDER BY plnum",
		$group['uid_cat']
	);
	if (db_num_rows($result)) {
		$cat_arr = array();
		while ($cat = db_fetch_array($result)) {
			$cat_arr[] = sprintf('<a href="'.PICMAN_MAIN."i%03dp%03d.html\">%s</a>", $cat['id'], 1, $cat['name']);
		}
		if ($display) {
			$cat_arr[] = sprintf('<a href="'.PICMAN_COLLECTION."i%03d.html\">%s</a>", $id_obj, $group['name']);
			$cat_arr[] = sprintf('<a href="'.PICMAN_COLLECTION."i%03dp%03d.html\">Page %d</a>", $id_obj, $display, $display);
		}
		else {
			$cat_arr[] = '<b>'.$group['name'].'</b>';
		}
		$cat_arr[] = sprintf('<a href="'.PICMAN_SLIDESHOW."i%03dp%03d.html\">[!!]</a>", $id_obj, $display ? $display : 1);
		if (PICMAN_ADMINISTRATION) {
			$cat_arr[] = sprintf('<a href="'.PICMAN_ADMIN_COLLECTION."i%03dp%03d.html\">[::]</a>", $id_obj, 1);
			if ($display) {
				$cat_arr[] = sprintf('<a href="'.PICMAN_ADMIN_THUMB."i%03dp%03d.html\">[&lt;&gt;]</a>", $id_obj, $display);
//			} else {
//				$cat_arr[] = sprintf('<a href="'.PICMAN_ADMIN_LINK."i%03dp%03d.html\">[&lt;&gt;]</a>", $id_obj, 1);
			}
		}
		$txt .= join(' / ', $cat_arr);
	}
	return $txt;
}

function GetGrpContent($id_cat, $item_txt, $txt_pre, $txt_post, $txt_prerow, $txt_postrow) {
	global $PICMAN_INFO, $page_pg, $num_total_pages;

	if (!$id_cat) return array('', '');
	
	$pagesel = '';

	$group  = db_fetch_array(db_query("SELECT colcols, colrows FROM {categories} WHERE id = $id_cat"));
	$perpage = $group['colcols'] * $group['colrows'];

	$ticodir = $PICMAN_INFO['dircat'];

	$result = db_query("
	SELECT DISTINCT
		G.id AS id, G.uniqid AS uniqid, G.name AS name, G.weight AS weight, G.quantity AS quantity, G.date_create AS dcreate,
		G.icoindex AS icoindex, G.coldir AS coldir, G.thumbsubdir AS thumbsubdir, G.thumbtemp AS thumbtemp, G.uid_cat AS uid_cat, 0 AS linkitem
	FROM {categories} C
		INNER JOIN {collections} G ON (C.uniqid = G.uid_cat)
	WHERE C.id = $id_cat
	
	UNION SELECT
		G.id AS id, G.uniqid AS uniqid, G.name AS name, L.weight AS weight, G.quantity AS quantity, G.date_create AS dcreate,
		G.icoindex AS icoindex, G.coldir AS coldir, G.thumbsubdir AS thumbsubdir, G.thumbtemp AS thumbtemp, G.uid_cat AS uid_cat, 1 AS linkitem
	FROM {categories} C
		INNER JOIN {links} L ON (C.uniqid = L.uid_cat)
		INNER JOIN {collections} G ON (L.uid_col = G.uniqid)
	WHERE C.id = $id_cat

	ORDER BY weight DESC, dcreate DESC, name
	");
	$txt = '';
	if ($num = db_num_rows($result)) {

		$page   = 1;
		$total  = (int)($num / $perpage);
		if ($num % $perpage) $total++;
		if (isset($page_pg) && $page_pg > 1) {
			$page     = $page_pg;
			if ($page > $total) $page = $total;
		}
		$startnum = (($page - 1) * $perpage);
		$maxnum   = ($page < $total ? $perpage : $num - (($total - 1) * $perpage));
		
		$num_total_pages = $total;

		$txt .= $txt_pre;

		$pgnumtemp = '%02d';
		if ($total > 1) {
			$pages_arr = array();
			for ($p = 1; $p <= $total; $p++) {
				if ($p == $page_pg) {
					$pages_arr[] = sprintf("<B>$pgnumtemp</B>", $p);
				}
				else {
					$pages_arr[] = sprintf('<a href="'.PICMAN_MAIN."i%03dp%03d.html\">$pgnumtemp</a>", $id_cat, $p, $p);
				}
			}
			$pagesel = join(' || ', $pages_arr);
		}
		$arr_collections = array();
		$arr_links  = array();
		db_seek($result, $startnum);
		for ($rec = $startnum; ($rec < $num) && ($rec < $startnum + $perpage); $rec++) {
			$grp_dir = '';
			$grp     = db_fetch_array($result);
			if (!empty($grp['icoindex'])) {
				$grp_dir = "$ticodir/".$grp['coldir'];
				$icoarr  = split(':', $grp['icoindex']);
				if ($icoarr[0] == 'T') {
					// Jako ikona będzie thumnbails
					$grp['icoindex'] = sprintf($grp['thumbsubdir']."/".$grp['thumbtemp'], $icoarr[1]);
				}
				if ($grp['linkitem']) $grp_dir = '';
			}
			$arr_collections[] = array(
				'id'     => $grp['id'],
				'name'   => $grp['name'],
				'count'  => $grp['quantity'],
				'icon'   => $grp['icoindex'],
				'dir'    => $grp_dir,
				'uniqid' => $grp['uniqid'],
				'date'   => date('Y-m-d', strtotime($grp['dcreate'])),
				'link'   => $grp['linkitem']
			);
			$arr_links[$grp['uniqid']] = $grp['uid_cat'];
		}

		$links_output = array();
		$qid_links = db_query("
		SELECT DISTINCT C.id AS id, C.name AS name, L.uid_col AS uid_col FROM {links} L
			INNER JOIN {categories} C ON (C.uniqid = L.uid_cat)
		WHERE
			(L.uid_cat IN ('".join("','", array_unique(array_values($arr_links)))."') OR L.uid_col IN ('".join("','", array_unique(array_keys($arr_links)))."'))
			AND C.id != $id_cat

		UNION SELECT C.id AS id, C.name AS name, G.uniqid AS uid_col FROM {collections} G
			INNER JOIN {categories} C ON (C.uniqid = G.uid_cat)
		WHERE
			(G.uid_cat IN ('".join("','", array_unique(array_values($arr_links)))."') OR G.uniqid IN ('".join("','", array_unique(array_keys($arr_links)))."'))
			AND C.id != $id_cat
		");
		if ($num_links = db_num_rows($qid_links)) {
			while ($pos = db_fetch_array($qid_links, $idx++)) {
				$links_output[$pos['uid_col']][$pos['id']] = $pos['name'];
			}
		}

//		echo '<pre>';
//		print_r($links_output);
//		echo '</pre>';

		$snum = 1;
		for ($r = 1; $r <= $group['colrows'] && $snum <= $maxnum; $r++) {
			$txt .= $txt_prerow;
			for ($c = 1; $c <= $group['colcols'] && $snum <= $maxnum; $c++) {
				$gr = $arr_collections[$snum - 1];

				$icon_thumb  = ($gr['link'] ? GetDirForCollection($gr['id']).'/' : '');
				$icon_thumb .= $gr['dir'].'/'.$gr['icon'];

				echo "\n<!-- Pic[$snum] : $icon_thumb --- ".$gr['dir']." :: ".$gr['icon']." -->\n";
				
				$icon = preg_replace('/[\/]+/', '/',
					(is_file(PICMAN_IMAGE_DIR.$icon_thumb) ?
						PICMAN_IMAGE.$icon_thumb
						:
						DEFAULT_COLLECTION_ICON
					)
				);
				$others = '';
				if (isset($links_output[$gr['uniqid']])) {
					foreach($links_output[$gr['uniqid']] as $catid => $catname) {
						$others .= sprintf('<a href="%si%03dp%03d.html" title="%s">&raquo;</a> ', PICMAN_MAIN, $catid, 1, $catname);
					}
				}
				if (PICMAN_ADMINISTRATION == 1) {
					$others .= sprintf('<a href="%si%03dp%03d.html" title="%s">[::]</a> ', PICMAN_ADMIN_COLLECTION, $gr['id'], 1, $catname);
				}
				
				$txt .= UpdateTemplate($item_txt, array(
					'href'  => sprintf(PICMAN_COLLECTION."i%03d.html", $gr['id']),
					'name'  => $gr['name'],
					'count' => ($gr['count'] ? $gr['count'] : ''),
					'icon'  => $icon,
					'dateupdate' => $gr['date'],
					'others' => $others
				));
				$snum++;
			}
			$txt .= $txt_postrow;
		}
		$txt .= $txt_post;
	}
	return array($txt, $pagesel);
}

function GetCatContent($id_cat, $item_txt, $txt_pre, $txt_post, $txt_prerow, $txt_postrow) {
	$cat = db_fetch_array(db_query("SELECT catcols, catrows FROM {categories} WHERE id = $id_cat"));
	if ($cat['catcols'] == 0 || $cat['catrows'] == 0) {
		$cat['catcols'] = 1;
		$cat['catrows'] = 8;
	}
	$perpage = $cat['catcols'] * $cat['catrows'];

/*
		(SELECT count(*) FROM {collections} G WHERE G.uid_cat = C.uniqid) AS grcount,
		(SELECT count(*) FROM {links} L WHERE L.uid_cat = C.uniqid) AS lncount,
		(SELECT max(G.date_create) FROM {collections} G WHERE G.uid_cat = C.uniqid) AS dcreate
*/

	$result = db_query("
	SELECT
		C.id AS id, C.name AS name, C.lnum AS lnum
	FROM {categories} C
	WHERE C.id_parent = $id_cat AND C.hidden = 0
	ORDER BY lnum
	");
	$txt = '';
	if ($num = db_num_rows($result)) {
		$txt .= $txt_pre;
		for ($rec = 0; $rec < $num; $rec++) {
			$item  = db_fetch_array($result);
			$count = $item['grcount'] + $item['lncount'];
			if (($rec % $cat['catcols']) == 0) {
				$txt .= $txt_prerow;
			}
			$txt .= UpdateTemplate($item_txt, array(
				'href'  => sprintf(PICMAN_MAIN."i%03dp%03d.html", $item['id'], 1),
				'name'  => $item['name'],
				'count' => ($count ? $count : ''),
				'date'  => (!empty($item['dcreate']) ? date('Y-m-d', strtotime($item['dcreate'])) : '')
			));
			if ((($rec % $cat['catcols']) == ($cat['catcols'] - 1)) || ($rec == $num - 1)) {
				$txt .= $txt_postrow;
			}
		}
		$txt .= $txt_post;
	}
	return $txt;
}


function FramedTable1() {
	return '<div class="frame">';
}

function FramedTable2() {
	return '</div>';
}

function AdminMenu() {
	global $page_id;
	return
	FramedTable1().
'<table><TR>
<TD CLASS="menu"> <a href="'.PICMAN_INDEX.'" CLASS="menu">Index</a> </TD>
<TH CLASS="menu"> Category: </TH><TD CLASS="menu"> '.
	sprintf('<a href="'.PICMAN_ADMIN_CAT.'i%03dp%03d.html"', 0, $page_id).
	' CLASS="menu">New</a>'.
	' :: '.
	'<a href="" CLASS="menu">Modify</a> :: <a href="" CLASS="menu">Delete</a> </TD>
<TH CLASS="menu"> Collection: </TH><TD CLASS="menu"> <a href="'.PICMAN_ADMIN_COLLECTION.'" CLASS="menu">New</a> :: <a href="" CLASS="menu">Modify</a> :: <a href="" CLASS="menu">Delete</a> </TD>
<TH CLASS="menu"> Link: </TH><TD CLASS="menu"> <a href="'.PICMAN_ADMIN_LINK.'" CLASS="menu">New</a> :: <a href="" CLASS="menu">Delete</a> </TD>
</TR></table>'.
	FramedTable2();
}

// ---- FORMULARZE ------------------------------------------------

function FrmTxt($form_text) {
	return htmlspecialchars(stripslashes(trim($form_text)));
}

function FrmDb($form_text) {
	return preg_replace("/'/", "''", stripslashes(trim($form_text)));
}

function FormHidden($name, $default = '') {
	global $frm;
	return '<input type="hidden" id="form-'.$name.'" name="frm['.$name.']" value="'.(isset($frm[$name]) ? FrmTxt($frm[$name]) : $default).'">';
}

function FormInput($name, $size = 50, $default = '') {
	global $frm;
	return '<input type="text" id="form-'.$name.'" name="frm['.$name.']" size="'.$size.'" value="'.(isset($frm[$name]) ? FrmTxt($frm[$name]) : $default).'">';
}

function FormSelect($name, $options = array(), $hash = 1) {
	global $frm;

	if (count($options) == 0) return '';
	if ($hash == 1) {
		foreach ($options as $key => $val)
		$txt .= '<option value="'.$key.'"'.(isset($frm[$name]) && $frm[$name] == $key ? ' selected="selected"' : '').'> '.$val.'</option>';
	} else {
		foreach ($options as $key)
		$txt .= '<option value="'.$key.'"'.(isset($frm[$name]) && $frm[$name] == $key ? ' selected="selected"' : '').'> '.$key.'</option>';
	}

	return '<select id="form-'.$name.'" name="frm['.$name.']">'.$txt.'</select>';
}

function FormCheckbox($name) {
	global $frm;
	return '<input type="checkbox" id="form-'.$name.'" name="frm['.$name.']" value="1"'.(isset($frm[$name]) && $frm[$name] ? ' checked="checked"' : '').'> ';
}

function FormCheckarea($name) {
	global $frm;
	$temp = '<span><input type="checkbox" name="frm[%s][]" value="%s" %s /> %s</span> ';
	$opt  = $frm['#'.$name];
	$val  = $frm[$name];
	$txt  = '';
	if (is_array($opt) && count($opt)) {
		foreach ($opt as $key => $label) {
			$txt .= sprintf($temp, $name, $key, ((is_array($val) ? in_array($key, $val) : ($val == $key)) ? 'checked="checked"' : ''), $label);
		}
	}
	return $txt;
}

function FormText($name, $cols = 50, $rows = 4) {
	global $frm;
	return '<textarea id="form-'.$name.'" name="frm['.$name.']" cols="'.$cols.'" rows="'.$rows.'" wrap="physical">'.FrmTxt($frm[$name]).'</textarea>';
}


function GetCategoryData($id) {
	global $frm;
	
	$result = db_query("SELECT * FROM categories WHERE id = $id");
	if (db_num_rows($result)) {
		$cat = db_fetch_array($result);
		$frm[uniqid] = $cat['uniqid'];
		$frm[parent] = $cat['id_parent'];
		$frm[datecr] = date('Y-m-d', strtotime($cat['date_create']));
		$frm[name]   = $cat['name'];
		$frm[title]  = $cat['title'];
		$frm[header] = $cat['header'];
		$frm[desc]   = $cat['description'];
		$frm[dircat] = $cat['catdir'];
		$frm[dirtem] = $cat['template'];
		$sql_options = $cat['options'];
		$frm[catrow] = $cat['catrows'];
		$frm[catcol] = $cat['catcols'];
		$frm[grrow]  = $cat['colrows'];
		$frm[grcol]  = $cat['colcols'];
		if (!empty($cat['hidden']) && $cat['hidden']) $frm[cathid] = 1;
	}
}

function GetLinkData($id) {
	global $frm;

	$result = db_query("
	SELECT C.id AS cid, L.weight AS weight, G.id AS gid, G.name AS name
	FROM {links} L
		INNER JOIN {collections} G ON (L.uid_col = G.uniqid)
		INNER JOIN {categories} C ON (L.uid_cat = C.uniqid)
	WHERE L.id = $id
	");
	if (db_num_rows($result)) {
		$link = db_fetch_array($result);
		$frm[parent]   = $link['cid'];
		$frm[position] = $link['weight'];
		$frm[idgr]     = $link['gid'];
		$frm[name]     = $link['name'];
	}
}

function GetCollectionData($id) {
	global $frm;
	
	$result = db_query("
	SELECT
		G.uniqid AS uniqid, C.id AS cid, G.weight AS weight, G.date_create AS date_create,
		G.name AS name, G.title AS title, G.header AS header, G.description AS description,
		G.startnum AS startnum, G.quantity AS quantity, G.holes AS holes,
		G.coldir AS coldir, G.picsubdir AS picsubdir, G.thumbsubdir AS thumbsubdir,
		G.pictemp AS pictemp, G.thumbtemp AS thumbtemp, G.pgnumtemp AS pgnumtemp,
		G.imgindex AS imgindex, G.icoindex AS icoindex,
		G.rows AS rows, G.cols AS cols, G.options AS options
	FROM {collections} G
		INNER JOIN {categories} C ON (G.uid_cat = C.uniqid)
	WHERE G.id = $id
	");
	if (db_num_rows($result)) {
		$cat = db_fetch_array($result);
		$frm[uniqid]   = $cat['uniqid'];
		$frm[parent]   = $cat['cid'];
		$frm[position] = $cat['weight'];
		$frm[datecr]   = date('Y-m-d', strtotime($cat['date_create']));
		$frm[name]     = $cat['name'];
		$frm[title]    = $cat['title'];
		$frm[header]   = $cat['header'];
		$frm[desc]     = $cat['description'];
		$frm[first]    = $cat['startnum'];
		$frm[quantity] = $cat['quantity'];
		$frm[holes]    = $cat['holes'];
		$frm[dirgr]    = $cat['coldir'];
		$frm[dirimg]   = $cat['picsubdir'];
		$frm[dirth]    = $cat['thumbsubdir'];
		$frm[tempimg]  = $cat['pictemp'];
		$frm[tempth]   = $cat['thumbtemp'];
		$frm[temppage] = $cat['pgnumtemp'];
		$frm[introimg] = $cat['imgindex'];
		$frm[thumbimg] = $cat['icoindex'];
		$frm[serrow]   = $cat['rows'];
		$frm[sercol]   = $cat['cols'];
		$sql_options   = $cat['options'];

		$arr_options = split('\|', $sql_options);
		if (in_array('HOME', $arr_options)) $frm[intro] = 1;
		if (in_array('LP', $arr_options)) {
			$frm[options] = 'LP';
		} elseif (in_array('LI', $arr_options)) {
			$frm[options] = 'LI';
		}
	}
}

function GetTags($id = null) {
	$result = db_query("
	SELECT T.id AS id, T.name AS name, T.weight AS weight
	FROM {tags} T
		INNER JOIN {tag_collections} TC ON (TC.id_tag = T.id)
		INNER JOIN {collections} C ON (C.id = TC.id_col)
	ORDER BY T.weight DESC, T.name
	");
	$tags = array();
	while ($tag = db_fetch_array($result)) {
		$tags[$tag['id']] = $tag['name'];
	}
	return $tags;
}

function SetCollectionTags($id, $name) {
	global $frm;
	$result = db_query("
	SELECT T.id AS id, T.name AS name
	FROM {tags} T
		INNER JOIN {tag_collections} TC ON (TC.id_tag = T.id)
		INNER JOIN {collections} C ON (C.id = TC.id_col)
	WHERE C.id = %d
	", $id);
	while ($tag = db_fetch_array($result)) {
		$frm[$name][] = $tag['id'];
	}
}

// Type: 0 - category, 1 - group, 2 - link
function GetCategoriesHierarchy($default = 0, $id = 0, $type = 1) {
	if ($default == 0 && $id == 0) {
		$result = db_query("
		SELECT id, name, lnum, rnum, hidden
		FROM categories
		WHERE NOT id IN (SELECT C.id FROM {categories} C, {categories} P WHERE P.hidden = 1 AND C.lnum BETWEEN P.lnum AND P.rnum)
		ORDER BY lnum
		");
	}
	else {
		$result = db_query("
		SELECT C.id AS id, C.name AS name, C.lnum AS lnum, C.rnum AS rnum, C.hidden AS hidden
		FROM {categories} C, {categories} A".($type == 2 ? ", {categories} L" : '')."
		WHERE
		".($id != 0 && $type == 0 ? 
			"A.id = $id AND C.id != $id" 
			:
			($type == 2 ? 
				"(L.id = $default AND A.id_parent = 0 AND A.lnum < L.lnum AND A.rnum > L.rnum)"
				:
				"A.id = $default"
			)
		)." AND (
			C.id_parent = 0
			OR C.id_parent = A.id_parent
			OR C.id IN (456,852,172,173,174,175,176,177,178,356,14108)
			OR (C.lnum <= A.lnum AND C.rnum >= A.rnum)
			".($type == 1 || $type == 2 ? "OR (C.lnum BETWEEN A.lnum AND A.rnum)" : '')."
		)
		ORDER BY C.lnum
		");
	}
	if ($num = db_num_rows($result)) {
		$tree = new TreeDraw();
		$tree->default = $default;
		$add_num = $default == 0 || $id == 0 ? 1 : 0;
		$max_num = 0;
		while ($cat = db_fetch_array($result)) {
			if ($cat['hidden']) $cat['name'] = '(*) '.$cat['name'];
			if ($cat['rnum'] > $max_num) $max_num = $cat['rnum'];
			$tree->AddNode($cat['lnum'] + $add_num, array($cat['rnum'] + $add_num, NTTREE_DOT, $cat['id'], $cat['name']));
		}
		if ($default == 0 || $id == 0) $tree->AddNode(1, array($max_num + 1, NTTREE_DOT, 0, ''));
		return $tree->GetOptions();
	}
	return '';
}

// -------------------------------------------------------------------------

SetPageParams();

$PICMAN_INFO = array (
	'dircat' => GetDirForCat($page_id),
	'dirgrp' => ''
);

include PICMAN_INCLUDE . ParsePage();

// -------------------------------------------------------------------------

db_close();


class TreeDraw {

	var $content;

	/*
	* Czy ma rysować kropkę-root
	*/
	var $draw_root;

	/*
	* For draw:
	*   array ( lnum => array (rnum, image, auth, text, subnodes), ... )
	* For options:
	*   array ( lnum => array (rnum, image, id, text, subnodes), ... )
	*/
	var $nodes;
	var $max_rnum;

	/*
	* Wartość domyślna dla options
	*/
	var $default;

	// czy posiada jakiś potomków
	//  -> dla poprawnego rysowania subnodes
	var $has_children;

	/*
	* Like nodes but set by DrawNodes
	*/
	var $subnode;

	var $images;
	var $image_align;

	/**
	 * function TreeDraw
	 *
	 * @param  integer $nodes
	 * @return integer
	 */
	function TreeDraw($nodes = "") {
		$this->ClearData();
		if (is_array($nodes)) {
			$this->nodes = $nodes;
		}
	}

	/**
	 * function ClearData
	 *
	 * @return integer
	 */
	function ClearData() {
		$this->content      = '';
		$this->draw_root    = 1;
		$this->nodes        = array ();
		$this->max_rnum     = 0;
		$this->default      = 0;
		$this->has_children = 0;

		$this->subnode      = 0;

		$this->images = array (
			NTTREE_DOT		  => NTWEB_IMAGE.'tree_dot.gif',
			NTTREE_FILE		  => NTWEB_IMAGE.'tree_file.gif',
			NTTREE_DIR		  => NTWEB_IMAGE.'tree_dir.gif',
			NTTREE_TEAM		  => NTWEB_IMAGE.'tree_team.gif',
			NTTREE_PROJ		  => NTWEB_IMAGE.'tree_proj.gif',
			NTTREE_COLLECTION => NTWEB_IMAGE.'tree_file.gif',
			NTTREE_USER		  => NTWEB_IMAGE.'tree_user.gif',
			NTTREE_BOOKUSER	  => NTWEB_IMAGE.'tree_bookuser.gif',
			NTTREE_DELUSER	  => NTWEB_IMAGE.'tree_deluser.gif',
		);
		$this->image_align  = 'absmiddle';
	}

	/**
	 * function AddNode
	 *
	 * @param  integer $lnum
	 * @param  integer $date
	 * @return integer
	 */
	function AddNode($lnum, $date) {
		if (is_array($date)) {
			$this->nodes[$lnum] = $date;
			if ($date[0] > $this->max_rnum) $this->max_rnum = $date[0];
			return 1;
		}
		return 0;
	}

	/**
	 * function GetOptions
	 *
	 * @return integer
	 */
	function GetOptions() {
		// Czy jest ustawione max_rnum, jeżeli nie to wyszukanie
		if (!$this->max_rnum) {
			foreach ($this->nodes as $lnum => $date) {
				$rnum = $date[0];
				if ($rnum > $this->max_rnum) {
					$this->max_rnum = $rnum;
				}
			}
		}
		$lnum = 1;
		while ($lnum < $this->max_rnum) {
			$rnum = $this->GetNodesOption($lnum);
			if ($rnum) $lnum = $rnum;
			$lnum++;
		}
		return $this->content;
	}

	/**
	 * function GetNodesOption
	 *
	 * @param  integer $lnum
	 * @param  integer $level
	 * @return integer
	 */
	function GetNodesOption($lnum = 1, $level = 0) {
		if (!isset($this->nodes[$lnum])) return 0;

		$rnum     = $this->nodes[$lnum][0];
		$id       = $this->nodes[$lnum][2];
		$text     = $this->nodes[$lnum][3];

		$children = ($rnum - $lnum - 1) / 2;

		$this->content .=
			'<option value="'.$id.'"'.
			(($this->default && $this->default == $id) ? " SELECTED> " : "> ");
		if ($level) {
			for ($sp = 1; $sp <= $level; $sp++) {
			$this->content .= '--';
			}
		}
		$this->content .= " $text</option>";

		if ($children) {
			$child_rnum = 0;
			// Posiada potomków -> wywołanie rekurencyjne
			while ($lnum < $rnum) {
			$child_lnum = $lnum + 1;
			if (isset($this->nodes[$child_lnum])) {
				$child_rnum = $this->GetNodesOption($child_lnum, $level + 1);
				$lnum = $child_rnum - 1;
			}
			$lnum++;
			}
		}
		return $rnum;
	}

	/**
	 * function Draw
	 *
	 * @return integer
	 */
	function Draw() {
		// Czy jest ustaione max_rnum, jeżeli nie to wyszukanie
		if (!$this->max_rnum) {
			foreach ($this->nodes as $lnum => $date) {
				$rnum = $date[0];
				if ($rnum > $this->max_rnum) {
					$this->max_rnum = $rnum;
				}
			}
		}
		$lnum = 1;
		while ($lnum < $this->max_rnum) {
			$rnum = $this->DrawNodes($lnum);
			if ($rnum) $lnum = $rnum;
			$lnum++;
		}
		return $this->content;
	}

	/**
	 * function DrawNodes
	 *
	 * @param  integer $lnum
	 * @param  integer $parent_rnum
	 * @param  integer $before
	 * @return integer
	 */
	function DrawNodes($lnum = 1, $parent_rnum = 0, $before = array () ) {
		if (!isset($this->nodes[$lnum])) return 0;

		$rnum     = $this->nodes[$lnum][0];
		$image    = $this->nodes[$lnum][1];
		$auth_img = $this->nodes[$lnum][2];
		$text     = $this->nodes[$lnum][3];

		$num      = count($before);
		$children = ($rnum - $lnum - 1) / 2;

		$this->subnode      = 0;
		$this->has_children = $children;

		// Jeżeli jest na szczycie
		if ($num == 0) {
			$this->DrawBefore(array("dot"));
			$before = array("space");
		}
		elseif ($rnum + 1 == $parent_rnum) {
			if ($before[$num - 1] == "middle") {
				$before[$num - 1] = "end";
				$this->DrawBefore($before);
				$before[$num - 1] = "space";
			}
		}
		else {
			$this->DrawBefore($before);
			$before[$num - 1] = "line";
		}
		$before[] = "middle";

		// Narysowanie ilustracji i tekstu
		$this->DrawImage($image);
		$this->DrawAuthImage($auth_img);
		$this->content .= " $text</nobr><br clear=\"ALL\">\n";
		// Jeżeli są jakieś inne elementy podczepione
		if (is_array($this->nodes[$lnum][4])) {
			$this->subnode = $this->nodes[$lnum][4];
			$this->DrawSubNodes(1, 0, $before);
		}

		if ($children) {
			$child_rnum = 0;
			// Posiada potomków -> wywołanie rekurencyjne
			while ($lnum < $rnum) {
				$child_lnum = $lnum + 1;
				if (isset($this->nodes[$child_lnum])) {
					$child_rnum = $this->DrawNodes($child_lnum, $rnum, $before);
					$lnum = $child_rnum - 1;
				}
				$lnum++;
			}
			// Występują jeszcze jakieś elementy (ale brak do nich uprawnień)
			if ($child_rnum + 1 != $rnum) {
				$num = count($before);
				if ($before[$num - 1] == "middle") {
					$before[$num - 1] = "end";
				}
				$before[] = "dots";
				$this->DrawBefore($before);
				$this->content .= "</nobr><br clear=\"ALL\">\n";
			}
		}
		return $rnum;
	}

	/**
	 *  Wyświetlenie wszystkich potomków wyświetlenie tych o numerach od 1 do
	 * rnum 
	 *
	 * @param  integer $lnum
	 * @param  integer $parent_rnum
	 * @param  integer $before
	 * @param  integer $level
	 * @return integer
	 */
	function DrawSubNodes($lnum = 1, $parent_rnum = 0, $before = array (), $level = 0 ) {
		if (!$this->subnode || !isset($this->subnode[$lnum])) return 0;

		$rnum     = $this->subnode[$lnum][0];
		$image    = $this->subnode[$lnum][1];
		$auth_img = $this->subnode[$lnum][2];
		$text     = $this->subnode[$lnum][3];

		$num      = count($before);
		$children = ($rnum - $lnum - 1) / 2;


		// Jeżeli jest na szczycie
		if ($parent_rnum == 0) {
			// Brak wyświetlenia pierwszego - głównego
		}
		elseif ($rnum + 1 == $parent_rnum) {
			if ($before[$num - 1] == "middle") {
				if ($level == 1 && $this->has_children) {
					$this->DrawBefore($before);
					$before[$num - 1] = "line";
				}
				else {
					$before[$num - 1] = "end";
					$this->DrawBefore($before);
					$before[$num - 1] = "space";
				}
				$before[] = "middle";
			}
		}
		else {
			$this->DrawBefore($before);
			$before[$num - 1] = "line";
			$before[] = "middle";
		}

		// Narysowanie ilustracji i tekstu
		if ($parent_rnum) {
			$this->DrawImage($image);
			$this->DrawAuthImage($auth_img);
			$this->content .= " $text</nobr><br clear=\"ALL\">\n";
		}

		if ($children) {
			// Posiada potomków -> wywołanie rekurencyjne
			while ($lnum < $rnum) {
				$child_lnum = $lnum + 1;
				if (isset($this->subnode[$child_lnum])) {
					$child_rnum = $this->DrawSubNodes($child_lnum, $rnum, $before, $level + 1);
					$lnum = $child_rnum - 1;
				}
				$lnum++;
			}
		}
		return $rnum;
	}

	/**
	 * function DrawBefore
	 *
	 * @param  integer $before
	 * @return integer
	 */
	function DrawBefore($before = array () ) {
	$this->content .= "<nobr>";
	foreach ($before as $item) {
		switch ($item) {
		case "dot"   :
			if ($this->draw_root)
				$this->content .= '<img src="'.$this->images[NTTREE_DOT].'" width=18 height=18 hspace=0 vspace=0 border=0 align="'.$this->image_align.'" alt="">';
			break;
		case "line"  :
		case "middle":
		case "end"   :
		case "dots"  :
		case "space" :
			$this->content .= '<img src="'.NTWEB_IMAGE."tree_$item".'.gif" width=18 height=18 hspace=0 vspace=0 border=0 align="'.$this->image_align.'" alt="">';
			break;
		}
	}
	}

	/**
	 * function DrawAuthImage
	 *
	 * @param  integer $idx
	 * @return integer
	 */
	function DrawAuthImage($idx) {
		$images = array (
			1 => 'auth_private',
			2 => 'auth_authorize',
			3 => 'auth_team'
		);
		if ($idx) {
		// Narysowanie odpowiednich ikon
		$this->content .= '<img src="'.NTWEB_IMAGE.$images[$idx].'.gif" width=18 height=18 hspace=0 vspace=0 border=0 align="'.$this->image_align.'" alt="">';
		}
	}

	/**
	 * function DrawImage
	 *
	 * @param  integer $idx
	 * @return integer
	 */
	function DrawImage($idx) {
		if ($idx >= 1 && $idx <= NTTREE_DELUSER) {
			$this->content .= '<img src="'.$this->images[$idx].'" width=18 height=18 hspace=0 vspace=0 border=0 align="'.$this->image_align.'" alt="">';
		}
	}

}

?>
