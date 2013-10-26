<h4><?= $this->t('record_add') ?><?= $this->h($this->user->pretty_name()) ?></h4>

<?= $this->formTag(function(){ ?>
  <?= $this->hiddenFieldTag("user_id", $this->user->id) ?>
  <table width="100%">
    <tbody>
      <tr>
        <th width="10%"><label><?= $this->t('record_pos') ?></label></th>
        <td width="90%"><?= $this->checkBox("user_record", "is_positive") ?></td>
      </tr>
      <tr>
        <th><label><?= $this->t('record_reason') ?></label></th>
        <td><?= $this->textArea('user_record', 'body', ['size' => "20x8"]) ?></td>
      </tr>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="2"><?= $this->submitTag($this->t('record_submit')) ?> <?= $this->buttonToFunction($this->t('record_cancel'), "location.back()") ?></td>
      </tr>
    </tfoot>
  </table>
<?php }) ?>
