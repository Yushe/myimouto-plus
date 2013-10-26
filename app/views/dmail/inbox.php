<h4><?= $this->t('.title') ?></h4>

<?php if ($this->dmails->blank()) : ?>
  <p><?=$this->t('.empty') ?></p>
<?php else: ?>
  <div class="mail">
    <table width="100%" class="highlightable">
      <thead>
        <tr>
          <th width="15%"><?=$this->t('.table.from') ?></th>
          <th width="15%"><?=$this->t('.table.to') ?></th>
          <th width="55%"><?=$this->t('.table.title') ?></th>
          <th width="15%"><?=$this->t('.table.when') ?></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($this->dmails as $dmail) : ?>
          <tr class="<?= $this->cycle('even', 'odd') ?>" id="row-<?= $dmail->id ?>">
            <td><?= $this->h($dmail->from->name) ?></td>
            <td><?= $this->h($dmail->to->name) ?></td>
            <td>
              <?php if ($dmail->from_id == current_user()->id) : ?>
                <?= $this->linkTo($this->h($dmail->title()), ['action' => "show", 'id' => $dmail->id], ['class' => "sent"]) ?>
              <?php else: ?>
                <?php if ($dmail->has_seen) : ?>
                  <?= $this->linkTo($this->h($dmail->title()), ['action' => "show", 'id' => $dmail->id], ['class' => "received"]) ?>
                <?php else: ?>
                  <strong><?= $this->linkTo($this->h($dmail->title()), ['action' => "show", 'id' => $dmail->id], ['class' => "received"]) ?></strong>
                <?php endif ?>
              <?php endif ?>
            </td>
            <td><?= $this->t(['time.x_ago', 't' => $this->timeAgoInWords($dmail->created_at)]) ?></td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
<?php endif ?>

<div id="paginator" style="margin-bottom: 1em;">
  <?= $this->willPaginate($this->dmails) ?>
</div>

<?= $this->partial("footer") ?>
