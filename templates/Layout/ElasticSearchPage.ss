<% include AggregationSideBar %>
<div class="content-container unit size3of4 lastUnit">
	<article>
		<h1>$Title</h1>
		<div class="content">$Content</div>

		<% include SimilarSearchDetails %>
		$SearchForm

		<% if $ErrorMessage %><div class="message error">$ErrorMessage</div> <% end_if %>

		<% if $SearchPerformed %>
		<% include ElasticResults %>
		<% end_if %>

	</article>
		$CommentsForm
</div>



