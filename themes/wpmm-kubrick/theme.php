<?php function wp_main_menu_create_menu($menu2) { ?>	
	
	<div id="wp_main_menu">
		<ul id="wp_main_menu_parents">
	
	<?php foreach ($menu2 as $key => $value): if ($value['status'] != 'draft'): ?>
	<?php $link = wpmm_asign_variables($value); ?>
			
			<li>
			
			<a href="<?php echo $link['url']; ?>"><?php echo $link['name']; ?></a>
			
			<?php if ($link['sublinks'] != '') wp_main_menu_create_submenu($link['sublinks']); ?>
			
			</li>
	
	<?php endif; endforeach; ?>
	
		</ul>
	</div>
	
	<div class="wp_main_menu_clear"></div>
	
<?php	
}

function wp_main_menu_create_submenu($menu2) { ?>	

	<ul class="wp_main_menu_sublinks">
	
	<?php foreach ($menu2 as $key => $value): if ($value['status'] != 'draft'): ?>
	<?php $link = wpmm_asign_variables($value); ?>
			
			<li>
			
			<a href="<?php echo $link['url']; ?>"><?php echo $link['name']; if ($link['sublinks'] != '') echo '<span class="alignright">&raquo;</span>'; ?></a>
			
			<?php if ($link['sublinks'] != '') wp_main_menu_create_submenu($link['sublinks']); ?>
			
			</li>
	
	<?php endif; endforeach; ?>
	
	</ul>
	
<?php } ?>