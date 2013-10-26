<h3><?= $this->t('.title') ?></h3>
<ul>
<?php $this->users.each do |u| ?>
  <li><?= linkTo $this->h(u.pretty_name), 'post#index', 'tags' => 'vote:3:#{u.name} order:vote' ?></li>
<?php end ?>
</ul>

<?= $this->partial('footer') ?>
