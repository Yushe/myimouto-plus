<?php foreach (range('a', 'z') as $letter) : ?>
  <?= $this->linkTo($letter, ['action' => "index", 'type' => $this->params()->type, 'order' => $this->params()->order, 'letter' => $letter]) ?>
<?php endforeach ?>
