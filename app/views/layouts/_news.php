<?php if (!CONFIG()->enable_news_ticker) return; ?>
<div id="news-ticker" style="display: none">
  <ul>
    <li>We're running MyImouto <?= CONFIG()->version ?>. Please report any errors <?= $this->linkTo('here', 'http://code.google.com/p/my-imouto-booru/issues/list', ['target' => '_blank']) ?>.</li>
  </ul>

  <a href="#" id="close-news-ticker-link"><?= $this->t('.close') ?></a>
</div>
<script type="text/javascript">
if (Cookie.get('hide-news-ticker') != '1') {
  $('news-ticker').show();
  $('close-news-ticker-link').observe('click', function(e) {
    $('news-ticker').hide();
    Cookie.put('hide-news-ticker', '1', 7);
    return false;
  })
}
</script>
