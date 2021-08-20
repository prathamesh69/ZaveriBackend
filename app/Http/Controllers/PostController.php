<?php

namespace App\Http\Controllers;

use App\Helpers\Auth;
use App\Post;
use App\Helpers\ImageHelper;
use App\Helpers\Utils;
use App\Helpers\VideoHelper;
use App\Http\Controllers\Controller;
use App\Jobs\NPNotificationJob;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * List posts.
     * GET /api/posts
     */
    public function index(Request $req)
    {
        $posts = Post::latest();

        if ($query = $req->query('query', false)) {
            $posts->where('text', 'like', "%$query%");
        }

        if (Auth::isWholesaler()) {
            $posts->where('wholesaler_firm_id', Auth::user()->wholesaler_firm_id);
        } else if (Auth::isRetailer()) {
            // TODO: enable this for non-premium wholesalers
            // $posts->whereRaw('wholesaler_firm_id IN (SELECT followed_id FROM follows WHERE follower_id = ?)', Auth::id());
            if ($wholesaler_firm_id = $req->query('wholesaler_firm_id', false)) {
                $posts->where('wholesaler_firm_id', $wholesaler_firm_id);
            }
        } else if ($wholesaler_firm_id = $req->query('wholesaler_firm_id', false)) {
            $posts->where('wholesaler_firm_id', $wholesaler_firm_id);
        }

        if ($req->query('nopaginate', false) == '1') {
            $posts = $posts->get();
        } else {
            $posts = $posts->paginate($req->query('per_page', 20));
        }

        if ($posts->isEmpty()) return Utils::error('No posts found!', 404);
        return $posts;
    }

    /**
     * Create post.
     * POST /api/posts
     */
    public function create(Request $req)
    {
        if (Auth::isWholesaler()) $req->merge(['wholesaler_firm_id' => Auth::user()->wholesaler_firm_id]);

        $this->validate($req, [
            'wholesaler_firm_id' => 'nullable|exists:wholesaler_firms,id',
            'text' => 'required_without_all:image,video|string',
            'image' => 'required_without_all:text,video|image',
            'video' => 'required_without_all:text,image|mimetypes:video/*',
        ]);

        // create post
        $post = new Post;
        $post->fill($req->all());
        if ($req->hasFile('image')) {
            $post->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_POST);
        }
        if ($req->hasFile('video')) {
            $post->video = VideoHelper::save($req->file('video'), VideoHelper::$TYPE_POST);
        }
        $post->save();

        dispatch(new NPNotificationJob($post));

        return $post;
    }

    /**
     * Show post.
     * GET /api/posts/{id}
     */
    public function show(Request $req, $id)
    {
        $post = Post::find($id);
        if (empty($post)) Utils::error('Post not found!', 404);
        return $post;
    }

    /**
     * Update post.
     * POST /api/posts/{id}
     */
    public function update(Request $req, $id)
    {
        $post = Post::find($id);
        if (empty($post)) Utils::error('Post not found!', 404);

        $this->validate($req, [
            'text' => 'nullable|string',
            'image' => 'nullable|image',
            'video' => 'nullable|mimetypes:video/*',
        ]);

        // update post
        if ($req->has('text')) $post->text = $req->input('text');
        if ($req->hasFile('image')) {
            if ($post->image != null) ImageHelper::delete(ImageHelper::$TYPE_POST, $post->image);
            $post->image = ImageHelper::saveImage($req->file('image'), ImageHelper::$TYPE_POST);
        }
        if ($req->hasFile('video')) {
            if ($post->video != null) VideoHelper::delete(VideoHelper::$TYPE_POST, $post->video);
            $post->video = VideoHelper::save($req->file('video'), VideoHelper::$TYPE_POST);
        }
        $post->save();

        return $post;
    }

    /**
     * Delete post by id.
     *
     * DELETE /api/posts/{id}
     */
    public function delete(Request $req, $id)
    {
        $post = Post::find($id);
        if (empty($post)) return Utils::error('Post not found!', 404);
        $post->delete();
        if ($post->image != null) ImageHelper::delete(ImageHelper::$TYPE_POST, $post->image);
        if ($post->video != null) VideoHelper::delete(VideoHelper::$TYPE_POST, $post->video);
        return $post;
    }
}
