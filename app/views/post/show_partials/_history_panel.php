<div>
  <h5><?= $this->t('post_history') ?></h5>
  <ul>
    <li><?= linkTo $this->t('.tags'), 'history#index', 'search' => 'post:#array($this->post.id)' ?></li>
    <li><?= linkTo $this->t('.notes'), 'note#history', 'post_id' => $this->post.id ?></li>
  </ul>
</div>
