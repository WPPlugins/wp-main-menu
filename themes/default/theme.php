<?php function wp_main_menu_create_menu($menu2) { ?>	
	
	<ul>
	
	<?php foreach ($menu2 as $key => $value): if ($value['status'] != 'draft'): ?>
	<?php $link = wpmm_asign_variables($value); ?>
			
		<li>
			
		<a href="<?php echo $link['url']; ?>"><?php echo $link['name']; ?></a>
			
		<?php if ($link['sublinks'] != '') wp_main_menu_create_menu($link['sublinks']); ?>
			
		</li>
	
	<?php endif; endforeach; ?>
	
	</ul>	
			
<?php	 } ?>