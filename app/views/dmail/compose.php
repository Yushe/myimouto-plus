<h4><?= $this->t('.title') ?></h4>

<?= $this->partial("compose", ['from_id' => current_user()->id]) ?>

<?= $this->partial("footer") ?>
