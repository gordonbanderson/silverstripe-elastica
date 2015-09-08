<% require css("elastica/css/elastica.css") %>

<div class="searchResults">

<% if $SearchResults.Count > 0 %>
<div class="resultsFound">
Page $SearchResults.CurrentPage of $SearchResults.TotalPages &nbsp;($SearchResults.Count <% _t('SearchPage.RESULTS_FOUND', ' results found') %> in $ElapsedTime seconds)
</div>
<% loop $SearchResults %>

<div class="searchResult">
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

<% end_loop %>

<% else %>

<div class="noResultsFound">
  <% _t('SearchPage.NO_RESULTS_FOUND', 'Sorry, your search query did not return any results') %>
  <% end_if %>
</div>

<% if $SearchResults.MoreThanOnePage %>
<div id="PageNumbers">
    <div class="pagination">
        <% if $SearchResults.NotFirstPage %>
        <a class="prev" href="$SearchResults.PrevLink" title="View the previous page">&larr;</a>
        <% end_if %>
        <span>
            <% loop $SearchResults.Pages %>
                <% if $CurrentBool %>
                $PageNum
                <% else %>
                <a href="$Link" title="View page number $PageNum" class="go-to-page">$PageNum</a>
                <% end_if %>
            <% end_loop %>
        </span>
        <% if $SearchResults.NotLastPage %>
        <a class="next" href="$SearchResults.NextLink" title="View the next page">&rarr;</a>
        <% end_if %>
    </div>
</div>
<% end_if %>


</div>
</div>
</div>
