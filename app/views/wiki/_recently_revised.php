<div style="margin-bottom: 1em;">
  <h6>Recent Changes (<?= $this->linkTo("all", ['action' => "index", 'order' => "date"]) ?>)</h6>
  <ul>
    <?php foreach (WikiPage::order("updated_at desc")->limit(25)->take() as $page) : ?>
      <li><?= $this->linkTo($this->h($page->pretty_title()), ['controller' => "wiki", 'action' => "show", 'title' => $page->title]) ?></li>
    <?php endforeach ?>
  </ul>
</div>
