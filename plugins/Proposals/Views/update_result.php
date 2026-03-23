<div class="p15">
  <h4 class="mb10">Proposals update</h4>

  <?php if (!empty($result) && get_array_value($result, "success")) { ?>
    <div class="alert alert-success mb15">Update executed successfully.</div>
  <?php } else { ?>
    <div class="alert alert-danger mb15">Update finished with errors.</div>
  <?php } ?>

  <div class="mb10"><strong>Tables checked:</strong></div>
  <?php $tables = isset($result) ? get_array_value($result, "tables") : array(); ?>
  <?php if ($tables && is_array($tables) && count($tables)) { ?>
    <ul class="mb15">
      <?php foreach ($tables as $table_name) { ?>
        <li><?php echo esc($table_name); ?></li>
      <?php } ?>
    </ul>
  <?php } else { ?>
    <div class="text-off mb15">No tables detected in SQL.</div>
  <?php } ?>

  <?php $errors = isset($result) ? get_array_value($result, "errors") : array(); ?>
  <?php if ($errors && is_array($errors) && count($errors)) { ?>
    <div class="mb10"><strong>Errors:</strong></div>
    <ul class="mb0">
      <?php foreach ($errors as $error) { ?>
        <li><?php echo esc($error); ?></li>
      <?php } ?>
    </ul>
  <?php } ?>
</div>
