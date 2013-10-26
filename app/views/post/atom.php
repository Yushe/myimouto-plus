<?xml version="1.0" encoding="UTF-8"?>

<feed xmlns="http://www.w3.org/2005/Atom">
  <title><?= $this->h(CONFIG()->app_name) ?></title>
  <link href="<?= scheme ?><?= $this->h CONFIG()->server_host ?>/post/atom" rel="self"/>
  <link href="<?= scheme ?><?= $this->h CONFIG()->server_host ?>/post/index" rel="alternate"/>
  <id><?= scheme ?><?= $this->h CONFIG()->server_host ?>/post/atom?tags=<?= $this->h $this->params()->tags ?></id>
  <?php if ($this->posts.any?) : ?>
    <updated><?= $this->posts[0].created_at.gmtime.xmlschema ?></updated>
  <?php endif ?>
  <author><name><?= $this->h CONFIG()->app_name ?></name></author>
  <?php foreach ($this->posts as $post) : ?>
    <entry>
      <title><?= $this->h post.cached_tags ?></title>
      <link href="<?= scheme ?><?= $this->h CONFIG()->server_host ?>/post/show/<?= post.id ?>" rel="alternate"/>
      <?php if (post.source =~ /^http/) : ?>
        <link href="<?= $this->h post.source ?>" rel="related"/>
      <?php endif ?>
      <id><?= scheme ?><?= $this->h CONFIG()->server_host ?>/post/show/<?= post.id ?></id>
      <updated><?= post.created_at.gmtime.xmlschema ?></updated>
      <summary><?= $this->h post.cached_tags ?></summary>
      <content type="xhtml">
        <div xmlns="http://www.w3.org/1999/xhtml">
          <a href="<?= scheme ?><?= $this->h CONFIG()->server_host ?>/post/show/<?= post.id ?>">
            <img src="<?= post.preview_url ?>"/>
          </a>
        </div>
      </content>
      <author>
        <name><?= $this->h post.author ?> (<?= post.width ?>x<?= post.height ?>)</name>
      </author>
    </entry>
  <?php endforeach ?>
</feed>
