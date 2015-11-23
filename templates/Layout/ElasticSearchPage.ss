<% include AggregationSideBar %>
<div class="content-container unit size3of4 lastUnit">
	<article>
		<h1>$Title</h1>
		<div class="content">$Content</div>
		<% if $ErrorMessage %><div class="message error">$ErrorMessage</div>
		<% else %>
			<% include SimilarSearchDetails %>
			$SearchForm

			<% if $SearchPerformed %>
			<% include ElasticResults %>
			<% end_if %>
		<% end_if %>



	</article>
		$CommentsForm
</div>



