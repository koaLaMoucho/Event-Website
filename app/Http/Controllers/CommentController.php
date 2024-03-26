<?php

namespace App\Http\Controllers;

use App\Models\UserLikes;
use App\Models\Comment;
use Illuminate\Http\Request;
use App\Events\PostComment;


class CommentController extends Controller
{

    function comment(Request $request) {
        event(new PostComment($request->id));
    }

    public function submitComment(Request $request)
    {
        $request->validate([
            'newCommentText' => 'required',
        ]);
        $comment = new Comment();
        $comment->text = $request->input('newCommentText');
        $comment->event_id = $request->input('event_id'); 
        $comment->author_id = auth()->user()->user_id;
        $comment->save();
    
        $comment->profile_image = auth()->user()->profile_image;
        $comment->load('author');
    
        return response()->json(['message' => $comment]);
    }

    public function hideComment($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        if (!$comment) {
            return response()->json(['status' => 'error', 'message' => 'Comment not found']);
        }
        $comment->update(['private' => true]);

        return response()->json(['status' => 'success', 'message' => 'Comment hidden successfully']);
    }

    public function showComment($commentId)
    {
        $comment = Comment::findOrFail($commentId);

        if (!$comment) {
            return response()->json(['status' => 'error', 'message' => 'Comment not found']);
        }
        $comment->update(['private' => false]);

        return response()->json(['status' => 'success', 'message' => 'Comment shown successfully']);
    }



    public function editComment(Request $request)
    {
        $commentID = $request->input('comment_id');
        $newCommentText = $request->input('newCommentText');
    
        $comment = Comment::find($commentID);
        if ($comment) {
            $comment->text = $newCommentText;
            $comment->save();
        }
    
        
        return response()->json(['message' => $comment]);
    }

      public function likeComment(Request $request){
        
        $commentID = $request->input('comment_id');

        $comment = Comment::find($commentID);
      
        if ($comment){
            $userLikes = new UserLikes();
            $userLikes->user_id = auth()->user()->user_id;
            $userLikes->comment_id = $commentID;
            $userLikes->save();

            return response()->json(['message' => $comment]);
        }
        return response()->json(['message' => 'Comment not found'], 404);
    }


    public function unlikeComment(Request $request){
        
        $commentID = $request->input('comment_id');
         
      
         $comment = Comment::find($commentID);
         
        $userID = auth()->user()->user_id;

        $userLikes = UserLikes::where([
            'user_id' => $userID,
            'comment_id' => $commentID,
        ])->delete();

       
            return response()->json(['message' => $comment]);
    }

       
   


    public function deleteComment(Request $request)
{
    $commentID = $request->input('comment_id');

    $comment = Comment::find($commentID);

    

    if ($comment) {
        $comment->likes()->delete();
        
        $comment->reports()->each(function ($report) {
           
            $report->notifications()->delete();

        
            $report->delete();
        });

       
        $comment->notifications()->delete();

       
        $comment->delete();

        return response()->json(['message' => $comment]);
    }

    return response()->json(['message' => 'Comment not found'], 404);
}


    
    
}
