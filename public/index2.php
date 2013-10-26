<?php
// echo preg_replace('/^(.*?\d+ .{50}\S+)(.*)?/', '\1', 'abc 234 tag_1 tag_2 tag_3');
// require __DIR__ . '/../config/boot.php';
// $q = Post::where('id < 1000')->limit(10)->take();
// $m = Post::where(['width' => 1000])->where("user_id = ?", 4)->take();

// vpe(Post::find(1));
// vpe($m);
// vpe($q);define('RAILS_ENV', 'development');
require __DIR__ . '/../config/boot.php';

set_time_limit(0);
JobTask::execute_once();
exit;
?>
<!doctype html>
<html><head>
<script src="/assets/application.js"></script>
</head>
<body>
<a href="/dmail/mark_all_read" data-method="post">AAA</a>

<script>
// Comment = Backbone.Model.extend({
    // defaults: {
        // body: "",
        // creator_id: null,
        // creator: null,
        // post_id: null
    // },
    
    // sync: function(method, model, options) {
        // if (method == "update")
            // options.url = "/comment/update/" + this.get("id") + ".json";
        // return Backbone.sync(method, model, options);
    // },
    
    // toJSON: function() {
        // return {comment:this.attributes}
    // },
    
    // urlRoot: function() {
        // if (this.isNew()){
            // return "/comment";
        // } else {
            // return "/comment/show.json/";
        // }
    // }
// })

// Comments = Backbone.Collection.extend({
    // model: Comment,
    // url: "/comment.json"
// })

// var c, cs;
// cs = new Comments();
// cs.fetch();

// setTimeout(function(){
    // c = cs.models[0];
    // c.set("body", "hey~~");
// }, 300)
</script>
</body>
</html>