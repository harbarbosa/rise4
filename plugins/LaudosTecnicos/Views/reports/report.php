<?php
$rows = array_values(is_array($rows ?? null) ? $rows : array());
$report = trim((string) ($report ?? ''));
?>
<div class="p-3">
    <h3 class="mb-3">Relatorio: <?php echo esc($report); ?></h3>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <?php if ($rows) { ?>
                        <?php foreach (array_keys((array) $rows[0]) as $key) { ?>
                            <th><?php echo esc($key); ?></th>
                        <?php } ?>
                    <?php } else { ?>
                        <th>Sem dados</th>
                    <?php } ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) { ?>
                    <tr>
                        <?php foreach ((array) $row as $cell) { ?>
                            <td><?php echo esc(is_scalar($cell) ? (string) $cell : laudostecnicos_safe_json($cell)); ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
