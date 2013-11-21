<h4>Advertisement #<?= $this->ad->id ?></h4>

<div>
  <label><?= $this->humanize('id') ?></label>
  <?= $this->ad->id ?>
</div>
<div>
  <label><?= $this->humanize('image_url') ?></label>
  <?= $this->ad->image_url ?>
</div>
<div>
  <label><?= $this->humanize('referral_url') ?></label>
  <?= $this->ad->referral_url ?>
</div>
<div>
  <label><?= $this->humanize('width') ?></label>
  <?= $this->ad->width ?>
</div>
<div>
  <label><?= $this->humanize('height') ?></label>
  <?= $this->ad->height ?>
</div>
<div>
  <label><?= $this->humanize('ad_type') ?></label>
  <?= $this->ad->ad_type ?>
</div>
<div>
  <label><?= $this->humanize('status') ?></label>
  <?= $this->ad->status ?>
</div>
<div>
  <label><?= $this->humanize('hit_count') ?></label>
  <?= $this->ad->hit_count ?>
</div>

<?= $this->linkTo($this->t('buttons.edit'), $this->editAdvertisementPath($this->ad)) ?> 
<?= $this->linkTo($this->t('buttons.delete'), $this->ad, ['data' => ['confirm' => $this->t('confirmations.is_sure')], 'method' => 'delete']) ?> 
<?= $this->linkTo($this->t('buttons.back'), $this->advertisementsPath()) ?> 
