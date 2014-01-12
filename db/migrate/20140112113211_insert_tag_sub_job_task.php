<?php
class InsertTagSubJobTask extends Rails\ActiveRecord\Migration\Base
{
    public function up()
    {
        JobTask::create([
            'task_type'    => "calculate_tag_subscriptions",
            'data_as_json' => '{}',
            'status'       => "pending",
            'repeat_count' => -1
        ]);
    }
}
