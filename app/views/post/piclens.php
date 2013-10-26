
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title><?= $this->h CONFIG()->app_name ?>/<?= $this->h $this->params()->tags ?></title>
  <link><?= scheme ?><?= $this->h CONFIG()->server_host ?>/</link>
  <description><?= $this->h CONFIG()->app_name ?>PicLens RSS Feed</description>
  <?php unless $this->posts.current_page == 1 ?>
    <?= tag("atom:link", {'rel' => 'previous', 'href' => 'url_for'('only_path' => 'false', 'post#piclens', :page => $this->posts.previous_page, :tags => $this->params()->tags)}, false) ?>
  <?php end ?>
  <?php unless $this->posts.next_page.nil? ?>
    <?= tag("atom:link", {'rel' => 'next', 'href' => 'url_for'('only_path' => 'false', 'post#piclens', :page => $this->posts.next_page, :tags => $this->params()->tags)}, false) ?>
  <?php end ?>

  <?php foreach ($this->posts as $post) : ?>
    <item>
      <title><?= $this->h post.cached_tags ?></title>
      <link><?= scheme ?><?= $this->h CONFIG()->server_host ?>/post/show/<?= post.id ?></link>
      <guid><?= scheme ?><?= $this->h CONFIG()->server_host ?>/post/show/<?= post.id ?></guid>
      <media:thumbnail url="<?= post.preview_url ?>"/>
    <?php if (CONFIG()->image_samples) : ?>
      <media:content url="<?= post.sample_url ?>" type=""/>
    <?php else ?>
        <media:content url="<?= post.file_url ?>" type=""/>
    <?php endif ?>
    </item>
  <?php endforeach ?>
</channel>
</rss>
