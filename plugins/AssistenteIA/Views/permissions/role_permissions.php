<div class="mb15">
    <h5><?php echo app_lang('assistente_ia'); ?></h5>
    <?php foreach (array(
        'assistente_ia_access' => 'Acessar o Assistente IA',
        'assistente_ia_view_history' => 'Visualizar histórico próprio',
        'assistente_ia_manage_settings' => 'Gerenciar configurações do Assistente IA',
        'assistente_ia_execute_actions' => 'Executar ações pelo Assistente IA',
    ) as $key => $label): ?>
        <div class="form-group">
            <label><input type="checkbox" name="<?php echo $key; ?>" value="1" <?php echo \get_array_value($permissions, $key) ? 'checked' : ''; ?>> <?php echo $label; ?></label>
        </div>
    <?php endforeach; ?>
</div>
