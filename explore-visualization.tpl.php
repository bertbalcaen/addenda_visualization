<?php  
drupal_add_js(drupal_get_path('theme','addenda_zen') . '/explore-visualization/js/underscore-min.js');
drupal_add_js(drupal_get_path('theme','addenda_zen') . '/explore-visualization/js/pourover.js');
?>
<script>
jQuery(function() {

	jQuery.get('<?php print path_to_theme(); ?>/explore-visualization/memories.json', function(memories){
			init(memories);
		}
	);

	function init(memories){

		var collection = new PourOver.Collection(memories);

		// build filters
		var filterNames = [
			'subject',
			'action',
			'location',
			'object',
			'category',
			'date',
			'country',
			'geography',
			'archetype',
			'archetype2',
		];
		var filters = [];
		for (var i = filterNames.length - 1; i >= 0; i--) {
			var filterName = filterNames[i];
			// need to pass in all possible values when creating the filter
			var values = [];
			for (var j = memories.length - 1; j >= 0; j--) {
				var memory = memories[j];
				if (values.indexOf(memory[filterName]) == -1) {
					values.push(memory[filterName]);
				}
			}
			filters[filterName] = PourOver.makeExactFilter("category", values);
		}
		collection.addFilters(filters);

		var GridView = PourOver.View.extend({
			render: function(){
				var current_items = this.getCurrentItems();
				console.log("num items found: " + current_items.length);
				var html = '';
				current_items.forEach(function(memory, i) {
					var title = '';
					title += 'name: ' + memory.title + "\n";
					title += 'id: ' + memory.id + "\n";
					title += 'duration: ' + memory.duration + " seconds\n";
					title += 'start_time: ' + memory.start_time + " seconds\n\n";
					for (var j = 0; j < filterNames.length; j++) {
						title += filterNames[j] + ': ' + memory[filterNames[j]] + "\n";
					}
					var url = 'http://staging03.dough.be/addenda/node/' + memory.id;
					html += '<a href="' + url + '" target="_blank" + title="' + title + '" videoUrl="' + memory.url + '" start_time="' + memory.start_time + '"><img src="http://staging03.dough.be/addenda/sites/default/files/' + memory.thumb + '" width="50"></a>';
				});
				jQuery("#results").html(html);
			}
		});
		var view = new GridView("grid_view", collection, {page_size: 100});
		view.on("update", function(){
			view.render(); 
		});
		view.render();

	}

});
</script>
<div id="filters" class="clearfix">
	<div class="filterGroup">
		<h1>Action</h1>
		<div class="filter">
			<h2>Subject</h2>
			<div class="clear"></div>
			<div id="f_subject" class="items">
			</div>
		</div>
		<div class="filter">
			<h2>Action</h2>
			<div class="clear"></div>				
			<div id="f_action" class="items">
			</div>
		</div>
		<div class="filter">
			<h2>Location</h2>
			<div class="clear"></div>				
			<div id="f_location" class="items">
			</div>
		</div>
		<div class="filter last">
			<h2>Object</h2>
			<div class="clear"></div>				
			<div id="f_object" class="items">
			</div>
		</div>
	</div>
	<div class="filterGroup">
		<h1>Specification</h1>
		<div class="filter">
			<h2>Category</h2>
			<div class="clear"></div>				
			<div id="f_category" class="items">
			</div>
		</div>
		<div class="filter">
			<h2>Date</h2>
			<div class="clear"></div>				
			<div id="f_date" class="items">
			</div>
		</div>
		<div class="filter">
			<h2>Country</h2>
			<div class="clear"></div>				
			<div id="f_country" class="items">
			</div>
		</div>
		<div class="filter last">
			<h2>Geography</h2>
			<div class="clear"></div>				
			<div id="f_geography" class="items">
			</div>
		</div>
	</div>
	<div class="filterGroup last">
		<h1>Archetype</h1>
		<div class="filter">
			<h2>Formal</h2>
			<div class="clear"></div>				
			<div id="f_archetype" class="items">
			</div>
		</div>
		<div class="filter last">
			<h2>Thematic</h2>
			<div class="clear"></div>				
			<div id="f_archetype2" class="items">
			</div>
		</div>
	</div>
</div>
<div class="clearfix">
	<div id="numResults">
	</div>
	<div id="results">
	</div>
</div>

<style>
#filters{
	background: #fff;
	overflow: auto;
	padding: 1em;
}
.filterGroup{
	float: left;
	border-right: 1px solid #ccc;
	padding-right: .5em;
	margin-right: 1em;
}
.filterGroup.last{
	border: none;
}
.filterGroup h1{
	font-size: 1.17em;
	margin: 0;
	color: #ccc;
}
.filterGroup h2{
	margin: 0;
	font-size: 1em;
	font-weight: bold;
}
.filter{
	float: left;
	/*width: 120px;*/
	margin-right: .5em;
}
.filter .clear{
	height: 1em;
	cursor: pointer;
	font-size: .75em;
	color: red;
}
.filter .items{
	height: 300px;
	overflow-x: hidden;
	overflow-y: scroll;
	text-transform: lowercase;
	font-size: .75em;
	border-right: 1px solid #ccc;
	margin: .5em 0;
	font-size: .75em;
}
.filter.last{
	margin-right: 0;
}
.filter.last .items{
	border-right: none;
}
.filter .items .item{
	padding-bottom: .35em;
	width: 100%;
	cursor: pointer;
	white-space: nowrap;
}
.filter .items .item.over{
	background: #ccc;
}
.filter .items .item .bar{
	height: 2px; 
	background: #ccc; 
	position: relative; 
	top: 0px;	
}
.filter .items .item.over .bar{
	background: #fff;
}
.filter .items .item i{
	color: #ccc;
}
.filter .items .item.over i{
	color: #fff;
}
.filter .items .selected{
	font-weight: bold;
}

#results a{
	display: block;
	width: 50px;
	height: 34px;
	float: left;
}
#results a.selected img{
	-webkit-filter: grayscale;
	-webkit-filter: brightness(50%); 
}
#numResults{
	color: #fff;
	padding: 1em;
	font-size: .75em;
	text-align: right;
}
</style>