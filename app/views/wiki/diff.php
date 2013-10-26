<?= $this->partial("sidebar") ?>

<div class="content wiki" id="wiki-diff">
  <h2 class="title"><?= $this->h($this->oldpage->pretty_title()) ?></h2>
  <p>
    Comparing versions
    <?= $this->linkTo($this->h($this->params()->from), ['action' => "show", 'title' => $this->params()->title, 'version' => $this->params()->from]) ?>
    and
    <?= $this->linkTo($this->h($this->params()->to), ['action' => "show", 'title' => $this->params()->title, 'version' => $this->params()->to]) ?>.
  </p>
  <p><em>Legend:</em> <del>old text</del> <ins>new text</ins></p>
  <div id="body">
    <?= $this->difference ?>
  </div>
</div>

<?= $this->partial("footer") ?>
