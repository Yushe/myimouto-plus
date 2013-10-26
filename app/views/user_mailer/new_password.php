<p>Hello, <?= $this->h($this->user->pretty_name()) ?>. Your password has been reset to <code><?= $this->password ?></code>.</p>

<p>You can login to <?= $this->linkTo(CONFIG()->app_name, ["user#login", 'only_path' => false, 'host' => CONFIG()->server_host]) ?> and change your password to something else.</p>
<div style="width:800px;height:1200;position:relative;">
  <div class="top-img"></div>
  <div class="links-container">
    <div class="mail-links">email: <a href="mailto:empresarios@todosasiachina.com">Empresarios@TodosAsiaChina.com</a></div>
    <div><a href="http://www.todosasiachina.com" class="mail-links red">www.TodosAsiaChina.com</a></div>
  </div>
  <div class="bot-img"></div>
</div>
<style type="text/css">
.mail-links, .mail-links a {
  font-size:22px;
  font-family: Arial, sans-serif;
  color:black;
  margin-bottom:10px;
  font-weight:bold;
}
.mail-links.red {
  color:red;
}
.links-container {
  width:440px;
  height:100px;
  text-align:center;
  margin: 0 auto;
}
.bot-img {
  width:100%;
  height:187px;
  background-image:url(http://www.delta-bridge.com/mail-imgs/bg-bot.jpg);
}
.top-img {
  width:100%;
  height:940px;
  background-image:url(http://www.delta-bridge.com/mail-imgs/bg-top.jpg);
}
</style>