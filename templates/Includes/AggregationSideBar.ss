<aside class="sidebar unit size1of4">
		<nav class="secondary">



				<% loop Aggregations %>
				<h3 class="heading">$Name <% if not $IsSelected %><i class="fi-play facetToggle">SHOW</i><% end_if %></h3>
				<ul>
				<span <% if $IsSelected %><% else %>style="display:none;"<% end_if %>>
				<% loop Buckets %>
				<li class="link"><% if $IsSelected %><a href="$URL">$Key &nbsp;<i class="fi-x"></i></a><% else %>
				<a href="$URL"><span class="arrow">&rarr;</span><span class="text">{$Key}&nbsp;<span class="count">($DocumentCount)</span><span></a><% end_if %>
				</li>
				<% end_loop %>
				</span>
				<li class="divider">&nbsp;</li>
				</ul>
				<% end_loop %>


		</nav>
</aside>

