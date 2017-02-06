{if $allow_view}
	<!-- customblockmenu -->
	{if count($menu)}
	<div id="customblockmenu" class="customblockmenu block">
		{if !empty($title)}
			<div class="title_block">{$title}</div>
		{/if}
		<ul>
		{foreach $menu as $key => $parent}
		<li class="parent">
			<a href="{$parent.url}">{$parent.title}</a>
			{if count($parent.child)}
				<span class="plus-minus"></span>
				<ul class="sub-menu">
					{foreach $parent.child as $k => $child}
					<li><a href="{$child.url}">{$child.title}</a></li>
					{/foreach}
				</ul>
			{/if}
		</li>
		{/foreach}
		</ul>
	</div>
	{/if}
	<!-- customblockmenu -->
{/if}