Installation
============

1. Replace /sites/all/themes/addenda_zen/templates/views-view--explore--page.tpl.php with:

`<?php require(dirname(__FILE__) . '/../explore-visualization/explore-visualization.tpl.php'); ?>`

2. The visualization loads its data from the memories.json file. This file needs to be refreshed from time to time by calling /sites/all/themes/addenda_zen/explore-visualization/create-data.php (for example in a cron job or hook).

3. Optional: enable GZIP compression for JSON by adding this line in /.htaccess:

`AddOutputFilterByType DEFLATE  application/json`

This will greatly reduce the download size of the JSON data for the visualization.

Lightbox
========

Memory detail page is shown in a lightbox. Simple solution to prevent the navigation from showing up in the lightbox: added and `<?php if (empty($_GET['lightbox'])): ?>` around the `<header>` in /sites/all/themes/addenda_zen/templates/page.tpl.php.