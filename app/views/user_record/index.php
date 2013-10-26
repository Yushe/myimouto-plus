<div id="user-record">
  <h4><?= $this->t('record_record') ?></h4>

  <table width="100%" class="highlightable" id="history">
    <thead>
      <tr>
        <th></th>
        <th><?=$this->t('record_user') ?></th>
        <th><?=$this->t('record_reporter') ?></th>
        <th><?=$this->t('record_when') ?></th>
        <th><?=$this->t('record_body') ?></th>
        <th><?=$this->t('record_action') ?></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($this->user_records as $rec) : ?>
        <tr class="<?= $this->cycle('even', 'odd') ?>" id="record-<?= $rec->id ?>">
          <td style="background: <?= $rec->is_positive ? '#3465a4' : '#cc0000' ?>;"><td>
            <?php if ($this->user) : ?>
              <?= $this->linkTo($this->h($rec->user->pretty_name()), ['controller' => "user", 'action' => "show", 'id' => $rec->user_id]) ?>
            <?php else: ?>
              <?= $this->linkTo($this->h($rec->user->pretty_name()), ['action' => "index", 'user_id' => $rec->user_id]) ?>
            <?php endif ?>
          </td>
          <td><?= $this->h($rec->reporter->pretty_name()) ?></td>
          <td><?= $this->t(['time.x_ago', 't' => $this->timeAgoInWords($rec->created_at)]) ?></td>
          <td class="change"><?= $this->format_text($rec->body) ?></td>
          <td>
            <?php if (current_user()->is_mod_or_higher() || current_user()->id == $rec->reported_by) : ?>
              <?= $this->linkToFunction($this->t('record_delete'), "UserRecord.destroy({$rec->id})") ?>
            <?php endif ?>
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <div id="paginator">
    <?= $this->willPaginate($this->user_records) ?>
  </div>

  <?= $this->partial("footer", ['user' => $this->user]) ?>
</div>
