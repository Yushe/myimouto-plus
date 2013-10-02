<div class="container" style="width:750px;">
<h1>Compile assets</h1>

  <div class="well" style="text-align:center;">
  <?php if ($this->error) : ?>
  <div class="alert alert-error"><?= $this->error ?></div>
  <?php endif ?>

  <?= $this->formTag([], ['style' => 'margin-bottom:0px'], function(){ ?>
      <input type="hidden" name="all" value="1" />
      <button type="submit" class="btn btn-primary">Compile all</button>
  <?php }) ?>
  </div>
  <div style="text-align:center;">Or</div>
  <br />
  <div class="well">
  <?= $this->formTag([], ['style' => 'margin-bottom:0px;'], function(){ ?><table class="table" style="width:100%;margin-bottom:0px;">
      <thead>
          <tr>
              <th>Assets folder</th>
              <th>File name</th>
          </tr>
      </thead>
      <tbody>
          <tr>
              <td><?= Rails::application()->config()->assets->prefix . '/' ?></td>
              <td><?= $this->textFieldTag('file', $this->params()->file, ['size' => 40, 'autofocus', 'placeholder' => 'E.g. application.css']) ?></td>
          </tr>
          <tr><td colspan="3" style="text-align:center;"><?= $this->submitTag('Compile file', ['class' => 'btn btn-primary']) ?></td></tr>
      </tbody>
  </table><?php }) ?>
  </div>
</div>
