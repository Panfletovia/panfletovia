<h2><?= __('Oops!') ?></h2>
<br/>
<p><?= __('Ocorreu um erro interno. Por favor, contate o administrador.') ?></p>
<br/>
<a class="btn btn-info" onclick="history.back();return true;"><?= __('Voltar') ?></a>
<br/>
<br/>
<? if (Configure::read('debug') > 0): ?>
    <p class="error">
        <strong><?php echo __d('cake_dev', 'Error'); ?>: </strong>
        <?php echo h($error->getMessage()); ?>
        <br>

        <strong><?php echo __d('cake_dev', 'File'); ?>: </strong>
        <?php echo h($error->getFile()); ?>
        <br>

        <strong><?php echo __d('cake_dev', 'Line'); ?>: </strong>
        <?php echo h($error->getLine()); ?>
    </p>
    <?= $this->element('exception_stack_trace') ?>
    <?

endif?>
