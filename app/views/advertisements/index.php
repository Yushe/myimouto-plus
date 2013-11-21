<h4><?= $this->t('.title') ?></h4>

<?= $this->formTag($this->updateMultipleAdvertisementsPath(), function() { ?>
  <?php $ads = [] ?>
  <table>
    <thead>
      <tr>
        <th class="center"><?= $this->checkBoxTag('check_all', 'check_all', false, ['onClick' => "checkbox_toggle(this, 'advertisement_ids[]');"]) ?></th>
        <th>#</th>
        <th><?= $this->humanize('image_url') ?></th>
        <th><?= $this->humanize('referral_url') ?></th>
        <th><?= $this->humanize('width') ?></th>
        <th><?= $this->humanize('height') ?></th>
        <th><?= $this->humanize('ad_type') ?></th>
        <th><?= $this->humanize('status') ?></th>
        <th><?= $this->humanize('hit_count') ?></th>
        <th></th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($this->ads as $ad) : ?>
        <tr>
          <td class="center"><?= $this->checkBoxTag('advertisement_ids[]', $ad->id) ?></td>
          <td><?= $this->linkTo($ad->id, $ad) ?></td>
          <td><?= $this->linkTo($ad->image_url, $ad->image_url) ?></td>
          <td><?= $this->linkTo($ad->referral_url, $ad->referral_url) ?></td>
          <td><?= $ad->width ?></td>
          <td><?= $ad->height ?></td>
          <td><?= $ad->ad_type ?></td>
          <td><?= $ad->status ?></td>
          <td><?= $ad->hit_count ?></td>
          <td><?= $this->linkTo($this->t('buttons.edit'), $this->editAdvertisementPath($ad)) ?></td>
          <td><?= $this->linkTo($this->t('buttons.delete'), $ad, ['data' => ['confirm' => $this->t('confirmations.is_sure')], 'method' => 'delete']) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <?= $this->submitTag($this->t('.reset_hit_count'), ['name' => 'do_reset_hit_count']) ?>
  <?= $this->submitTag($this->t('buttons.delete'), ['name' => 'do_delete']) ?>
<?php }) ?>

<?= $this->linkTo($this->t('buttons.add'), $this->newAdvertisementPath()) ?>
<?= $this->willPaginate($this->ads) ?>

<script type="text/javascript">
  function checkbox_toggle(source, name) {
    checkboxes = document.getElementsByName(name);
    for(var i in checkboxes)
      checkboxes[i].checked = source.checked;
  }
</script>
