<?php

namespace MyImouto\Http\Controllers;

use DB;
use Gate;
use Auth;
use Illuminate\Http\Request;
use MyImouto\Http\Requests;
use MyImouto\Http\Controllers\Controller;
use MyImouto\Post;
use MyImouto\User;
use MyImouto\Validators\PostValidator;

class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Gate::check('upload-limit')) {
            return response(null, 421)->json([
                'success' => false,
                'reason'  => 'Daily limit exceeded'
            ]);
        }
        
        DB::beginTransaction();
        
        $post = new Post();
        
        $post->setRelation('user', Auth::user());
        $post->user_id    = Auth::user()->id;
        $post->ip_addr    = $request->ip();
        $post->tag_string = $request->tag_string;
        
        $processor = new Post\Processor($post, $request->file('upload'));
        $processor->process();
        
        if ($processor->errors()->isEmpty()) {
            $post->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'post'    => $post->apiAttributes()
            ]);
        } else {
            DB::rollback();
            
            $processor->deleteFiles();
            
            return response()->json([
                'success' => false,
                'errors'  => $processor->errors()
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
