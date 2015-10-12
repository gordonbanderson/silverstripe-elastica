<% include AggregationSideBar %>
<div class="content-container unit size3of4 lastUnit">
	<article>
		<h1>$Title ESP</h1>
		<div class="content">$Content</div>
		$SearchForm

		<% if $SearchPerformed %>
		<% include ElasticResults %>
		<% end_if %>

	</article>
		$CommentsForm
</div>



