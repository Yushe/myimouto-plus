<h4><?= $this->t('.title') ?></h4>
<p><?= $this->t(['.confirm', 'name' => $this->artist->name]) ?></p>

<?= $this->formTag([], ['level' => 'privileged'], function(){ ?>
  <?= $this->submitTag($this->t('buttons._yes')) ?>
  <?= $this->submitTag($this->t('buttons._no')) ?>
<?php }) ?>
