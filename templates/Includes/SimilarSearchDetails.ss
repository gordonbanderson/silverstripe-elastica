<% if $SimilarTo %>
<div class="similarSearchInfo">
<span class="remove" id="cancelSimilar">'</span>
Similar to: $SimilarTo.Title
<div class="terms">
<ul><% loop $SimilarSearchTerms %><li>$Value</li><% end_loop %></ul>
</div>
</div>
<% end_if %>
