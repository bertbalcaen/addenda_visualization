<?php  
drupal_add_js(drupal_get_path('theme','addenda_zen') . '/explore-visualization/js/underscore-min.js');
drupal_add_js(drupal_get_path('theme','addenda_zen') . '/explore-visualization/js/pourover.js');
drupal_add_js(drupal_get_path('theme','addenda_zen') . '/explore-visualization/js/jquery.pagination.js');
?>
<script>
"use strict";

jQuery(function() {

	var collection;
	var view;
	var uiElements = [];
	var ITEMS_PER_PAGE = 7 * 16;

	jQuery('#filters').hide();

	jQuery.get('<?php print path_to_theme(); ?>/explore-visualization/memories.json', function(memories){
			collection = new PourOver.Collection(memories);
			initView();
			initUI();
		}
	);

	function initUI(){

		// console.log(collection);

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
		for (var i = filterNames.length - 1; i >= 0; i--) {
			var filterName = filterNames[i];
			// need to pass in all possible values when creating the filter
			var filterValues = [];
			for (var j = collection.items.length - 1; j >= 0; j--) {
				var memory = collection.items[j];
				var values = memory[filterName];
				if (typeof values == 'string') {
					values = [values];
				}
				for (var k = values.length - 1; k >= 0; k--) {
					var value = values[k]
					if (filterValues.indexOf(value) == -1 && value.length && value != '--none--') {
						filterValues.push(value);
					}
				}
			}
			collection.addFilters(PourOver.makeExactFilter(filterName, filterValues));
			// create UI element
			var select = new PourOver.UI.SimpleSelectElement({
				filter: collection.filters[filterName],
				template: function(vals){
					// get list of items
					var items = [];
					// console.log("filterName: " + this.filter.name);
					// if this filter is active, then temporarily clear it so we can calculate the number of matches for each possibility
					if (vals.state) {
						this.filter.clearQuery();
					}
					for(var key in this.filter.possibilities){
						var possibility = this.filter.possibilities[key];
						// console.log(vals);
						// console.log(this.filter.name + ' > ' + possibility.value);
						// number of matches = result of current filters in combination this possibility for the filter
						var currentMatchSet = view.match_set;
						var itemMatchSet = this.filter.collection.getFilteredItems(this.filter.name, possibility.value);
						var matchSet = currentMatchSet.and(itemMatchSet);
						// console.log("currentMatchSet");
						// console.log(currentMatchSet);
						// console.log("itemMatchSet");
						// console.log(itemMatchSet);
						// console.log("matchSet");
						// console.log(matchSet);
						var numMatches = matchSet.cids.length;
						var selected = false;
						if (vals.state && vals.state[0] == possibility.value) {
							selected = true;
						}
						var item = {
							name: possibility.value,
							numMatches: numMatches,
							selected: selected
						};
						items.push(item);
					}
					if (vals.state) {
						this.filter.query(vals.state[0]);
					}
					items.sort(function(a, b){
						return a.numMatches - b.numMatches;
					});
					// set HTML for list
					var html = '';
					html += '<ul data-filter-name="' + this.filter.name + '">';
					for (var i = items.length - 1; i >= 0; i--) {
						var item = items[i];
						if (item.numMatches > 0 && typeof item.name == 'string') {
							var selectedClass = '';
							if (item.selected) {
								selectedClass = ' class="selected" ';
							}
							html += '<li data-filter-item-value="' + item.name + '"' + selectedClass + '>' + item.name + ' <em>' + item.numMatches + '</em></li>';
						}
					}
					html += '</ul>';
					var filterElement = jQuery('[data-filter-name="' + this.filter.name + '"]');
					filterElement.find('.items').html(html);
					// clear btn
					var clearBtn = filterElement.find('.clear');
					if (!vals.state) {
						clearBtn.html("");
						clearBtn.css('visibility', 'hidden');
					} else {
						clearBtn.html('<span title="clear filter">x ' + vals.state[0] + '</a>');
						clearBtn.css('visibility', '');
					}
				}
			});
			uiElements.push(select);
			// make column widths fixed
			setTimeout(function(){
				jQuery('.filter').each(function(index, el){
					jQuery(el).css('width', (jQuery(el).width()) + 'px');
				});
			}, 1000);
		}

		// filter when clicking on an item
		jQuery('.filter li').live('click', function(){
			var filterName = jQuery(this).parents('[data-filter-name]').attr('data-filter-name');
			var filter = collection.filters[filterName];
			var val = jQuery(this).attr('data-filter-item-value');
			// console.log(" clicked filter: " + filterName + " value " + val);
			filters[filterName] = [];
			filters[filterName].push(val);
			filter.query(val);
			// update UI
			renderUI();
		});

		// clear filter when clicking on button
		jQuery('.filter .clear').click(function(){
			var filterName = jQuery(this).parents('[data-filter-name]').attr('data-filter-name');
			clearFilter(filterName);
			renderUI();
		});

		// clear filter when clicking on button
		jQuery('.clearAllFilters').live('click', function(){
			clearFilters();
			return false;
		});

		function clearFilter(filterName){
			var filter = collection.filters[filterName];
			filter.clearQuery();
		}

		function clearFilters(){
			for (var i = 0; i < filterNames.length; i++) {
				clearFilter(filterNames[i]);
			}
			renderUI();
		}

		// toggle filters
		jQuery('.toggleFilters').live('click', function(){
			jQuery('#filters').toggle();
			return false;
		});

		renderUI();

	}

	function renderUI(){
		for (var i = uiElements.length - 1; i >= 0; i--) {
			uiElements[i].render();
		}
		updatePagination();
		updateNumResults();
		// updateHistory();
	}

	function updatePagination(){

		jQuery("#pagination").pagination(view.match_set.cids.length, {
			callback: function (page_index, jq){
				view.setPage(page_index);
				return false;
			},
			items_per_page: ITEMS_PER_PAGE,
			// num_display_entries: 10,
			// num_edge_entries: 2,
			prev_text: '« previous',
			next_text: 'next »'
		});

		if (view.match_set.cids.length < ITEMS_PER_PAGE) {
			jQuery("#pagination").hide();
		} else {
			jQuery("#pagination").show();
		}

		if (view.match_set.cids.length === 0) {
			jQuery("#results").html('<p>No results found.</p>');
		}

	}

	function updateNumResults(){
		var numTotal = collection.items.length;
		var numMatches = view.match_set.cids.length;
		var html = '';
		// html += '<p>';
		html += numMatches + ' of ' + collection.items.length + ' memories filtered (' + Math.ceil((numMatches/numTotal) * 100) + '%)';
		if (numMatches != numTotal) {
			html += ' <a href="#" class="clearAllFilters">Clear filters</a>';
		}
		// html += '</p>';
		jQuery("#numResults").html(html);	
	}

	function updateHistory(){

		if (window.history && window.history.pushState){
			var frags = [];
			if(filters['sector']){
				frags.push('sector=' + encodeURIComponent(filters['sector']));
			}
			if(filters['occupation']){
				frags.push('beroep=' + encodeURIComponent(filters['occupation']));
			}
			if(filters['region']){
				frags.push('regio=' + encodeURIComponent(filters['region']));
			}
			var fragsStr = '';
			if (frags) {
				fragsStr = '?' + frags.join('&');
			}
			// console.log(fragsStr);
			history.pushState({}, "Zoeken", "/zoeken" + fragsStr);
		}

	}

	function initView(){

		var GridView = PourOver.View.extend({
			render: function(){
				// update results
				var current_items = this.getCurrentItems();
				// console.log("num items found: " + current_items.length);
				var html = '';
				current_items.forEach(function(memory, i) {
					var title = '';
					title += 'name: ' + memory.title + "\n";
					title += 'id: ' + memory.id + "\n";
					title += 'duration: ' + memory.duration + " seconds\n";
					title += 'start_time: ' + memory.start_time + " seconds\n\n";
					for (var j = 0; j < collection.filters.length; j++) {
						var filter = collection.filters[j];
						title += filter.name + ': ' + memory[filter.name] + "\n";
					}
					var url = 'http://staging03.dough.be/addenda/node/' + memory.id;
					html += '<a href="' + url + '" target="_blank" + title="' + title + '" videoUrl="' + memory.url + '" start_time="' + memory.start_time + '"><img src="http://staging03.dough.be/addenda/sites/default/files/' + memory.thumb + '" width="75"></a>';
				});
				jQuery("#results").html(html);
			}
		});

		view = new GridView("grid_view", collection, {page_size: ITEMS_PER_PAGE});
		view.on("update", function(){
			view.render();
		});

		var SortByIdDesc = PourOver.Sort.extend({
			attr: "id",
			fn: function(a,b){
				return a-b;
			}
		});
		var sortByIdDesc = new SortByIdDesc("by_id_desc");
		collection.addSorts([sortByIdDesc]);
		view.setSort("by_id_desc");
		
		view.render();

		jQuery('#filters').show();

	}

});
</script>
<div class="clearfix">
	<div id="search" style="width: 75%; float: left;">
		<label for="searchKeyword">Search</label>
		<input type="text" name="searchKeyword" id="searchKeyword" placeholder="Keywords">
		<button class="clearSearchKeyword">Clear</button>
	</div>
	<div style="width: 25%; float: left; text-align: right;">
		<button class="toggleFilters">Toggle filters</button>
	</div>
</div>
<div id="filters" class="clearfix">
	<div class="filterGroup">
		<h1>Action</h1>
		<div class="filter" data-filter-name="subject">
			<h2>Subject</h2>
			<div class="clear"></div>
			<div class="items">
			</div>
		</div>
		<div class="filter" data-filter-name="action">
			<h2>Action</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
		<div class="filter" data-filter-name="location">
			<h2>Location</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
		<div class="filter last" data-filter-name="object">
			<h2>Object</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
	</div>
	<div class="filterGroup">
		<h1>Specification</h1>
		<div class="filter" data-filter-name="category">
			<h2>Category</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
		<div class="filter" data-filter-name="date">
			<h2>Date</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
		<div class="filter" data-filter-name="country">
			<h2>Country</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
		<div class="filter last" data-filter-name="geography">
			<h2>Geography</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
	</div>
	<div class="filterGroup last">
		<h1>Archetype</h1>
		<div class="filter" data-filter-name="archetype">
			<h2>Formal</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
		<div class="filter last" data-filter-name="archetype2">
			<h2>Thematic</h2>
			<div class="clear"></div>				
			<div class="items">
			</div>
		</div>
	</div>
</div>
<div class="clearfix" id="paginationAndNumResults">
	<div id="pagination" class="pager clearfix" style="width: 75%; float: left;"></div>
	<div id="numResults" style="width: 25%; float: left;"></div>
</div>
<div id="results">
</div>

<style>
#filters{
	/*width: 100%;*/
	overflow: hidden;
}

#search label{
	display: inline;
}

.filterGroup{
	float: left;
	border-right: 1px solid #8d887a;
	padding-right: .5em;
	margin-right: 1em;
}
.filterGroup.last{
	border: none;
}
.filterGroup h1{
	font-size: 1.17em;
	margin: 0;
	color: #8d887a;
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
	text-transform: lowercase;
}
.filter .items{
	height: 150px;
	overflow-x: hidden;
	overflow-y: scroll;
	text-transform: lowercase;
	font-size: .75em;
	border-right: 1px solid #8d887a;
	margin: .5em 0;
	font-size: .75em;
}
.filter .items ul{
	list-style: none;
	padding: 0;
	margin: 0;
	cursor: pointer;
	line-height: 1.25em;
}
.filter.last{
	margin-right: 0;
}
.filter.last .items{
	border-right: none;
}
.filter .items li{
	width: 100%;
	white-space: nowrap;
}
.filter .items li:hover{
	background: #8d887a;
}

.filter .items li em{
	color: #8d887a;
}
.filter .items li:hover em{
	color: #fff;
}
.filter .items .selected{
	font-weight: bold;
}

#paginationAndNumResults{
	margin: 1em 0 .25em 0;
}

#results a{
	display: block;
	width: 75px;
	height: 51px;
	float: left;
}
#results a.selected img{
	-webkit-filter: grayscale;
	-webkit-filter: brightness(50%); 
}

#numResults{
	text-align: right;
}

.pager {
	font-family: Bitter;
	font-size: 16px;
}
		
.pager a {
	text-decoration: none;
}

.pager a, .pager span {
	display: block;
	float: left;
	padding: 0 0.5em;
	margin-right: 5px;
	margin-bottom: 5px;
	border-right: 1px solid #8d887a;
	width: 20px;
	color: #8d887a;
}

.pager .prev, .pager .next{
	cursor: pointer;
}

.pager .prev{
	width: 70px;
	padding-left: 0;
}

.pager .next{
	border-right: none;
	width: 40px;
}

.pager .current{
	font-weight: bold;
	color: #000;
}

.pager :hover {
	/*text-decoration: underline;*/
}

.pager .current.prev, .pager .current.next{
	font-weight: normal;
	color: #8d887a;
	border-color: #8d887a;
}
</style>