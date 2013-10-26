<?php
class UserRecordController extends ApplicationController
{
    public function index()
    {
        if ($this->params()->user_id) {
            // $this->params(user_id] = params[:user_id].to_i
            # Use .where to ignore error when invalid user_id entered.
            # .first because .where returns array.
            $this->user = User::where('id = ?', $this->params()->user_id)->first();
            $this->user_records = UserRecord::where("user_id = ?", $this->params()->user_id)->order("created_at desc")->paginate($this->page_number(), 20);
        } else {
            $this->user = false;
            $this->user_records = UserRecord::order("created_at desc")->paginate($this->page_number(), 20);
        }
    }

    public function create()
    {
        $this->user = User::find($this->params()->user_id);

        if ($this->request()->isPost()) {
            if ($this->user->id == $this->current_user->id)
                $this->notice("You cannot create a record for yourself");
            else {
                $this->user_record = UserRecord::create(array_merge($this->params()->user_record, ['user_id' => $this->params()->user_id, 'reported_by' => $this->current_user->id]));
                $this->notice("Record updated");
            }
            $this->redirectTo(["#index", 'user_id' => $this->user->id]);
        }
    }

    public function destroy()
    {
        $user_record = UserRecord::find($this->params()->id);
        
        if ($this->current_user->is_mod_or_higher() || ($this->current_user->id == $user_record->reported_by)) {
            $user_record->destroy();
            
            $this->respond_to_success("Record updated", ["#index", 'user_id' => $this->params()->id]);
        } else
            $this->access_denied();
    }
    
    protected function filters()
    {
        return [
            'before' => [
                'privileged_only' => ['only' => ['create', 'destroy']]
            ]
        ];
    }
}