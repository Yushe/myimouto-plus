<?= $this->provide('title', $this->t('.title')) ?>
<div id="tag-list" style="margin-bottom: 1em;">
  <?php if ($this->tags->none()) : ?>
    <h4><?= $this->t('tag_cloud') ?></h4>
  <?php endif ?>

  <?= $this->cloud_view($this->tags) ?>
</div>

<?= $this->partial("footer") ?>
