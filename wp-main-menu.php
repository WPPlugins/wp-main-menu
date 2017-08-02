<?php
/*
Plugin Name: WP Main Menu
Plugin URI: http://sumolari.com/wp-main-menu
Description: With WP Main Menu you can create a great menu with links to categories, pages, posts, tags and more for WordPress
Version: 0.2
Author: Sumolari
Author URI: http://sumolari.com
*/

if (isset($_GET['activatetheme'])) { if ($_GET['activatetheme'] != '') { update_option('wp_main_menu_theme', $_GET['activatetheme']); } }

$wp_main_menu_path[1] = plugin_basename(__FILE__);
$wp_main_menu_path[2] = ereg_replace('/wp-main-menu.php', '', $wp_main_menu_path[1]);

if (!defined( 'WP_CONTENT_URL' ) ) define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if (!defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );

$wp_main_menu_path[5] = str_replace(get_option('siteurl').'/language/', '', WP_PLUGIN_URL);
$wp_main_menu_path[5] .= '/';

$wp_main_menu_path[4] = basename(dirname(__FILE__));

$currentLocale = get_locale();
if(!empty($currentLocale)) {
	$moFile = dirname(__FILE__) . "/language/" . $currentLocale . ".mo";
	if(@file_exists($moFile) && is_readable($moFile)) load_textdomain('wp-main-menu', $moFile);
}

$wp_main_menu_path[3] = WP_PLUGIN_URL.'/'.$wp_main_menu_path[2];

$wp_main_menu_theme = get_option('wp_main_menu_theme');
if ($wp_main_menu_theme == '') $wp_main_menu_theme = 'default';

function wpmm_load_theme_css_scripts() {
	global $wp_main_menu_theme, $wp_main_menu_path;
	include('themes/'.$wp_main_menu_theme.'/index.php');
	
	if (isset($theme['css'])) {
		if ($theme['css'] != '') {
			foreach($theme['css'] as $key => $value) {
				echo '<link rel="stylesheet" href="'.$wp_main_menu_path[3].'/themes/'.$wp_main_menu_theme.'/'.$value.'" type="text/css" media="screen" />';
			}
		}
	}
}

add_action('wp_head', 'wpmm_load_theme_css_scripts');

function wpmm_load_theme_js_scripts() {
	global $wp_main_menu_theme, $wp_main_menu_path;
	
	include('themes/'.$wp_main_menu_theme.'/index.php');
	if (isset($theme['js'])) {
		if ($theme['js'] != '') {
			foreach($theme['js'] as $key => $value) {
				wp_register_script('wp-main-menu-theme-js-'.$key, $wp_main_menu_path[3].'/themes/'.$wp_main_menu_theme.'/'.$value);
				wp_enqueue_script('wp-main-menu-theme-js-'.$key);
			}
		}
	}
}

add_action('init', 'wpmm_load_theme_js_scripts');

function wpmm_asign_variables($value) {
	
	$link['title'] = $value['name'];
	$link['name'] = $value['name'];
	
	$link['url'] = wp_main_menu_create_permalink($value['type'], $value['url']);
	
	if (isset($value['sublinks'])) {
		$link['sublinks'] = $value['sublinks'];
	} else {
		$link['sublinks'] = '';
	}
	
	return $link;
	
}

function wp_main_menu($mode='show') {
	global $wp_main_menu_theme;
	
	require('themes/'.$wp_main_menu_theme.'/theme.php');
	
	$wp_main_menu_link_prev = get_option('wp_main_menu');
	$wp_main_menu_link_prev = unserialize($wp_main_menu_link_prev);
	
	if (!isset($menu)) $menu = '';
	
	foreach ($wp_main_menu_link_prev as $key => $value) {
		
		$value = wp_main_menu_check_order($menu, $value);
		
		$menu[$value['order'].$key] = $value;
		$menu[$value['order'].$key]['id'] = $key;
				
	}
	
	ksort($menu);
	
	$menu2 = wp_main_menu_asign_sublinks($menu, $wp_main_menu_link_prev);
	
	ksort($menu2);
	
	//$generated_menu = wp_main_menu_create_menu($menu2);
		
	/* Only for Debug
	
	echo '<pre>';
	echo 'MENU';
	print_r($menu);
	echo 'MENU2';
	print_r($menu2);
	echo 'MAIN MENU';
	print_r($wp_main_menu_link_prev);
	echo '</pre>';
	
	*/
	
	//if ($echo) { echo $generated_menu; } else { return $generated_menu; }
	
	switch ($mode) {
		case 'show':
			wp_main_menu_create_menu($menu2);
			break;
		case 'get':
			ob_start();
			wp_main_menu_create_menu($menu2);
			$out = ob_get_contents();
			ob_end_clean();
			return $out;
			break;
		case 'array':
			return $menu2;
			break;
		default:
			break;
	}
		
}

function wp_main_menu_check_order($menu, $value) {
	if (!isset($menu[$value['order']])) $menu[$value['order']] = '';
	if ($menu[$value['order']] != '') { $value['order']++;  $value = wp_main_menu_check_order($menu, $value); }
	return $value;
}

function wp_main_menu_asign_sublinks($menu, $wp_main_menu_link_prev) {
	
	foreach ($menu as $key => $value) {
		
		if ($value['parent'] != '') {
			//Sublink
						
			//$menu2[$wp_main_menu_link_prev[$value['parent']]['order']]['sublinks'][$key] = $value;
			$menu2[$key] = $value;
			
			//$menu2[$wp_main_menu_link_prev[$value['parent']]['order']]['sublinks'][$key] = wp_main_menu_asign_sublinks($menu2[$wp_main_menu_link_prev[$value['parent']]['order']], $wp_main_menu_link_prev);
			
		} else {
			$menu2[$key] = $value;
		}
	}
	
	return $menu2;
	
}

add_action('admin_menu', 'wp_main_menu_wpmenulink');
add_action('admin_head', 'wp_main_menu_wpadmincss');

function wp_main_menu_dropdown_posts() {
	
	$posts_query = new WP_Query('showposts=10000');

	while ($posts_query->have_posts()) : $posts_query->the_post();
  	
		echo'<option value="'.get_the_ID().'">'.get_the_title().'</option>';
	
 	endwhile;
	
}

function wp_main_menu_dropdown_tags() {
	
	$tags = get_tags();
	
	if ($tags) {
		foreach($tags as $tag) {
			echo '<option value="'.$tag->term_id.'">'.$tag->name.'</option>'; 
		}
	}

}

function wp_main_menu_create_permalink($type, $id) {
	
	switch ($type) {
				
		case 'post':
			$permalink = get_permalink($id);
			break;
		
		case 'page':
			$permalink = get_permalink($id);
			break;
			
		case 'category':
			$permalink = get_category_link($id);
			break;
			
		case 'tag':
			$permalink = get_tag_link($id);
			break;
			
		case 'author':
			$permalink = get_bloginfo('url').'/?author='.$id.'';
			break;
			
		case 'URL':
			$permalink = $id;
			break;
		
	}
	
	return $permalink;
	
}

function wp_main_menu_create_name($type, $id) {
	
	switch ($type) {
				
		case 'post':
			$name = get_the_title($id);
			break;
		
		case 'page':
			$name = get_the_title($id);
			break;
			
		case 'category':
			$name = get_the_category($id);
			$name = $name[0]->cat_name;
			break;
			
		case 'tag':
			$name = get_the_tags($id);
			$name = $name->name;
			break;
			
		case 'author':
			$name = get_userdata($id);
			$name = $name->user_login;
			break;
			
		case 'URL':
			$name = $id;
			break;
		
	}
	
	return $name;
	
}

function selfURL($delete_url_variables=false, $delete_all_url_variables=false) {
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	
	$protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
	
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	
	$URL = $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
	
	if ($delete_url_variables) {
		$URL_array = explode('&', $URL);
		return $URL_array[0];
	} elseif ($delete_all_url_variables) {
		$URL_array = explode('?', $URL);
		return $URL_array[0];
	} else { return $URL; }
}

function strleft($s1, $s2) {
	return substr($s1, 0, strpos($s1, $s2));
}

function wp_main_menu_wpadmincss() {
	global $wp_main_menu_path;
	?>
	<!-- WP Main Menu - Table CSS -->
	<style type="text/css">
		.column-id { width:50px; }
		.column-order span { width:40px; }
		
		th.headerSortUp span { 
			background-image: url(<?php echo $wp_main_menu_path[3]; ?>/img/asc.gif); 
		} 
		th.headerSortDown span { 
			background-image: url(<?php echo $wp_main_menu_path[3]; ?>/img/desc.gif); 
		}
		th.header  span { 
			background: url(<?php echo $wp_main_menu_path[3]; ?>/img/bg.gif) no-repeat center left; 
			padding-left: 20px; 
		} 
		
	</style>
	<!-- End of WP Main Menu - Table CSS -->
	
	<!-- WP Main Menu - Post JS -->
	<script type="text/javascript" src="<?php echo $wp_main_menu_path[3]; ?>/jquery.tablesorter.js"></script>
	<script language="JavaScript">
		
		function wp_main_menu_hideDivs(){
			document.getElementById("camposPost").style.display = "none";
			document.getElementById("camposPage").style.display = "none";
			document.getElementById("camposCategory").style.display = "none";
			document.getElementById("camposAuthor").style.display = "none";
			document.getElementById("camposTag").style.display = "none";
			document.getElementById("camposURL").style.display = "none";
		}
		
		function wp_main_menu_doShow(){
			
			switch (window.document.forms.post_wp_main_menu.link_type.item(window.document.forms.post_wp_main_menu.link_type.selectedIndex).value) {
				
				case "post": wp_main_menu_hideDivs();
				document.getElementById("camposPost").style.display = "block";
				break;
				
				case "page": wp_main_menu_hideDivs();
				document.getElementById("camposPage").style.display = "block";
				break;
				
				case "category": wp_main_menu_hideDivs();
				document.getElementById("camposCategory").style.display = "block";
				break;
				
				case "author": wp_main_menu_hideDivs();
				document.getElementById("camposAuthor").style.display = "block";
				break;
				
				case "tag": wp_main_menu_hideDivs();
				document.getElementById("camposTag").style.display = "block";
				break;
				
				case "URL": wp_main_menu_hideDivs();
				document.getElementById("camposURL").style.display = "block";
				break;
				
			}
		}
		
		wp_main_menu_doShow();
	</script>
	<script type="text/javascript">
	
		jQuery(document).ready(function() 
			{ 
				jQuery("#wp_main_menu_links").tablesorter();
			} 
		)
	</script>
	<!-- End of WP Main Menu - Post JS -->
	
	<?php
}

function wp_main_menu_wpmenulink() {
	global $wp_main_menu_path;
	add_object_page('WP Main Menu', 'WP Main Menu', 8, __FILE__, 'wp_main_menu_wpoptionspages', $wp_main_menu_path[3].'/img/wp_main_menu_icon.png');
	add_submenu_page(__FILE__, __('Themes', 'wp-main-menu'), __('Themes', 'wp-main-menu'), 'administrator', 'wp-main-menu-themes', 'wp_main_menu_wpthemespage');
	add_submenu_page(__FILE__, __('Export', 'wp-main-menu'), __('Export', 'wp-main-menu'), 'administrator', 'wp-main-menu-export', 'wp_main_menu_wpexportpage');
	add_submenu_page(__FILE__, __('Import', 'wp-main-menu'), __('Import', 'wp-main-menu'), 'administrator', 'wp-main-menu-import', 'wp_main_menu_wpimportpage');
	add_submenu_page(__FILE__, __('Uninstall', 'wp-main-menu'), __('Uninstall', 'wp-main-menu'), 'administrator', 'wp-main-menu-uninstall', 'wp_main_menu_wpuninstallpage');
}

function wp_main_menu_dropdown_links($wp_main_menu_links) {
	foreach ($wp_main_menu_links as $key => $value) { 
		$father = explode('-', $value['parent']);
		
		$total_fathers = 0;
		foreach ($father as $f_key => $f_value) {
			if ($f_value != '') { $total_fathers++; }
		}
		
	?>
		<option value="<?php echo $value['parent']; if ($value['parent'] != '') { ?>-<?php } echo $key;?>"<?php if (isset($_GET['edit_id'])) { if ($wp_main_menu_links[$_GET['edit_id']]['parent'] == $key) { echo ' selected="selected"';} } ?>>
		<?php $n = 0; while ($total_fathers > $n) { echo '&nbsp;&nbsp;&nbsp;&nbsp;'; $n++; }?><?php echo $value['name']; ?>
		</option>
	<?php 
				
		if ($value['sublinks'] != '') {
			$father .= $key.'-';
			wp_main_menu_dropdown_links($value['sublinks']);
			$father = '';
		}
	}
}

function wp_main_menu_asign_array($new_link, $total_fathers, $my_father, $wp_main_menu_link_prev, $n=0, $current='', $return_current=false, $last_index) {
	
	$current .= '['.$my_father[$n].']["sublinks"]';
	$n++;
				
	if ($total_fathers > $n) {
		$current = wp_main_menu_asign_array($new_link, $total_fathers, $my_father, $wp_main_menu_link_prev, $n, $current, true, $last_index);
	}
	
	if ($return_current) {
		return $current;
	} else {
		
		$long=strlen($current)-1;
		$current = substr($current,0,$long);
		$current = substr($current,1);  
		
		$string = '$wp_main_menu_link_prev['.$current.']';
		
		$code = $string.'[] = $new_link;';
				
		eval($code);
		
		return $wp_main_menu_link_prev;
		
	}
		
}

function check_wp_main_menu_last_index($last_index, $wp_main_menu_link_prev, $mode='return') {
	switch ($mode) {
		case 'return':
			while ($wp_main_menu_link_prev[$last_index] != '') {
				$last_index++;
			}
			
			return $last_index;
			break;
		case 'check':
			$last_index_2 = $last_index;
			if (isset($wp_main_menu_link_prev[$last_index_2])) $wp_main_menu_link_prev[$last_index_2] = '';
			while ($wp_main_menu_link_prev[$last_index_2] != '') {
				$last_index_2++;
			}
			
			if ($last_index_2 == $last_index) { return true; } else { return false; }
			break;
	}
}

function wp_main_menu_detect_last_index($parent, $wp_main_menu_link_prev) {	
	$parents_array = explode('-', $parent);
	$current_parent = current($parents_array);
	$other_parents = explode($current_parent.'-', $parent, 2);
	$next_parent = current($parents_array);
					
	if (isset($wp_main_menu_link_prev[$current_parent]['sublinks'])) {
		$current_wp_main_menu_link_prev = $wp_main_menu_link_prev[$current_parent]['sublinks'];
	} else {
		$current_wp_main_menu_link_prev = '';
	}
	
	if(!isset($other_parents[1])) $other_parents[1] = '';
		
	if ($current_wp_main_menu_link_prev == '' && $other_parents[1] != '') { 
		$last_index = 1;
		$last_index = check_wp_main_menu_last_index($last_index, $current_wp_main_menu_link_prev);
	} elseif ($other_parents[1] == '') {
		$n = 0;
		foreach ($wp_main_menu_link_prev as $key => $value) {
			$n++;
			if (!isset($current_wp_main_menu_link_prev[$n])) $current_wp_main_menu_link_prev[$n] = '';
			if ($current_wp_main_menu_link_prev[$n] != '') { $previous_last_index = $key; } else { if(!isset($previous_last_index)) { $previous_last_index = 0; } $last_index = $previous_last_index + 1; }
		}
		
		if (!check_wp_main_menu_last_index($last_index, $wp_main_menu_link_prev, 'check')) {
			$last_index = check_wp_main_menu_last_index($last_index, $wp_main_menu_link_prev);
		}
		
	} else {
		$n = 0;
		foreach ($current_wp_main_menu_link_prev as $key => $value) { 
			$n++;
			if (!isset($current_wp_main_menu_link_prev[$n])) $current_wp_main_menu_link_prev[$n] = '';
			if ($current_wp_main_menu_link_prev[$n] != '') { $previous_last_index = $key; } else { $last_index = $previous_last_index + 1; }
		}
				
		if ($next_parent != '') { $last_index = wp_main_menu_detect_last_index($other_parents[1], $current_wp_main_menu_link_prev); }
		
		if($last_index == '') { $last_index = $previous_last_index + 1; }
	}
		
	return $last_index;
	
}

function wp_main_menu_create_delete_sublink_pointer_string($parents_array, $id) {
	
	$parents_number = count($parents_array);
	
	if (!isset($new_pointer)) $new_pointer = '';
	if (!isset($parents_array[0])) $parents_array[0] = '';
	if ($parents_array[0] != '') { $new_pointer .= '['.$parents_array[0].']'; } else { $new_pointer .= '['.$id.']'; }
	
	unset($parents_array[0]);
	$parents_array = array_values($parents_array);
	$parents_new_number = $parents_number--;
	
	if ($parents_new_number > 0) {
		$new_pointer .= "['sublinks']";
		$new_pointer .= wp_main_menu_create_delete_sublink_pointer_string($parents_array, $id);
	}
	
	return $new_pointer;
	
}

function wp_main_menu_wpoptionspages() {
		global $wp_main_menu_path;
	
		/* This line will erase al WP Main Menu's data */
		//update_option('wp_main_menu', '');
		
		if (isset($_GET['uninstall'])) { if($_GET['uninstall'] == true) {
			// WP Main Menu is going to be uninstalled
			delete_option('wp_main_menu');
			delete_option('wp_main_menu_theme');
		} }
	
		$wp_main_menu_link_prev = get_option('wp_main_menu');
		$wp_main_menu_link_prev = unserialize($wp_main_menu_link_prev);
				
		$n = 1;
		if ($wp_main_menu_link_prev != '') {
			foreach ($wp_main_menu_link_prev as $key => $value) {
				$array_2[$n] = $key;
				$n++;
			}
		}
		if (isset($array_2)) {
			$n_l = count($array_2);
			if ($array_2[$n_l] == '') { $last_index = 0; } else { $last_index = $array_2[$n_l]; }
		} else {
			$last_index = 0;
		}
		
		$last_index++;
				
		if (isset($_GET['delete_id'])) {
			
			// WP Main Menu is deleting an item
			
			/* Only for Debug 
			
			echo 'DELETE LINK #'.$_GET['delete_id'];
			
			*/
			
			if($_GET['parent_id'] != '' || $_GET['parent_id'] == '0') {
				
				// AHORA TOCARIA EXPLODE Y RECURSIVA
				
				$pointer_string = wp_main_menu_create_delete_sublink_pointer_string(explode('-', $_GET['parent_id']), $_GET['delete_id']);
				
				$delete_code = 'unset($wp_main_menu_link_prev'.$pointer_string.');';
				
				/* Only for Debug
			
				echo 'DELETE LINK #'.$_GET['delete_id'].' <br /> POINTER '.$pointer_string.' <br/> DELETE CODE '.$delete_code;
				
				*/
				
				eval ($delete_code);
												
			} else {
				unset($wp_main_menu_link_prev[$_GET['delete_id']]);		
			}
			
			/* Only for Debug
			
			echo '<pre>';
			print_r($wp_main_menu_link_prev);
			echo '</pre>';
			
			*/
			
			$wp_main_menu_link_prev_deleted = serialize($wp_main_menu_link_prev);
						
			update_option('wp_main_menu', $wp_main_menu_link_prev_deleted);
			
		}
					
		if (isset($_POST['publish']) || isset($_POST['save'])) {
			if (isset($_GET['edit_id'])) {
				
				// WP Main Menu is editing an item
				
				$id = $_GET['edit_id'];
				
				$_POST['sublinks'] = str_replace('\"', '"', $_POST['sublinks']);
												
				$wp_edited_link[$_GET['edit_id']]['name'] = $_POST['post_title'];
				$wp_edited_link[$_GET['edit_id']]['type'] = $_POST['link_type'];
				$wp_edited_link[$_GET['edit_id']]['order'] = $_POST['post_order'];
				$wp_edited_link[$_GET['edit_id']]['sublinks'] = unserialize($_POST['sublinks']);
				$wp_main_menu_link_prev[$_GET['edit_id']]['parent'] = $_POST['post_parent'];
				
				switch ($_POST['link_type']) {
					
					case 'post':
						$wp_edited_link[$_GET['edit_id']]['url'] = $_POST['post_id'];
						break;
					
					case 'page':
						$wp_edited_link[$_GET['edit_id']]['url'] = $_POST['page_id'];
						break;
						
					case 'category':
						$wp_edited_link[$_GET['edit_id']]['url'] = $_POST['category_id'];
						break;
						
					case 'tag':
						$wp_edited_link[$_GET['edit_id']]['url'] = $_POST['tag_id'];
						break;
						
					case 'author':
						$wp_edited_link[$_GET['edit_id']]['url'] = $_POST['author_id'];
						break;
						
					case 'URL':
						$wp_edited_link[$_GET['edit_id']]['url'] = $_POST['link_url'];
						break;
					
				}
				
				if (isset($_POST['publish'])) { $wp_edited_link[$_GET['edit_id']]['status'] = 'publish'; }
				if (isset($_POST['save'])) { $wp_edited_link[$_GET['edit_id']]['status'] = 'draft'; }
				
				if ($_GET['parent_id'] != '') {
					
					$wp_edited_link[$_GET['edit_id']]['parent'] = $_GET['parent_id'];
					
					$editlink_parents = explode ('-', $_GET['parent_id']);
					foreach($editlink_parents as $key => $value) {
						$editlink_pointer .= "[".$value."]['sublinks']";
					}
					
					$editlink_pointer .= '['.$_GET['edit_id'].']';
					
					$add_editlink_code = '$wp_main_menu_link_prev'.$editlink_pointer.' = $wp_edited_link['.$_GET['edit_id'].'];';
					eval ($add_editlink_code);
										
				} else {
					$wp_main_menu_link_prev[$_GET['edit_id']] = $wp_edited_link[$_GET['edit_id']];
				}
				
				/* Only for Debug
				
				echo '<pre>';
				print_r($wp_main_menu_link_prev);
				echo '</pre>';
				
				*/
				
				$wp_main_menu_link_prev = serialize($wp_main_menu_link_prev);
				
				update_option('wp_main_menu', $wp_main_menu_link_prev);
				
			} else {
				
				// WP Main Menu is adding a new item
							
				/* Only for Debug
				
				echo '<pre>';
				print_r($_POST);
				echo '</pre>';
				
				*/
								
				$new_link['name'] = $_POST['post_title'];
				$new_link['type'] = $_POST['link_type'];
				$new_link['order'] = $_POST['post_order'];
				$new_link['parent'] = $_POST['post_parent'];
				
				$my_father = explode('-', $new_link['parent']);
				
				$total_fathers = 0;
				foreach ($my_father as $f_key => $f_value) {
					if ($f_value != '') { $total_fathers++; }
				}
				
				switch ($_POST['link_type']) {
					
					case 'post':
						$new_link['url'] = $_POST['post_id'];
						break;
					
					case 'page':
						$new_link['url'] = $_POST['page_id'];
						break;
						
					case 'category':
						$new_link['url'] = $_POST['category_id'];
						break;
						
					case 'tag':
						$new_link['url'] = $_POST['tag_id'];
						break;
						
					case 'author':
						$new_link['url'] = $_POST['author_id'];
						break;
						
					case 'URL':
						$new_link['url'] = $_POST['link_url'];
						break;
					
				}
				
				if (isset($_POST['publish'])) { $new_link['status'] = 'publish'; }
				if (isset($_POST['save'])) { $new_link['status'] = 'draft'; }
								
				if ($_POST['post_parent'] != '') {
					
					$last_index = wp_main_menu_detect_last_index($_POST['post_parent'], $wp_main_menu_link_prev);
										
					/* Only for Debug
					
					echo '<pre>';
					print_r($wp_main_menu_link_prev);
					echo '</pre>';
					
					*/
					if ($total_fathers == '1') {
						$wp_main_menu_link_prev[$_POST['post_parent']]['sublinks'][] = $new_link;
					} else {
						$wp_main_menu_link_prev = wp_main_menu_asign_array($new_link, $total_fathers, $my_father, $wp_main_menu_link_prev, 0, '', false, $last_index);
						
						/* Only for Debug
						
						echo '<pre>';
						print_r($wp_main_menu_link_prev);
						echo '</pre>';
						
						*/
																																																
					}
					
				} else {
					$n_temp = 0;
					if ($wp_main_menu_link_prev != '') {
						foreach ($wp_main_menu_link_prev as $key => $value) {
							$n_temp++;
							$array_temp[$n_temp] = $key;
						}
						$last_index = $array_temp[$n_temp] + 1;
						$wp_main_menu_link_prev[$last_index] = $new_link;
					} else {
						$wp_main_menu_link_prev[0] = $new_link;
					}
				}
										
				/* Only for Debug
				
				echo 'New Link: <pre>';
				print_r($new_link);
				echo '</pre>';
				
				*/
								
				/* Only for Debug
				
				echo 'Main array: <pre>';
				print_r($wp_main_menu_link_prev);
				echo '</pre>';
				
				*/
												
				$wp_main_menu_link_prev_updated = serialize($wp_main_menu_link_prev);

				update_option('wp_main_menu', $wp_main_menu_link_prev_updated);
				
				printf(__('<p>The link has been added successfully, click <a href="%s">here</a> to continue.</p>', 'wp-main-menu'), selfurl());
				exit;

			}
						
		}
		
		$wp_main_menu_links = get_option('wp_main_menu');
		
		$wp_main_menu_links = unserialize($wp_main_menu_links);
		
		/* Only for Debug
		
		echo 'Content in Database: <pre>';
		print_r($wp_main_menu_links);
		echo '</pre>';
		
		*/
				
	?>
	<div class="wrap" id="wp_main_menu">
		
		<div id="wpmm-warning" class="updated fade"><p><?php echo _e('To show the menu, add this code in your theme:', 'wp-main-menu'); ?><strong><em>&lt;?php wp_main_menu(); ?&gt;</em></strong></p></div>

		
		<div id="icon-link-manager" class="icon32"><br /></div>
		<h2>WP Main Menu</h2>

		<table class="widefat post fixed" cellspacing="0" id="wp_main_menu_links">
		
			<thead>
				<tr>
					<th scope="col" id="id" class="manage-column column-id" style=""><span><?php echo _e('ID', 'wp-main-menu'); ?></span></th>
					<th scope="col" id="title" class="manage-column column-title" style=""><span><?php echo _e('Name', 'wp-main-menu'); ?></span></th>
					<!--<th scope="col" id="visible" class="manage-column column-visible" style=""><span>Visible para</span></th>-->
					<th scope="col" id="status" class="manage-column column-status" style=""><span><?php echo _e('Status', 'wp-main-menu'); ?></span></th>
					<th scope="col" id="type" class="manage-column column-type" style=""><span><?php echo _e('Type', 'wp-main-menu'); ?></span></th>
					<th scope="col" id="url" class="manage-column column-url" style=""><span><?php echo _e('URL', 'wp-main-menu'); ?></span></th>
					<th scope="col" id="order" class="manage-column column-order" style=""><span><?php echo _e('Order', 'wp-main-menu'); ?></span></th>
					<th scope="col" id="parent" class="manage-column column-parent" style=""><span><?php echo _e('Father', 'wp-main-menu'); ?></span></th>
				</tr>
			</thead>
	
			<tfoot>
				<tr>
					<th scope="col" class="manage-column column-id" style=""><span><?php echo _e('ID', 'wp-main-menu'); ?></span></th>
					<th scope="col" class="manage-column column-title" style=""><span><?php echo _e('Name', 'wp-main-menu'); ?></span></th>
					<!--<th scope="col" class="manage-column column-visible" style="">Visible para</th>--->
					<th scope="col" class="manage-column column-status" style=""><span><?php echo _e('Status', 'wp-main-menu'); ?></span></th>
					<th scope="col" class="manage-column column-type" style=""><span><?php echo _e('Type', 'wp-main-menu'); ?></span></th>
					<th scope="col" class="manage-column column-url" style=""><span><?php echo _e('URL', 'wp-main-menu'); ?></span></th>
					<th scope="col" class="manage-column column-order" style=""><span><?php echo _e('Order', 'wp-main-menu'); ?></span></th>
					<th scope="col" class="manage-column column-parent" style=""><span><?php echo _e('Father', 'wp-main-menu'); ?></span></th>
				</tr>
			</tfoot>
	
			<tbody>
				<?php wp_main_menu_create_links_table_content($wp_main_menu_link_prev, $wp_main_menu_links); ?>
			</tbody>
				
		</table>
	
		<div id="icon-edit" class="icon32"><br /></div>
		<h2><?php if (isset($_GET['edit_id'])) { echo _e('Edit a link', 'wp-main-menu'); } else { echo _e('Add a link', 'wp-main-menu'); } ?></h2>
		
		<form name="post_wp_main_menu" action="<?php echo selfURL(); ?>" method="post" id="post_wp_main_menu" class="post_wp_main_menu">
		<?php 
			if (isset($_GET['edit_id'])) {
				$editlink_pointer = '';
				
				if (isset($_GET['parent_id'])) {
					$editlink_parents = explode ('-', $_GET['parent_id']);
					foreach($editlink_parents as $key => $value) {
						$editlink_pointer .= "[".$value."]['sublinks']";
					}
				}
				
				$editlink_pointer .= '['.$_GET['edit_id'].']';
				$select_editlink_array_code = '$editlink = $wp_main_menu_links'.$editlink_pointer.';';
				eval($select_editlink_array_code);
			}
		?>
			<div id="poststuff" class="metabox-holder has-right-sidebar">
			
				<div id="side-info-column" class="inner-sidebar">


					<div id='side-sortables' class='meta-box-sortables'>
					
						<!-- Publish link -->
					
						<div id="submitdiv" class="postbox">
							<div class="handlediv" title="Click to toggle"><br /></div>
							<h3 class='hndle'><span><?php echo _e('Publish', 'wp-main-menu'); ?></span></h3>
							<div class="inside">
								<div class="submitbox" id="submitpost">
									<div id="minor-publishing">

										<div id="minor-publishing-actions">
											<div id="save-action">
												<input  type="submit" name="save" id="save-post" value="<?php echo _e('Save', 'wp-main-menu'); ?> <?php if (isset($_GET['edit_id'])) { ?><?php echo _e('as a', 'wp-main-menu'); ?> <?php } echo _e('draft', 'wp-main-menu'); ?>" tabindex="4" class="button button-highlighted" />
											</div>
											<!--
											<div id="preview-action">
												<a class="preview button" href="#link-url" target="wp-preview" id="post-preview" tabindex="4">Previsualizar</a>
											</div>
											-->
											<div class="clear"></div>
										</div>
										
										
										<div id="misc-publishing-actions">
											<div class="misc-pub-section">
												<label for="post_order"><?php echo _e('Order', 'wp-main-menu'); ?>:</label>
												<input type="text" name="post_order" value="<?php if (isset($_GET['edit_id'])) { echo $editlink['order']; } else { echo '0'; } ?>" />
											</div>
											<?php if (isset($_GET['edit_id'])) { ?> <input type="hidden" id="post_parent" class="post_parent" name="post_parent" value="<?php echo $editlink['parent']; ?>" /><?php } else { ?>
											<div class="misc-pub-section">
												<label for="post_parent"><?php echo _e('Father', 'wp-main-menu'); ?>:</label>
												<select id="post_parent" class="post_parent" name="post_parent">
													<?php wp_main_menu_dropdown_links($wp_main_menu_links); ?>
													<option value="" selected="selected"><?php echo _e('None', 'wp-main-menu'); ?></option>
												</select>
											</div>
											<?php } ?>
											
											<?php if (isset($_GET['edit_id'])) {  ?>
											<div class="misc-pub-section">
											<?php printf(__('Click <a href="%s">here</a> to add a new link instead of edit an old one <br />', 'wp-main-menu'), selfURL(false, true).'?page='.$wp_main_menu_path[1].'#post_wp_main_menu'); ?>
											</div>
											<?php } ?>
										</div>
										
										
									</div>
								</div>
								<div class="clear"></div>
							</div>

							<div id="major-publishing-actions">
								<div id="publishing-action">
									<input name="publish" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php if (isset($_GET['edit_id'])) { echo _e('Update', 'wp-main-menu'); } else { echo _e('Publish', 'wp-main-menu'); } ?>" />
								</div>
								<div class="clear"></div>
							</div>

						</div>
						<!-- End of Publish link -->
						
					</div>
				</div>			

				<div id="post-body">
					<div id="post-body-content">
					
						<div id="titlediv">
							<div id="titlewrap">
								<label class="screen-reader-text" for="title">Title</label>

								<input type="text" name="post_title" size="30" tabindex="1" id="title" autocomplete="off" value="<?php if (isset($_GET['edit_id'])) { echo $editlink['name']; } ?>" />
							</div>
						</div>
	
						<div id='normal-sortables' class='meta-box-sortables'>
						
							<!-- Select a type -->
							<div id="tagsdiv-post_tag" class="postbox " >
								<div class="handlediv" title="Click to toggle"><br /></div>
								<h3 class='hndle'><span><?php echo _e('Link type', 'wp-main-menu'); ?></span></h3>
								<div class="inside">
									<p><?php echo _e('Select a link type from the list', 'wp-main-menu'); ?></p>
									<?php if (isset($_GET['edit_id'])) { $edit_link_type = $editlink['type']; } ?>
									<?php if (!isset($edit_link_type)) $edit_link_type = ''; ?>
									<select name="link_type" id="link_type" class="link_type" onchange="wp_main_menu_doShow()" onfocus="wp_main_menu_doShow()">
										<option class="post" id="post" value="post"<?php if ($edit_link_type == 'post') { ?> selected="selected"<?php } ?>><?php echo _e('Post', 'wp-main-menu'); ?></option>
										<option class="page" id="page" value="page"<?php if ($edit_link_type == 'page') { ?> selected="selected"<?php } ?>><?php echo _e('Page', 'wp-main-menu'); ?></option>
										<option class="category" id="category" value="category"<?php if ($edit_link_type == 'category') { ?> selected="selected"<?php } ?>><?php echo _e('Category', 'wp-main-menu'); ?></option>
										<option class="tag" id="tag" value="tag"<?php if ($edit_link_type == 'tag') { ?> selected="selected"<?php } ?>><?php echo _e('Tag', 'wp-main-menu'); ?></option>
										<option class="author" id="author" value="author"<?php if ($edit_link_type == 'author') { ?> selected="selected"<?php } ?>><?php echo _e('User', 'wp-main-menu'); ?></option>
										<option class="url" id="url" value="URL"<?php if ($edit_link_type == 'URL') { ?> selected="selected"<?php } ?>><?php echo _e('URL', 'wp-main-menu'); ?></option>
									</select>
								</div>
							</div>
							<!-- End of Select a type -->
						
							<!-- Select an object -->		
							<div id="tagsdiv-post_tag" class="postbox " >
								<div class="handlediv" title="Click to toggle"><br /></div>
								<h3 class='hndle'><span><?php echo _e('Link', 'wp-main-menu'); ?></span></h3>
								<div class="inside">
									<p><?php echo _e('Select an item for the link', 'wp-main-menu'); ?> <?php if (isset($_GET['edit_id'])) { printf( __('(previously "%s" )', 'wp-main-menu'), wp_main_menu_create_name($editlink['type'], $editlink['url'])); } ?>.</p>
									<div id="camposPost"<?php if ($edit_link_type != 'post') { ?> style="display:none;"<?php } ?>>
										<select name="post_id" id="post_id">
											<?php wp_main_menu_dropdown_posts(); ?>
										</select>
									</div>
									<div id="camposPage"<?php if ($edit_link_type != 'page') { ?> style="display:none;"<?php } ?>>
										<?php wp_dropdown_pages(); ?>
									</div>
									<div id="camposCategory"<?php if ($edit_link_type != 'category') { ?> style="display:none;"<?php } ?>>
										<?php wp_dropdown_categories('name=category_id'); ?>
									</div>
									<div id="camposTag"<?php if ($edit_link_type != 'tag') { ?> style="display:none;"<?php } ?>>
										<select name="tag_id" id="tag_id">
											<?php wp_main_menu_dropdown_tags(); ?>
										</select>
									</div>
									<div id="camposAuthor"<?php if ($edit_link_type != 'author') { ?> style="display:none;"<?php } ?>>
										<?php wp_dropdown_users('name=author_id'); ?>
									</div>
									<div id="camposURL"<?php if ($edit_link_type != 'URL') { ?> style="display:none;"<?php } ?>>
										<input type="text" name="link_url" id="link_url" value="<?php if (isset($_GET['edit_id'])) { echo wp_main_menu_create_name($editlink['type'], $editlink['url']);  } else { ?>http://<?php } ?>" />
									</div>
								</div>
							</div>
							<!-- End of Select an object -->		
						
						</div>
						
					</div>
				</div>
				
				<br class="clear" />
				
			</div><!-- /poststuff -->
			
			<input type="hidden" name="sublinks" class="sublinks" id="sublinks" value='<?php if (isset($_GET['edit_id'])) { echo serialize($editlink['sublinks']); }?>' />
			
		</form>
	
		<p><?php _e('English translation by <a href="http://sumolari.com">Sumolari</a>.', 'wp-main-menu'); ?></p>
	
	</div>
	<?php
}

function wp_main_menu_create_links_table_content($wp_main_menu_link_prev, $wp_main_menu_links, $parent='', $parent_id='') {
	
	/* Only for debug
	echo '<pre>';
	print_r($wp_main_menu_link_prev);
	echo '</pre>';
	*/
	
	$n = 0;
	if ($wp_main_menu_link_prev != '') {
		foreach ($wp_main_menu_links as $key => $value) {
			
		$n++;
		$n2 = $n / 2;
		$n2 = round($n2, 0);
		$n3 = $n2 * 2;
		if ($n3 == $n) { $alternate = ''; } else { $alternate = 'alternate'; }
		
		if ($value['status'] == 'draft') { $status = __('Draft', 'wp-main-menu'); } elseif ($value['status'] == 'publish') { $status = __('Published', 'wp-main-menu'); }
		
		switch ($value['type']) {
	
			case 'post':
				$type = __('to a post', 'wp-main-menu');
				break;
			
			case 'page':
				$type = __('to a page', 'wp-main-menu');
				break;
				
			case 'category':
				$type = __('to a category', 'wp-main-menu');
				break;
				
			case 'tag':
				$type = __('to a tag', 'wp-main-menu');
				break;
				
			case 'author':
				$type = __('to an user', 'wp-main-menu');
				break;
				
			case 'URL':
				$type = __('to an URL', 'wp-main-menu');
				break;
			
		}
		
		$sublink_indicators = count(explode('-', $value['parent']));
		
	?>
	<tr id="link-<?php echo $key; ?>" class="<?php echo $alternate; ?><?php if ($parent != '') echo " sublink"; ?>" valign="top">
		
		<th scope="row" class="id-column"><?php $indicators_showed = 0; while ($indicators_showed < $sublink_indicators) { $indicators_showed++; if ($parent != '') echo "&raquo; "; } ?><?php echo $key; ?></th>
		
		<td class="post-title column-title">
			<strong>
				<a class="row-title" href="#" title="Editar &#8220;<?php echo $value['name']; ?>&#8221;"><?php echo $value['name']; ?></a>
			</strong>
			<div class="row-actions">
				<span class='edit'>
					<a href="<?php echo selfURL(true); ?>&edit_id=<?php echo $key; if ($value['parent'] != '') echo '&parent_id='.$value['parent'].''; ?>#post_wp_main_menu" title="<?php echo _e('Edit this link', 'wp-main-menu'); ?>"><?php echo _e('Edit', 'wp-main-menu'); ?></a> | 
				</span>
				<span class="delete">
					<a class="submitdelete" title="<?php echo _e('Delete this link', 'wp-main-menu'); ?>" href="<?php echo selfURL(); ?>&delete_id=<?php echo $key; echo '&parent_id='.$value['parent'].''; ?>" onclick="if ( confirm('<?php echo _e('You are going to delete', 'wp-main-menu'); ?> \'<?php echo $value['name']; ?>\'\n \'<?php echo _e('Cancel', 'wp-main-menu'); ?>\' <?php echo _e('to stop', 'wp-main-menu'); ?>, \'<?php echo _e('OK', 'wp-main-menu'); ?>\' <?php echo _e('to delete.', 'wp-main-menu'); ?>') ) { return true;}return false;"><?php echo _e('Delete', 'wp-main-menu'); ?></a> | </span>
				<span class='view'>
					<a href="<?php echo wp_main_menu_create_permalink($value['type'], $value['url']); ?>" title="<?php echo _e('View', 'wp-main-menu'); ?> &#8220;<?php echo $value['name']; ?>&#8221;" rel="permalink" target="_blank"><?php echo _e('View', 'wp-main-menu'); ?></a>
				</span>
			</div>
		</td>
		
		<!--<td class="visible column-visible">Todos</td>-->
		
		<td class="status column-status"><?php echo $status; ?></td>
		
		<td class="type column-type"><?php echo _e('Link', 'wp-main-menu'); ?> <?php echo $type; ?></td>

		<td class="url column-url"><a href="<?php echo wp_main_menu_create_permalink($value['type'], $value['url']); ?>"><?php echo wp_main_menu_create_permalink($value['type'], $value['url']); ?></a></td>
		<td class="order column-order"><?php echo $value['order']; ?></td>
		<td class="parent column-parent"><?php echo $parent; ?></td>
	
	</tr>
	<?php
			
			if (isset($value['sublinks'])) {
				if ($value['sublinks'] != '') {
					wp_main_menu_create_links_table_content($wp_main_menu_links , $value['sublinks'], $value['name'], $key);
				}
			}
	
		}
	}
	
}

function wp_main_menu_wpexportpage() {
	?>
	<div class="wrap" id="wp_main_menu">
		
		<div id="icon-tools" class="icon32"><br></div>
		<h2><?php echo _e('Export', 'wp-main-menu'); ?></h2>
		
		<p><?php echo _e('Copy this code and paste it in the WP Main Menu\'s import page to backup your config. You can also put this code in a text file and paste it in WP Main Menu\'s import page later', 'wp-main-menu'); ?></p>
		
		<form>
		<textarea cols="60" rows="20"><?php echo base64_encode(get_option('wp_main_menu')); ?></textarea>
		</form>
		
	</div>
		
	<?php
}

function wp_main_menu_wpimportpage() {
	
	if (isset($_POST['import'])) { 
		if ($_POST['import'] != '') {
			
		 	$wp_main_menu = $_POST['import'];
			//echo 'First step (simple echo): '.$wp_main_menu.' .';
			
			$wp_main_menu = base64_decode($wp_main_menu);
			//echo '<br /> Second step (base64_decode): '.$wp_main_menu.' .';
			
			update_option('wp_main_menu', $wp_main_menu);
			
		}
	}
	
	?>
	<div class="wrap" id="wp_main_menu">
		
		<div id="icon-tools" class="icon32"><br></div>
		<h2><?php echo _e('Import', 'wp-main-menu'); ?></h2>
		
		<p><?php printf(__('If you have a WP Main Menu\'s exportation code, copy it in this field and click on "%s" ', 'wp-main-menu'), __('Import')); ?></p>
		
		<form name="wp-main-menu-import" id="wp-main-menu-import" action="<?php echo selfURL(); ?>" method="post"> 
		<textarea cols="60" rows="20" name="import" id="import"></textarea>
		<br />
		<input name="publish" type="submit" class="button-primary" id="publish" tabindex="5" accesskey="p" value="<?php echo _e('Import', 'wp-main-menu'); ?>" />
		</form>
		
	</div>
		
	<?php	
}

function wp_main_menu_wpthemespage() {
	global $wp_main_menu_path, $wp_main_menu_theme;
	
	//$wp_main_menu_theme = get_option('wp_main_menu_theme');
		
	$dir = explode(get_option('siteurl'), $wp_main_menu_path[3]);
	$dir = $dir[1];
	$dir = "..$dir/themes";
	
	$url = $wp_main_menu_path[3].'/themes/';

	if (is_dir($dir)) {
    	if ($handle = opendir($dir)) {
       		while (($file = readdir($handle)) !== false) {
            	if (is_dir($dir.'/'.$file) && $file != '.' && $file != '..') { if ($file != '.svn') $themes[] = $file; }
        	}
        closedir($handle);
		}
	}
		
	foreach ($themes as $key => $value) {
		include($dir.'/'.$value.'/index.php');
		$themeinfo[$value] = $theme;
	}
			
?>
	
	<div class="wrap">
		<div id="icon-themes" class="icon32"><br /></div>
			<h2><?php echo __('Manage Themes', 'wp-main-menu'); ?></h2>
			
			<h3><?php echo __('Current Theme', 'wp-main-menu'); ?></h3>
			<div id="current-theme">
				<img src="<?php echo $url.$wp_main_menu_theme.'/screenshot.png'; ?>" alt="<?php echo __('Current theme preview', 'wp-main-menu'); ?>" />
				<h4><?php echo $themeinfo[$wp_main_menu_theme]['name'].' '; echo __('by', 'wp-main-menu'); ?> <a href="<?php echo $themeinfo[$wp_main_menu_theme]['author_url']; ?>" title="<?php echo __('Visit author homepage', 'wp-main-menu'); ?>"><?php echo $themeinfo[$wp_main_menu_theme]['author']; ?></a></h4>
				<p class="theme-description"><?php echo $themeinfo[$wp_main_menu_theme]['desc']; ?> <br/> <?php printf(__('Version %s', 'wp-main-menu'), $themeinfo[$value]['version']); ?></p>
				<?php printf(__('<p>All of this theme&#8217;s files are located in <code>%s</code>.</p>', 'wp-main-menu'), $dir.'/'.$wp_main_menu_theme); ?>
			</div>

			<div class="clear"></div>
			
			<h3><?php echo __('Available Themes', 'wp-main-menu'); ?></h3>
			<div class="clear"></div>

			<?php
				$otherthemes = $themes;
				foreach ($otherthemes as $key => $value) { $temp_themes[$value] = $key; }
				$activatedtheme_key = $temp_themes[$wp_main_menu_theme];
				unset($otherthemes[$activatedtheme_key]);
				$totalthemes = count($otherthemes);
				$otherthemes = array_values($otherthemes);
				if ($totalthemes > 1 || $totalthemes = 1):
			?>
			<table id="availablethemes" cellspacing="0" cellpadding="0">
			<?php foreach ($otherthemes as $key => $value): if ($value != $wp_main_menu_theme): ?>
			
				<?php
					$show_tr = false;
					if ($key != 0) {
						$temp_a = $key / 3;
						$temp_b = floor($temp_a);
						$temp_b = $temp_b * 3;
						if ($key == $temp_b) { $show_tr = true; }
					} else { $show_tr = true; }
					
					$last_item_in_tr = false;
					if ($key != 0) {
						$temp_key = $key + 1;
						$temp_a = $temp_key / 3;
						$temp_b = floor($temp_a);
						$temp_b = $temp_b * 3;
						if ($temp_key == $temp_b) { $last_item_in_tr = true; }
					} else { $last_item_in_tr = false; }
					
					$bottom_td = false;
					if((($key + 3) == $totalthemes) || (($key + 2) == $totalthemes) || (($key + 1) == $totalthemes)) $bottom_td = true;
					
					$top_td = false;
					if (($key == 0) || ($key == 1) || ($key == 2)) $top_td = true;
					
					if ($show_tr) { ?><tr><?php } ?>
					
					<td class="available-theme <?php if($bottom_td) { ?>bottom<?php } if($top_td) { ?>top<?php } ?> <?php if ($show_tr) { ?>left<?php } if ($last_item_in_tr) { ?>right<?php } ?>">
						<a href="<?php echo selfurl().'&activatetheme='.$value; ?>" class="thickbox thickbox-preview screenshot">
						<img src="<?php echo $url.$value.'/screenshot.png'; ?>" alt="<?php echo $themeinfo[$value]['name']; ?>" />
						</a>
						<h3><?php echo $themeinfo[$value]['name'].' '.__('by', 'wp-main-menu').' '.$themeinfo[$value]['author']; ?></h3>
						<p class="description"><?php echo $themeinfo[$value]['desc']; ?> <br /> <?php printf(__('Version %s', 'wp-main-menu'), $themeinfo[$value]['version']); ?></p>
						<span class='action-links'><a href="<?php echo selfurl().'&activatetheme='.$value; ?>"><?php echo __('Activate', 'wp-main-menu'); ?></a> | <a href="<?php echo $themeinfo[$value]['url']; ?>"><?php echo __('Visit theme site', 'wp-main-menu'); ?></a> | <a href="<?php echo $themeinfo[$value]['author_url']; ?>"><?php echo __('Visit author site', 'wp-main-menu'); ?></a></span>

						<?php printf(__('<p>All of this theme&#8217;s files are located in <code>%s</code>.</p>', 'wp-main-menu'), $wp_main_menu_path[3].'/themes/'.$value); ?>
					</td>
					
					<?php if ($last_item_in_tr) { ?></tr><?php } ?>
			<?php endif; endforeach; ?>
			<?php endif; ?>
			</table>
			
			<br class="clear" />
			<br class="clear" />

		</div>

	
<?php
	
}

function wp_main_menu_wpuninstallpage() {
	global $wp_main_menu_path;
		
	$uninstall_url = selfURL(false, true).'?page='.$wp_main_menu_path[1].'&uninstall=true';
	
	?>
	<div class="wrap">
		<div id="icon-plugins" class="icon32"><br /></div>
		<h2><?php echo _e('Uninstall'); ?></h2>
		<p><?php printf(__('<p>Are you sure that you want to uninstall WP Main Menu? It will erase all WP Main Menu\'s data in the DataBase.</p><p>Click <a href="%s">here</a> if you want to delete WP Main Menu\'s data. After that you must deactivate WP Main Menu and finally, you must delete the folder <strong>%s</strong>.</p><p>If you don\'t do that steps, you will restart WP Main Menu, but you will not delete it.</p>', 'wp-main-menu'), $uninstall_url, $wp_main_menu_path[4]); ?>
	</div>
	<?php
}

?>