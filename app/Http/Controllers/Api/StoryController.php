<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StoryController extends Controller
{
    /**
     * Display a listing of stories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get stories that haven't expired yet
        $stories = Story::with('user')
            ->where('expires_at', '>', now())
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stories
        ]);
    }

    /**
     * Store a newly created story in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:500',
            'media' => 'required_without:content|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:20480',
            'expires_in_hours' => 'nullable|integer|min:1|max:48', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Set expiration time (default 24 hours)
        $expiresInHours = $request->input('expires_in_hours', 24);
        $expiresAt = Carbon::now()->addHours($expiresInHours);

        $story = new Story();
        $story->user_id = auth()->id();
        $story->content = $request->input('content');
        $story->expires_at = $expiresAt;
        $story->is_active = true;

        // Handle media upload if present
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mediaType = explode('/', $file->getMimeType())[0]; // 'image' or 'video'
            $path = $file->store('stories', 'public');
            
            $story->media_url = Storage::url($path);
            $story->media_type = $mediaType;
        }

        $story->save();

        return response()->json([
            'success' => true,
            'message' => 'Story created successfully',
            'data' => $story
        ], 201);
    }

    /**
     * Display the specified story.
     *
     * @param  \App\Models\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function show(Story $story)
    {
        // Check if story has expired or is inactive
        if ($story->expires_at < now() || !$story->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Story not available'
            ], 404);
        }

        // Record view if authenticated user is not the owner
        if (auth()->id() !== $story->user_id) {
            StoryView::firstOrCreate([
                'story_id' => $story->id,
                'user_id' => auth()->id()
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $story->load('user', 'views.user')
        ]);
    }

    /**
     * Update the specified story.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Story $story)
    {
        // Check if user is the owner of the story
        if (auth()->id() !== $story->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Update story
        if ($request->has('content')) {
            $story->content = $request->input('content');
        }

        if ($request->has('is_active')) {
            $story->is_active = $request->input('is_active');
        }

        $story->save();

        return response()->json([
            'success' => true,
            'message' => 'Story updated successfully',
            'data' => $story
        ]);
    }

    /**
     * Remove the specified story from storage.
     *
     * @param  \App\Models\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function destroy(Story $story)
    {
        // Check if user is the owner of the story
        if (auth()->id() !== $story->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete media if exists
        if ($story->media_url) {
            $path = str_replace('/storage/', '', $story->media_url);
            Storage::disk('public')->delete($path);
        }

        $story->delete();

        return response()->json([
            'success' => true,
            'message' => 'Story deleted successfully'
        ]);
    }

    /**
     * Get stories by user.
     *
     * @param  int  $userId
     * @return \Illuminate\Http\Response
     */
    public function userStories($userId)
    {
        $stories = Story::with('user')
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stories
        ]);
    }

    /**
     * Get current user's stories.
     *
     * @return \Illuminate\Http\Response
     */
    public function myStories()
    {
        $stories = Story::with(['views.user'])
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $stories
        ]);
    }

    /**
     * Mark a story as viewed.
     *
     * @param  \App\Models\Story  $story
     * @return \Illuminate\Http\Response
     */
    public function view(Story $story)
    {
        // Check if story has expired or is inactive
        if ($story->expires_at < now() || !$story->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Story not available'
            ], 404);
        }

        // Don't record view if user is the owner
        if (auth()->id() === $story->user_id) {
            return response()->json([
                'success' => true,
                'message' => 'This is your own story'
            ]);
        }

        // Record view
        StoryView::firstOrCreate([
            'story_id' => $story->id,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Story viewed successfully'
        ]);
    }
}
