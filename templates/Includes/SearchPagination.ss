<% if $Results.MoreThanOnePage %>
<div id="PageNumbers">
    <div class="pagination">
        <% if $Results.NotFirstPage %>
        <a class="prev" href="$Results.PrevLink" title="View the previous page">&larr;</a>
        <% end_if %>
        <span>
            <% loop $Results.Pages %>
                <% if $CurrentBool %>
                $PageNum
                <% else %>
                <a href="$Link" title="View page number $PageNum" class="go-to-page">$PageNum</a>
                <% end_if %>
            <% end_loop %>
        </span>
        <% if $Results.NotLastPage %>
        <a class="next" href="$Results.NextLink" title="View the next page">&rarr;</a>
        <% end_if %>
    </div>
    <p>Page $Results.CurrentPage of $Results.TotalPages</p>
</div>
<% end_if %>
