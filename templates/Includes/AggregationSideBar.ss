<aside class="sidebar unit size1of4">
	<% if $Menu(2) %>
		<nav class="secondary">
			<% with $Level(1) %>
				<h3>
					$MenuTitle
				</h3>
				<ul>
					<% include SidebarMenu %>
				</ul>
			<% end_with %>
		</nav>
	<% end_if %>

	<nav class="secondary aggregationMenu">
		<h3>Aggregations</h3>
		<ul class="aggregations">
		<% loop Aggregations %>
			<% if $IsSelected %>
				<li class="aggTitle link selected">
				<% with Buckets.First %>
				<a href="$URL">$Key <span class="remove">&#39;</span></a>
				<% end_with %>
				$Name
				</li>
			<% else %>
				<li class="aggTitle link">$Name &nbsp;<span class="facetToggle rotate">&#93;</span></li>
				<ul>
				<% loop Buckets %>
				<li class="link"><% if $IsSelected %><a href="$URL">$Key &nbsp;<i class="fi-x"></i></a><% else %>
				<a href="$URL"><span class="arrow">&rarr;</span><span class="text">{$Key}&nbsp;<span class="count">($DocumentCount)</span><span></a><% end_if %>
				</li>
				<% end_loop %>
				</ul>
			<% end_if %>
		<% end_loop %>
		</ul>
	</nav>
</aside>

