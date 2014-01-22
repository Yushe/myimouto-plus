<p>Hello, <?= $this->h($this->user->pretty_name()) ?>. Your password has been reset to <code><?= $this->password ?></code>.</p>

<p>You can login to <?= $this->linkTo(CONFIG()->app_name, ["user#login", 'only_path' => false, 'host' => CONFIG()->server_host]) ?> and change your password to something else.</p>
