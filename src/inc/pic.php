<?php

$TemplateDir = PICMAN_DEFAULT_TEMP;

$imgdir = GetDirForCollection($page_id);

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
				for ($i = $range_from; $i <= $range_to; $i++) $picholes[] = $i;
			} else {
				$picholes[] = $range_from;
			}
		}
	}

	if (is_dir(PICMAN_TEMPLATE_DIR . $temp['template'])) {
		$TemplateDir = $temp['template'];
	}

	$pic_temp = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir . '/' . 'pic.html');

	if ($page_pg > $item['quantity']) $page_pg = $item['quantity'];
	$picperpage  = $item['rows'] * $item['cols'];
	$page_num    = (int) ($page_pg / $picperpage);
	if ($page_pg % $picperpage) $page_num++;

	if ($page_pg && ($page_pg < $item['quantity'])) {
		$next_href = sprintf(PICMAN_PICTURE."i%03dp%03d.html", $page_id, $page_pg + 1);
		$next_name = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir . '/' . 'group_next.html');
	} elseif ($page_pg == $item['quantity']) {
		$next_href = sprintf(PICMAN_PICTURE."i%03dp%03d.html", $page_id, $page_pg);
	}
	if ($page_pg && ($page_pg > 1)) {
		$prev_href = sprintf(PICMAN_PICTURE."i%03dp%03d.html", $page_id, $page_pg - 1);
		$prev_name = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir . '/' . 'group_prev.html');
	}

	$picdir = preg_replace('/[\/]+/', '/', PICMAN_IMAGE."$imgdir/".$item['picsubdir']."/");

#	echo "[$item['holes']]<BR>";

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

//	echo "<BR>";
//	echo "$mov_holes - $page_pg -- $item['startnum'] -- ".($page_pg + $mov_holes + ($item['startnum'] - 1))."<BR>\n";
	
	$content = sprintf($picdir . $item['pictemp'], $page_pg + $mov_holes + $item['startnum'] - 1);


}

echo UpdateTemplate($pic_temp, array(
	'title'    => "Picture $page_pg of ".$item['quantity'],
	'cols'     => $item['cols'],
	'rows'     => $item['rows'],
	'quantity' => $item['quantity'],
	'picnr'    => $page_pg,
	'topcat'   => GetTopCatsForCollection($page_id, $page_pg),
	'prevhref' => $prev_href,
	'prevname' => $prev_name,
	'nexthref' => $next_href,
	'nextname' => $next_name,
	'image'    => $content,
	'imgdir'   => PICMAN_TEMPLATE_IMG . $TemplateDir

));

?>
