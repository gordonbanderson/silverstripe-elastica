$ElasticSearchForm

<% if $SearchResults %>
<ul>
<% loop $SearchResults %>
<li>
<h5>$Title</h5>
<% loop $ElasticaSearchHighlights %>$Snippet &hellip;<% end_loop %>
</li>
<% end_loop %>
</ul>
<% else %>
No search results
<% end_if %>

<% include SearchPagination %>
