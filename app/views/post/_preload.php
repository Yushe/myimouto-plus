<?php foreach ($posts as $post) : ?>
<link rel="prefetch" href="<?= $post->preview_url() ?>">
<?php endforeach ?>
