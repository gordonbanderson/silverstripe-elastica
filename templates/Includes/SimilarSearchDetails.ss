<% if $SimilarTo %>
<div class="similarSearchInfo">
<span class="remove" id="cancelSimilar">'</span>
Similar to: $SimilarTo.Title

<div class="terms">
<ul>
<% loop SimilarSearchTerms %>
<li class="fieldName">{$FieldName}:</li>
<% loop $Terms %><li>$Term</li><% end_loop %>
<% end_loop %>
</ul>
</div>


</div>
<% end_if %>
