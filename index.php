<?php
/**
 * Index New File.
 *
 * @file-name index_new.php
 * @author    Pete Gupta <pete@ucertify.com>
 * @version   1.0.0
 * @package   pe-gold3
 */


$data['timer_not_allowed'] = 0;
$show_search       = 1;
require_once 'prepengine-header.php';
if (isNewWebsite()) {
	include_once 'web/index.php';
	die();
}
require_once SITE_ABSPATH . '/courses/courses.functions.php';


// @TODO: Why we are setting it.
$merge_config->callModule('assignDefault');
$data['org_id'] = $org_detail[0]['org_id'];

$action         = trim(strip_all_tags_uc(strtolower(empty($data['action']) ? $data['useraction'] : $data['action'])));
$func           = trim(strip_all_tags_uc(strtolower($data['func'])));
trackIndexFuncLog($func, $action);

// @Deepika: do not remove this: need to load bootstrap4 on home page on mobile too.
$theme->template_dir = SITE_THEME_DIR . 'bootstrap4';

setThemeOptions($theme_options);

//@pete: we should remove it its only use for start.ucertify.com
if ($merge_config->getThemeValue('home_page_url')) {
	$home_page = $merge_config->getThemeValue('home_page_url');
	include_once $home_page;
	exit();
}

if ($is_mobile <> 1) {
	include_once LIB_DIR . 'array.courses.php';
}

if (isset($is_mobile) && $is_mobile == 1) {
	include_once LIB_DIR . 'array.courses.mobile.php';
	$vendor_list       = get_all_vendors(false, 1, 1, 1);
	$all_certification = get_all_certifications();
	asort($vendor_list);
	$theme->assign('all_certification', @json_encode_uc($all_certification));
	$theme->assign('home_side_pane', 1);
	$theme->assign('side_pane', 1);
}
$theme_options['show_search']               = $show_search;
$theme_options['show_shopmenu']             = 1;
$theme_options['show_cartmenu']             = 1;
$theme_options['bottom_bar']                = 1;
$theme_options['show_chat_window']          = 1;
$theme_options['show_chat_inwebsite_pages'] = 1;
setThemeOptions($theme_options);
$theme->assign('image_slider', $image_slider);
$theme->assign('assets', $assets);
$theme->assign('top_courses', $top_courses);
$theme->assign('vendor_list', $vendor_list);
$theme->assign('page_menu', true);
$theme->assign('data', $data);
$theme->assign('browse_title', $browse_title);
$theme->assign('tbsol_titles_group', $tbsol_titles_group);
$theme->assign('trusted_partner', $trusted_partner);
// Here hide_isbn means you have to show isbn if value will be 1 and 0 to show.
$theme->assign('hide_isbn', $merge_config->getThemeValue('pearson_hide_isbn'));
//@TODO: Get products from config crns
if (is_array($merge_config->getAllConfig['crns']) && count_uc($merge_config->getAllConfig['crns']) > 0) {
	$org_id = ORG_ID; //phpcs:ignore
	$course_status = 'c,u';
	$where  = array();
	//@TODO: Get products from config crns
	if ($merge_config->getValue('crns')) {
		$where['crn'] = implode(',', $merge_config->getValue('crns'));
	}
	$where['limit']           = 0;
	$where['orderby']         = 'crn';
	$where['effective_price'] = $effective_price;
	$where['columns']         = 'price,discount,status,course_name,color,isbn,crn,course_tag,course_code,type,start_date,vendor,product_licence_available,picture,components,page_uri';
	//@TODO: Get products from config; check this function
	$where['org_id'] = ORG_ID; //this will get the products json from org config and change course data accordingly
	if ($merge_config->getValue('org_name') == 'Pearson') {
		$where['content_from_org_id'] = ORG_ID;
	}
	
	$raw_data = courseListGet($course_status, '*>=0', 'k,c,n', false, 1, $where);

	foreach ($raw_data as $r) {
		$r['licence_available'] = byte2array($r['product_licence_available'], $_product_licence_byte);
		$course_components      = expandCourseComponent($r['components']);
		if (isset($course_components['l1']) && $course_components['l1']['vl'] > 0) {
			$r['lab1_virtual_lab'] = 1;
		}
		$r['optional_license']       = getOptionalLicense($r['licence_available']);
		$home_page_data[ $r['crn'] ] = $r;
	}
	/*
	$get_currency = get_default_currency();
	//@TODO: Get products from config
	if ( isset( $merge_config->getAllConfig['products'] ) && is_array( $merge_config->getAllConfig['products'] ) ) {
		foreach ( $merge_config->getAllConfig['products'] as $k => $p ) {
			$p['price']                        = round( $p['price'] * $get_currency['cratio'], 2 );
			$org_price[ $k ]                   = $p;
			$home_page_data[ $k ]['publisher'] = $p['publisher'];
		}
	}*/
	if ($merge_config->getValue('allow_course_sorting') == 1) {
		//@TODO: Get products from config crns
		foreach ($merge_config->getAllConfig['crns'] as $k => $value) {
			$home_page_data_new[ $value ] = $home_page_data[ $value ];
		}
		$home_page_data = $home_page_data_new;
	}
	$theme->assign('price', $merge_config->getAllConfig['products']);
} else {
	$home_page_data = get_top_products();
}
$where_testimonial           = array();
$where_testimonial['limit']  = 0;
$where_testimonial['status'] = '2';

$theme->assign('testimonials_home', getTestimonials($where_testimonial));
$vendors = array(); // venders list for current org.

if (is_array($home_page_data)) {
	foreach ($home_page_data as $k) {
		if (! in_array($k['vendor'], $vendors) && $k['vendor'] != '') {
			$vendors[] = $k['vendor'];
		}
	}
}
if (isset($vendors) && $vendors != '') {
	$theme->assign('vendors', $vendors);
}
if (isset($home_page_data) && $home_page_data != '') {
	$theme->assign('home_page_data', $home_page_data);
}
$home_page_footer = $merge_config->getAllConfig['theme_options']['home_page_footer'];
if (isset($home_page_footer) && $home_page_footer == '0') {
	$theme->assign('bottom_bar', 0);
}
if ($merge_config->getThemeValue('custom_top_titles') == 1) {
	$top_titles = array_slice(array_merge_uc($home_page_data, $top_titles), 0, 30);
}
$theme->assign('show_deal', $show_deal);
$theme->assign('donot_include_globaljs', 'prepengine_footer_reduced');
$theme->assign('donot_include_globalcss', 'certification_page');
$theme->assign('donot_include_globalcss_mob', 'home_page');
$theme->assign('donot_include_globaljs_mob', 'prepengine-footer_reduced');
$theme->assign('product_license_icon', $_product_license_img);
$theme->assign('product_license_hint', $_product_licence);


// second condition is querystring to check in ucertify.
if ($show_deal == 1 || ( $data['show_deal'] && $data['show_deal'] == 1 )) {
	include_once 'deal.php';
	$theme->assign('deal_data', $deal_data);
}

$tagCourseList = getTagCoursesDetails('05fTF,05ftC,05ftc,05ftb,05nCP');
foreach ($tagCourseList as $v) {
	$index                                     = $v['crn'];
	$tagCourseList[ $v['tag_guid'] ][ $index ] = $v;
}
$theme->assign('coding_course_details', $tagCourseList['05fTF']);
//$theme->assign( 'vocational_course_details', $tagCourseList['05ftC'] );
$theme->assign('security_course_details', $tagCourseList['05nCP']);
$theme->assign('proj_mgt_course_details', $tagCourseList['05ftc']);
$theme->assign('it_cs_course_details', $tagCourseList['05ftb']);

$theme->assign('product_licence_img', $_product_license_img);
$theme->assign('product_licence', $_product_licence);
$theme->assign('testimonials', $testimonials);
$theme->assign('top_titles', $top_titles);
$theme->assign('show_testimonial_image', 1);
$theme->assign('host_id', $merge_config->getValue('site_id'));
$theme->assign('hide_search', $merge_config->getThemeValue('pearson_hide_search'));
$home_txt = $merge_config->getValue('home_text');
if ($home_txt) {
	$home_txt = itemContentGet($home_txt);
	$home_txt = json_decode_uc($home_txt[ $home_txt ]['content_text'], true);
	$theme->assign('home_txt', nl2br($home_txt['content']));
}
$data['func']  = 'homepage';
$template_name = 'coverpage_new.tpl';

if (! empty($merge_config->getAllConfig['bootcamp_products'])) {
	$productlist     = $merge_config->getAllConfig['bootcamp_products']['products'];
	$productlist     = createLearningCurveGroupProductList($productlist);
	$productCategory = $merge_config->getAllConfig['bootcamp_products']['category'];
	$theme->assign("productcategory", $productCategory);
	$theme->assign("productlist", $productlist);
}

showpage($theme->fetch($template_name));


/**
 * This function is used for Get Tag Cousrse Details.
 *
 * @param  array $course_guid Course Guid.
 * @return array
 */
function getTagCoursesDetails($course_guid)
{
	$_product_licence_byte = getGlobalArray('_product_licence_byte');

	$where_coding                  = array();
	$where_coding['limit']         = 0;
	$where_coding['settings']      = 1;
	$where_coding['tag_guid']      = $course_guid;
	$where_coding['course_status'] = 'c,u';
	$where_coding['columns']       = 'eg.group_guid as tag_guid,c.course_code,c.crn,c.course_name,c.color,c.picture,c.type,c.product_licence_available,c.price,c.isbn,page_uri,visiblity';
	$raw_data_coding               = getAPIDataJ('cat2.catalog_course_for_tag', $where_coding);
	$course_new                    = array();
	foreach ($raw_data_coding as $k => $v) {
		if (ucIsset($v['visiblity'], 1)) {
			$c                                 = $v['crn'];
			$course_new[ $c ]                  = $raw_data_coding[ $k ];
			$course_new[ $c ]['license_array'] = byte2array($raw_data_coding[ $k ]['product_licence_available'], $_product_licence_byte);
		}
	}
	return $course_new;
}

//@TODO: Get products from config
function createLearningCurveGroupProductList($productlist)
{
	if (is_string($productlist) && json_validate($productlist)) {
		$productlist = json_decode_uc($productlist, true);
	}

	$course_crns           = implode(',', array_column_uc($productlist, 'crn'));
	$where_c               = array();
	$where_c['ddl']        = "2";
	$where_c['idcolumn']   = "crn";
	$where_c['crn']        = $course_crns;
	$where_c['datacolumn'] = "course_name,course_code,picture,crn,page_uri";
	$course_data           = getAPIDataJ("cat2.catalog_course_get", $where_c);
	
	foreach ($productlist as $key => $val) {
		$crns = explode(',', $val['crn']);
	
		foreach ($crns as $crn) {
			$product                          = $course_data[ $crn ];
			$product['picture']               = USER_ASSETS_URL . 'jigyaasa_assets/images/new/' . $product['picture'] . '.png';
			$product['crn']                   = $crn;
			$productlist[ $key ]['courses'][] = $product;
		}

		$productlist[$key]['image'] = JIGYAASA_CONTENT_STATIC . 'gofourth/' . $val['image'];
	}

	return $productlist;
}


function trackIndexFuncLog($func, $action)
{
	$action_list = ',agreement,cover,ebook_search,gen_qr_code,get_capture_tool,get_gotit,get_lab_print,get_tools_gotit,list_mentors,set_current_time,survey1,survey2';
	$func_list = ',live_lab,get_item_xml,home,proctor_exams,access_simulator,assign_exam_objective,assignments,class_ranking_report,create_group,deletesession,ebook,exam_obj_relation,get_case,get_confirm_modal,get_content_copy,get_course_list,get_current_grade,get_dialog,get_flash_card,get_flashcard_score,get_keyboard_modal,get_mentor_annotation,get_module_report,get_request_demo,get_video_list,glossary,gradebook_planner,insight_log,instructor_search,lab_resources,lab_only_resources,resources,lectures,live_lab_assignments,load_course,mentoring_chat,open_side_pane,procees_live_lab,set_accessibility,start_lab,start_live_lab,start_performance,start_proctored_exam,study_planner,sync_lab_grade,sync_to_lms,test_edit,test_history_modal,video,video_playlist,welcome';
	if (empty($func)  && empty($action)) {
		return false;
	}

	if (@strpos($func_list, $func) === false && @strpos($action_list, $action) === false) {
		return false;
	}
	trackAPPLog($func, $action, 'IAT', __FILE__);
}
