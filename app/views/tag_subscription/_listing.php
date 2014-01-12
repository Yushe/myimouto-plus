<thead>
  <tr>
    <th></th>
    <th width="15%"><?=$this->t('sub_name') ?></th>
    <th width="70%"><?=$this->t('sub_tags') ?></th>
    <th width="10%"><?=$this->t('sub_vis') ?></th>
  </tr>
</thead>

<tfoot>
  <tr>
    <td></td>
    <td colspan="3">
      <?= $this->submitTag($this->t('sub_save')) ?>
      <input onclick="new Ajax.Request('<?= $this->urlFor(['tag_subscription#create', 'format' => 'js']) ?>', {asynchronous:true, evalScripts:true});" type="button" value="<?= $this->t('sub_add') ?>">
    </td>
  </tr>
</tfoot>

<tbody id="tag-subscription-body">
  <?php foreach ($this->tag_subscriptions as $tag_subscription) : ?>
    <?= $this->partial("listing_row", ['tag_subscription' => $tag_subscription]) ?>
  <?php endforeach ?>
</tbody>
