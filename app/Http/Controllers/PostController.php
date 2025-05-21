<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{

    public function index()
    {
        $posts = Post::with(['category', 'user'])->get();
        return response()->json($posts);
    }


    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

            if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Cria o post associando ao usuário autenticado
        $post = new Post($request->all());
        Auth::user()->posts()->save($post);

        return response()->json([
            'message' => 'Post created successfully',
            'post' => $post->load('user', 'category'),
        ], 201);
    }


    public function show(string $id)
    {
        $post = Post::with(['category', 'user'])->findOrFail($id);
        return response()->json($post);
    }


    public function update(Request $request, string $id)
    {
         $post = Post::findOrFail($id);

        // Verifica se o usuário é dono do post (opcional)
        if ($post->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You can only update your own posts'
            ], Response::HTTP_FORBIDDEN); // 403
        }

        $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
        ]);

        $post->update($request->all());

        return response()->json([
            'message' => 'Post updated successfully',
            'post' => $post->fresh(['category', 'user']),
        ]);
    }

    public function destroy(string $id)
    {
        $post = Post::findOrFail($id);

        if ($post->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You can only delete your own posts'
            ], Response::HTTP_FORBIDDEN); // 403
        }

        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully'
        ], Response::HTTP_NO_CONTENT); // 204
    }
}
