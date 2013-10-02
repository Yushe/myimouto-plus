<div class="container" style="width:750px;">
<h1>Create files</h1>
<?= $this->formTag(null, ['class' => 'form-horizontal well'], function() { ?>
  <div class="form-group">
    <label class="col-md-2 control-label">File name</label>
    <div class="col-md-10">
        <input type="text" name="file_name" id="file_name" class="form-control" placeholder="E.g. 'User', 'Admin/Post'" />
    </div>
  </div>
  <div class="form-group">
    <div class="col-lg-8">
      <div class="col-lg-4">
        <input type="hidden" name="type[controller]" value="0" />
        <input type="checkbox" name="type[controller]" value="1" id="type_controller" checked />
        <label for="type_controller" class="form-inline" />Controller</label>
      </div>
      
      <div class="col-lg-4">
        <input type="hidden" name="type[model]" value="0" />
        <input type="checkbox" name="type[model]" value="1" id="type_model" checked />
        <label for="type_model" />Model</label>
      </div>
      
      <div class="col-lg-4">
        <input type="hidden" name="type[helper]" value="0" id="type_model" />
        <input type="checkbox" name="type[helper]" value="1" id="type_helper" />
        <label for="type_helper" />Helper</label>
      </div>
    </div>
  </div>
  <div class="form-group">
    <div class="col-lg-8">
      <?= $this->contentTag('button', 'Create', ['class' => 'btn btn-primary']) ?>
    </div>
  </div>
<?php }) ?>
</div>
<script>document.getElementById('file_name').focus();</script>
