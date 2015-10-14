<div class="searchResult" id="$ClassName $ID">
<a href="$Link"><h4><% if $SearchHighlightsByField.Title %><% loop $SearchHighlightsByField.Title %>$Snippet<% end_loop %><% else %>$Title<% end_if %></h4></a>
<% loop $SearchHighlights %>$Snippet &hellip;<% end_loop %>
<div class="searchFooter">
<% if $SearchHighlightsByField.Link %>
<% loop $SearchHighlightsByField.Link %>$Snippet<% end_loop %>
<% else %>
  $AbsoluteLink
<% end_if %>

- $LastEdited.Format(d/m/y)
</div>
</div>
