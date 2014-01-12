<?php if ($this->user->tag_subscriptions->none()) : ?>
  <?= $this->t('sub_none') ?>
<?php else: ?>
  <?= $this->tag_subscription_listing($this->user) ?>
<?php endif ?>
  
<?php if (current_user()->id == $this->user->id) : ?>
  (<?= $this->linkTo($this->t('sub_edit'), 'tag_subscription#index') ?>)
<?php endif ?>
