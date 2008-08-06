<?php

$TemplateDir = PICMAN_DEFAULT_TEMP;
$imgdir      = GetDirForCollection($page_id);

$prev_href = '';
$prev_name = '';
$next_href = '';
$next_name = '';

if (!empty($imgdir)) {
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

	$picholes = array();
	if (!empty($item['holes'])) {
		foreach(split(',', $item['holes']) as $range) {
			list($range_from, $range_to) = split('-', $range);
			if (isset($range_to) && $range_to) {
				for ($i = $range_from; $i <= $range_to; $i++) {
					$picholes[] = $i;
				}
			} else {
				$picholes[] = $range_from;
			}
		}
	}

	if (is_dir(PICMAN_TEMPLATE_DIR . $temp['template'])) {
		$TemplateDir = $temp['template'];
	}
	$group_temp = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'group.html');
	$icon_temp  = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'group_icon.html');

	$indeximg = (empty($item['imgindex']) ? DEFAULT_INDEX_IMAGE : preg_replace('/[\/]+/', '/',  PICMAN_IMAGE.$imgdir."/".$item['imgindex']));

	$group_options = split('\|', $item['options']);

	$picperpage  = $item['rows'] * $item['cols'];
	$total_pages = (int) ($item['quantity'] / $picperpage);
	if ($item['quantity'] % $picperpage) $total_pages++;
	if ($page_pg < 0) $page_pg = 0;
	if ($page_pg > $total_pages) $page_pg = $total_pages;

	$is_page_index = 0;
	if ($page_pg == 0 && in_array('HOME', $group_options)) {
		$is_page_index = 1;
		$group_temp    = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'.'group_index.html');
		$next_href     = sprintf(PICMAN_COLLECTION."i%03dp%03d.html", $page_id, 1);
		$next_name     = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'group_next.html');
	}
	else {
		if ($page_pg < 1) $page_pg = 1;
	}

	if ($page_pg && $page_pg < $total_pages) {
		$next_href = sprintf(PICMAN_COLLECTION."i%03dp%03d.html", $page_id, $page_pg + 1);
		$next_name = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'group_next.html');
	}
	if ($page_pg && $page_pg > 1) {
		$prev_href = sprintf(PICMAN_COLLECTION."i%03dp%03d.html", $page_id, $page_pg - 1);
		$prev_name = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'group_prev.html');
	}

	$pagesel = '';
	if ($total_pages > 1) {
		$pages_arr = array();
		for ($p = 1; $p <= $total_pages; $p++) {
			if ($p == $page_pg) {
				$pages_arr[] = sprintf('<b>'.$item['pgnumtemp'].'</b>', $p);
			}
			else {
				$pages_arr[] = sprintf('<a href="'.PICMAN_COLLECTION.'i%03dp%03d.html">'.$item['pgnumtemp'].'</a>', $page_id, $p, $p);
			}
		}
		$pagesel = join(' || ', $pages_arr);
	}

	$content = '';
	if (!$is_page_index) {
		$icondir = preg_replace('/[\/]+/', '/', PICMAN_IMAGE."$imgdir/".$item['thumbsubdir']."/");
		$absidir = preg_replace('/[\/]+/', '/', PICMAN_IMAGE_DIR."$imgdir/".$item['thumbsubdir']."/");
		$picdir  = preg_replace('/[\/]+/', '/', PICMAN_IMAGE."$imgdir/".$item['picsubdir']."/");

		if (in_array('LP', $group_options)) {
			$href = sprintf('<a href="'.PICMAN_PICTURE."i%03dp%%03d.html\">", $page_id);
		}
		elseif (in_array('LI', $group_options)) {
			$href = '<a href="'.$picdir.$item['pictemp'].'">';
		}
		else {
			$href = '<!-- %d -->';
		}

		$img_add   = $item['startnum'] - 1;
		$inum      = (($page_pg - 1) * $picperpage) + 1;
		$mov_holes = 0;
		// Dodanie numerów których nie ma
		if ($page_pg > 1 && count($picholes)) {
			$iter = 1;
			$nr   = $inum;
			while ($iter <= $nr) {
				if (in_array($iter + $img_add, $picholes)) {
					$mov_holes++;
					$nr++;
				}
				$iter++;
			}
		}

		for ($r = 1; $r <= $item['rows'] && $inum <= $item['quantity']; $r++) {
			$content .= '<tr valign="middle">';
			for ($c = 1; $c <= $item['cols'] && $inum <= $item['quantity']; $c++) {
				if (count($picholes)) while (in_array($inum + $img_add + $mov_holes, $picholes)) $img_add++;
				$icon_num   = $inum + $img_add + $mov_holes;
				$href_num   = (in_array('LI', $group_options) ? $icon_num : $inum);
				$icon_thumb = sprintf($item['thumbtemp'], $icon_num);
				$content .= UpdateTemplate($icon_temp, array(
					'href' => sprintf($href, $href_num),
//					'icon' => (is_file($absidir.$icon_thumb) ? $icondir.$icon_thumb : DEFAULT_THUMB_ICON.$icondir.$icon_thumb)
					'icon' => (is_file($absidir.$icon_thumb) ? $icondir.$icon_thumb : DEFAULT_THUMB_ICON)
				));
				$inum++;
			}
			$content .= '</tr>'."\n";
		}
	}
	else {
		$content  = 'Jesteśmy na stronie index';
	}
	
}

$catdir = PICMAN_IMAGE . GetDirForCat(0, $page_id);
$grpdir = $catdir . '/' . $item['coldir'] . '/';

echo UpdateTemplate($group_temp, array(
	'prevhref'   => $prev_href,
	'prevname'   => $prev_name,
	'nexthref'   => $next_href,
	'nextname'   => $next_name,
	'title'      => $item['title'],
	'header'     => $item['header'],
	'desc'       => $item['description'],
	'cols'       => $item['cols'],
	'rows'       => $item['rows'],
	'dateupdate' => date('Y-m-d', strtotime($item['date_create'])),
	'quantity'   => $item['quantity'],
	'indeximg'   => $indeximg,
	'topcat'     => GetTopCatsForCollection($page_id, $page_num),
	'pagesel'    => $pagesel,
	'content'    => $content,
	'imgdir'     => PICMAN_TEMPLATE_IMG . $TemplateDir,
	'catdir'     => $catdir,
	'grpdir'     => $grpdir
));

?>
