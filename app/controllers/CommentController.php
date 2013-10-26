<?php
class CommentController extends ApplicationController
{
    protected function init()
    {
        $this->helper('Avatar', 'Post');
    }
    
    protected function filters()
    {
        return array(
            'before' => [
                'member_only' => ['only' => array('create', 'destroy', 'update')],
                'janitor_only' => ['only' => array('moderate')]
            ]
        );
    }

    public function edit()
    {
        $this->comment = Comment::find($this->params()->id);
    }

    public function update()
    {
        $comment = Comment::find($this->params()->id);
        if (current_user()->has_permission($comment)) {
            $comment->updateAttributes($this->params()->comment);
            $this->respond_to_success("Comment updated", '#index');
        } else {
            $this->access_denied();
        }
    }

    public function destroy()
    {
        $comment = Comment::find($this->params()->id);
        if (current_user()->has_permission($comment)) {
            $comment->destroy();
            $this->respond_to_success("Comment deleted", array('post#show', 'id' => $comment->post_id));
        } else {
            $this->access_denied();
        }
    }

    public function create()
    {
         if (current_user()->is_member_or_lower() && $this->params()->commit == "Post" && Comment::where("user_id = ? AND created_at > ?", current_user()->id, strtotime('-1 hour'))->count() >= CONFIG()->member_comment_limit) {
            # TODO: move this to the model
            $this->respond_to_error("Hourly limit exceeded", '#index', array('status' => 421));
            return;
        }

        $user_id = current_user()->id;
Rails::log($this->params()->comment);
        $comment = new Comment(array_merge($this->params()->comment, array('ip_addr' => $this->request()->remoteIp(), 'user_id' => $user_id)));
        if ($this->params()->commit == "Post without bumping") {
            $comment->do_not_bump_post = true;
        }
        
        if ($comment->save()) {
            $this->respond_to_success("Comment created", '#index');
        } else {
            $this->respond_to_error($comment, '#index');
        }
    }

    public function show()
    {
        $this->set_title('Comment');
        $this->comment = Comment::find($this->params()->id);
        $this->respond_to_list("comment");
    }

    public function index()
    {
        $this->set_title('Comments');
        
        if ($this->request()->format() == "json" || $this->request()->format() == "xml") {
            $this->comments = Comment::generate_sql($this->params()->all())->order("id DESC")->paginate($this->page_number(), 25);
            $this->respond_to_list("comments");
        } else {
            $this->posts = Post::where("last_commented_at IS NOT NULL")->order("last_commented_at DESC")->paginate($this->page_number(), 10);

            $comments = new Rails\ActiveRecord\Collection();
            $this->posts->each(function($post)use($comments){$comments->merge($post->recent_comments());});

            $newest_comment = $comments->max(function($a, $b){return $a->created_at > $b->created_at ? $a : $b;});
            if (!current_user()->is_anonymous() && $newest_comment && current_user()->last_comment_read_at < $newest_comment->created_at) {
                current_user()->updateAttribute('last_comment_read_at', $newest_comment->created_at);
            }

            $this->posts->deleteIf(function($x){return !$x->can_be_seen_by(current_user(), array('show_deleted' => true));});
        }
    }

    public function search()
    {
        $query = Comment::order('id desc');
        // $conds = $cond_params = $search_terms = array();
        if ($this->params()->query) {
            $keywords = array();
            foreach (explode(' ', $this->params()->query) as $s) {
                if (!$s) continue;
                
                if (strpos($s, 'user:') === 0 && strlen($s) > 5) {
                    list($search_type, $param) = explode(':', $s);
                    if ($user = User::where(['name' => $param])->first()) {
                        $query->where('user_id = ?', $user->id);
                    } else {
                        $query->where('false');
                    }
                    continue;
                }

                $search_terms[] = $s;
            }
            $query->where('body LIKE ?', '%' . implode('%', $search_terms) . '%');
            // $options['conditions'] = array_merge(array(implode(' AND ', $conds)), $cond_params);
        } else
            $query->where('false');

        $this->comments = $query->paginate($this->page_number(), 30);

        $this->respond_to_list("comments");
    }

    public function moderate()
    {
        $this->set_title('Moderate Comments');
        if ($this->request()->isPost()) {
            $ids = array_keys($this->params()->c);
            $coms = Comment::where("id IN (?)", $ids)->take();

            if ($this->params()->commit == "Delete") {
                $coms->each('destroy');
            } elseif ($this->params()->commit == "Approve") {
                $coms->each('updateAttribute', array('is_spam', false));
            }

            $this->redirectTo('#moderate');
        } else {
            $this->comments = Comment::where("is_spam = TRUE")->order("id DESC")->take();
        }
    }

    public function markAsSpam()
    {
        $this->comment = Comment::find($this->params()->id);
        $this->comment->updateAttributes(array('is_spam' => true));
        $this->respond_to_success("Comment marked as spam", '#index');
    }
}
