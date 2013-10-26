<div id="note-list">
  <?= $this->partial("post/posts", ['posts' => $this->posts]) ?>

  <div id="paginator">
    <?= $this->willPaginate($this->posts) ?>
  </div>

  <?= $this->partial("footer") ?>
</div>
