<p>
<table class="appusage table table-striped table-bordered">
	<thead>
		<tr>
            <th data-i18n="listing.appusage.appname">App Name</th>
            <th data-i18n="listing.appusage.event">Event</th>
            <th data-i18n="listing.appusage.lastevent">Last Event</th>
            <th data-i18n="listing.appusage.count">Count</th>
            <th data-i18n="version">Version</th>
            <th data-i18n="path">Path</th>
            <th data-i18n="bundle_id">BundleID</th>
		</tr>
	</thead>
	<tbody>
<?php $appusageitemobj = new Appusage_model(); ?>
      <?php foreach($appusageitemobj->retrieveMany('serial_number=?', array($serial_number)) as $item): ?>
      <?php $name_url=url('module/inventory/items/'. rawurlencode($item->app_name)); ?>
        <tr>
          <td><a href='<?php echo $name_url; ?>'><?php echo $item->app_name; ?></a></td>
          <td><?php echo str_replace(array('quit','launch'), array('Quit','Launch'), $item->event); ?></td>
          <td><?php echo $item->last_time; ?></td>
          <td><?php echo $item->number_times; ?></td>
          <td><?php echo $item->app_version; ?></td>
          <td><?php echo $item->app_path; ?></td>
          <td><?php echo $item->bundle_id; ?></td>
        </tr>
  <?php endforeach; ?>	</tbody>
</table>

<script>
  $(document).on('appReady', function(e, lang) {

        // Initialize datatables
            $('.appusage').dataTable({
                "bServerSide": false,
                "aaSorting": [[0,'asc']]
            });
  });
</script>