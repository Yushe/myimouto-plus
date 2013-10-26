<div class="memo">
    <h3><?= $this->t('user_logout_text') ?></h3>
    <p><?= $this->t('user_logout_text2') ?></p>
    <?= $this->linkTo($this->t('user_logout_link'), "/user/login") ?>
</div>

<?= $this->partial("footer") ?>
