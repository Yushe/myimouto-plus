<?= $this->textArea("wiki_page", "body", ['size' => "60x30"]) ?><br>
<?= $this->submitTag("Save", ['name' => "save"]) ?><?= $this->buttonToFunction("Cancel", "history.back()") ?><?= $this->buttonToFunction("Preview", "$('wiki-view').innerHTML = '<em>Loading preview...</em>'; new Ajax.Updater('wiki-view', '/wiki/preview', {parameters: 'body=' + encodeURIComponent($('wiki_page_body').value)})") ?>
