$('<?= $this->escapeJavascript("tag-subscription-row-" . $this->tag_subscription->id) ?>').remove();
notice('Tag subscription deleted');
