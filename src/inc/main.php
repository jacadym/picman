<?php

// -------------------------------------------------------------------------

$TemplateDir     = PICMAN_DEFAULT_TEMP;
$num_total_pages = 1;

$prev_page_href  = '';
$prev_page_text  = '';

$next_page_href  = '';
$next_page_text  = '';

$temp = db_fetch_array(db_query("
SELECT P.template AS template
FROM {categories} P, {categories} C
WHERE C.id = $page_id AND P.lnum <= C.lnum AND P.rnum >= C.rnum AND P.template != ''
ORDER BY P.lnum DESC
LIMIT 1
"));
if (!empty($temp['template']) && is_dir(PICMAN_TEMPLATE_DIR . $temp['template'])) {
	$TemplateDir = $temp['template'];
}

$cat = db_fetch_array(db_query("SELECT description FROM {categories} WHERE id = $page_id"));

$group_temp  = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat.html');
$content_cat = GetCatContent($page_id,
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemcat.html'),
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemcat_pre.html'),
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemcat_post.html'),
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemcat_prerow.html'),
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemcat_postrow.html')
);
list ($content_grp, $pagesel_grp) = GetGrpContent($page_id,
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemgr.html'),
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemgr_pre.html'),
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemgr_post.html'),
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemgr_prerow.html'),
	LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'cat_itemgr_postrow.html')
);

if ($num_total_pages > 1) {
	if ($page_pg && $page_pg < $num_total_pages) {
		$next_page_href  = sprintf(PICMAN_MAIN."i%03dp%03d.html", $page_id, $page_pg + 1);
		$next_page_text  = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'group_next.html');
	}
	if ($page_pg && $page_pg > 1) {
		$prev_page_href  = sprintf(PICMAN_MAIN."i%03dp%03d.html", $page_id, $page_pg - 1);
		$prev_page_text  = LoadTemplateFile(PICMAN_TEMPLATE_DIR . $TemplateDir .'/'. 'group_prev.html');
	}
}

echo UpdateTemplate($group_temp, array(
	'title'      => '',
	'topcat'     => GetTopCatsForCat($page_id),
	'prevhref'   => $prev_page_href,
	'prevname'   => $prev_page_text,
	'nexthref'   => $next_page_href,
	'nextname'   => $next_page_text,
	'categories' =>	$content_cat,
	'collections'=> (empty($content_grp) ? '<img src="'.DEFAULT_INDEX_IMAGE.'" width=200 height=10 />' : $content_grp),
	'pagesel'    => $pagesel_grp,
	'desc'       => $cat['description'],
	'imgdir'     => PICMAN_TEMPLATE_IMG . $TemplateDir,
	'catdir'     => PICMAN_IMAGE . $PICMAN_INFO['dircat']
));

?>
