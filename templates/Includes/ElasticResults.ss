<% require css("elastica/css/elastica.css") %>
<div class="searchResults">
<% if $SearchResults.Count > 0 %>
<div class="resultsFound">
Page $SearchResults.CurrentPage of $SearchResults.TotalPages &nbsp;
($NumberOfResults <% _t('SearchPage.RESULTS_FOUND', ' results found') %> in $ElapsedTime seconds)
</div>
<% if $SuggestedQuery && not $IgnoreSuggestions %>
<p class="showingResultsForMsg">Showing results for <a href="./?q=$SuggestedQuery">$SuggestedQueryHighlighted</a></p>
<p class="searchOriginalQueryMsg">Search instead for <a href="$SearchInsteadForLink">$OriginalQuery</a></p>
<% end_if %>
<% loop $SearchResults %>
$RenderResult($Top.Link)
<% end_loop %>
</div>
<% else %>
<% if not $QueryIsEmpty %>
<div class="noResultsFound">
  <% _t('SearchPage.NO_RESULTS_FOUND', 'Sorry, your search query did not return any results') %>
</div>
<% end_if %>
<% end_if %>

<% if $SearchResults.MoreThanOnePage %>
<div id="PageNumbers">
    <div class="pagination">
        <% if $SearchResults.NotFirstPage %>
        <a class="prev" href="$SearchResults.PrevLink" title="View the previous page">&larr;</a>
        <% end_if %>
        <span>
            <% loop $SearchResults.PaginationSummary(4) %>
                <% if $CurrentBool %>
                <span class="current">$PageNum</span>
                <% else %>
                <% if $Link %>
	  					<a href="$Link" title="View page number $PageNum" class="go-to-page">$PageNum</a>
	  				<% else %>
	  					<span class="dotdotdot">&hellip;</span>
	  			<% end_if %>


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
<% if $QueryIsEmpty %>
<div class="contentForEmptySearch">$ContentForEmptySearch</div>
<% end_if %>
</div>

</div>


