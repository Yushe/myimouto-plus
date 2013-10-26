<?= $this->partial("sidebar") ?>

<div class="content">
  <table width="100%">
    <thead>
      <tr>
        <th width="60%">Page</th>
        <th width="40%">Last edited</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($this->wiki_pages as $wiki_page) : ?>
        <tr class="<?= $this->cycle('even', 'odd') ?>">
          <td><?= $this->linkTo($this->h($wiki_page->pretty_title()), ["wiki#show", 'title' => $wiki_page->title]) ?></td>
          <td><?= date("m/d H:m", strtotime($wiki_page->updated_at)) ?> by <?= $this->h($wiki_page->author()) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>

  <div id="paginator">
    <?= $this->willPaginate($this->wiki_pages) ?>
  </div>
</div>


<?= $this->partial("footer") ?>
