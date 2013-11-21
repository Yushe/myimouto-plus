<?= $this->formFor($this->ad, function($f) { ?>
  <?= $this->partial('shared/error_messages', ['object' => $f->object()]) ?>

  <div>
    <?= $f->label('image_url') ?>
    <?= $f->textField('image_url') ?>
  </div>
  <div>
    <?= $f->label('width') ?>
    <?= $f->textField('width') ?>
  </div>
  <div>
    <?= $f->label('height') ?>
    <?= $f->textField('height') ?>
  </div>
  <div>
    <?= $f->label('referral_url') ?>
    <?= $f->textField('referral_url') ?>
  </div>
  <div>
    <?= $f->label('ad_type') ?>
    <?= $f->select('ad_type', ['Horizontal' => 'horizontal', 'Vertical' => 'vertical']) ?>
  </div>
  <div>
    <?= $f->label('status') ?>
    <?= $f->textField('status') ?>
  </div>
  <?php if ($this->request()->action() == 'edit') : ?>
    <div>
      <?= $f->label('reset_hit_count') ?>
      <?= $f->checkBox('reset_hit_count') ?>
    </div>
  <?php endif ?>
  <div>
    <?= $f->submit() ?>
  </div>
<?php }) ?>
