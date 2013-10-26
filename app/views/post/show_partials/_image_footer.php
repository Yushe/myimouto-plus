<div>
  <h4>
    <?= $this->linkToFunction($this->t('.edit'), "", ['class' => 'show_edit_form']) ?> |
    <?= $this->linkToFunction($this->t('.respond'), "", ['class' => 'show_reply_form']) ?>
  </h4>
</div>
<script>jQuery('.show_edit_form').click(function(){$('comments').hide(); $('edit').show(); $('post_tags').focus(); Cookie.put('show_defaults_to_edit', 1);});jQuery('.show_reply_form').click(function(){$('edit').hide(); $('comments').show(); $('reply-text-<?= $this->post_id ?>').focus(); Cookie.put('show_defaults_to_edit', 0);})</script>
