<div class="searchResult" id="{$ClassName}_$ID">
<a href="$Link"><h4><% if $SearchHighlightsByField.Title_standard %>asdfsadf <% loop $SearchHighlightsByField.Title_standard %>$Snippet<% end_loop %><% else %>$Title<% end_if %></h4></a>
<% loop $SearchHighlights %>$Snippet &hellip;<% end_loop %>

<div class="searchFooter">
<% if $SearchHighlightsByField.Link_standard %>
<% loop $SearchHighlightsByField.Link_standard %>$Snippet<% end_loop %>
<% else %>
  $AbsoluteLink
<% end_if %>

- $LastEdited.Format(d/m/y)
- <a href="$ContainerLink/similar/$ClassName/$ID">Similar</a>
</div>

</div>
