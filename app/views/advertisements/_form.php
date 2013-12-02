<?= $this->formFor($this->ad, function($f) { ?>
  <?php $action = $this->request()->action() ?>
  <?= $this->partial('shared/error_messages', ['object' => $f->object()]) ?>

  <table class="form">
    <tbody>
      <tr>
        <th width="150px;">Type</th>
        
        <td>
          <label for="type-image">Image</label>
          <input type="radio" name="type" id="type-image" checked />
          
          <label for="type-html">Html</label>
          <input type="radio" name="type" id="type-html" />
          
          <script>
          jQuery(function(){
            var $ = jQuery;
            $('#type-image').on('click', function() {
              if ($(this).is(':checked')) {
                $('.type-fields').hide();
                $('.type-image').show();
              }
            });
            $('#type-html').on('click', function() {
              if ($(this).is(':checked')) {
                $('.type-fields').hide();
                $('.type-html').show();
              }
            });
          });
          </script>
        </td>
      </tr>
    </tbody>
    
    <tbody>
      <tr>
        <th><?= $f->label('ad_type') ?></th>
        <td><?= $f->select('ad_type', ['Horizontal' => 'horizontal', 'Vertical' => 'vertical']) ?></td>
      </tr>
      <tr>
        <th><?= $f->label('status') ?></th>
        <td><?= $f->select('status', ['Active' => 'active', 'Disabled' => 'disabled']) ?></td>
      </tr>
      
      <?php if ($action == 'edit') : ?> 
      <tr>
        <th><?= $f->label('reset_hit_count') ?></th>
        <td><?= $f->checkBox('reset_hit_count') ?></td>
      </tr>
      <?php endif ?> 
    </tbody>

    <tbody class="type-fields type-image<?php if ($action != 'blank' || $f->object()->html) echo ' hide' ?>">
      <tr>
        <th><?= $f->label('image_url') ?></th>
        <td><?= $f->textField('image_url') ?></td>
      </tr>
      <tr>
        <th><?= $f->label('referral_url') ?></th>
        <td><?= $f->textField('referral_url') ?></td>
      </tr>
      <tr>
        <th><?= $f->label('width') ?></th>
        <td><?= $f->textField('width') ?></td>
      </tr>
      <tr>
        <th><?= $f->label('height') ?></th>
        <td><?= $f->textField('height') ?></td>
      </tr>
    </tbody>
    
    <tbody class="type-fields type-html<?php if ($action == 'blank' || !$f->object()->html) echo ' hide' ?>">
      <tr>
        <th><?= $f->label('html') ?></th>
        <td><?= $f->textArea('html', ['style' => 'height: 300px;']) ?></td>
      </tr>
    </tbody>
    
    <tbody>
      <tr>
        <td colspan="2"><?= $f->submit() ?></td>
      </tr>
    </tbody>
  </table>
<?php }) ?>

<style>
table.form [type=text], table.form textarea{
  width: 100%;
  box-sizing: border-box;
}
table.form tbody.hide {
  display:none;
}
</style>
