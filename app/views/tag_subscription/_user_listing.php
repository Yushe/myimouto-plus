<?php if (empty($this->user->tag_subscriptions)) : ?>
  <?= $this->t('sub_none') ?>
<?php else: ?>
  <?= $this->tag_subscription_listing($user) ?>
<?php endif ?>
  
<?php if (current_user()->id == $this->user->id) : ?>
  (<?= $this->linkTo($this->t('sub_edit'), 'tag_subscription#index') ?>)
<?php endif ?>
