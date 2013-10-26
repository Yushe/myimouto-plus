Hello, <?= $this->user->pretty_name() ?>. Your password has been reset to:

   <?= $this->password ?>

You can login to <?= $this->urlFor(["user#login", 'only_path' => false, 'host' => CONFIG()->server_host]) ?> and change your password to something else.
