<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\User; // 追加

class FavoritesController extends Controller
{
    //userモデルのfavoritesメソッドを使う
    //引数microposts_idはbladeファイルで設定されて、web.phpからやってくる
    public function store($micropost_id) 
    {
         \Auth::user()->favorite($micropost_id);
         return back();
    }
    public function destroy($micropost_id) 
    {
         \Auth::user()->unfavorite($micropost_id);
         return back();
    }
    public function show($id) 
    {
        //$user = \Auth::user();
        //第一引数：bladeファイルで使えるようにする変数、第二引数：上の$user
        //return view('users.users')->with('users', $user);
        
        $user = User::findOrFail($id);
        $user->loadRelationshipCounts();
        //$userは上でとってきたユーザーの情報
        //favorites()はユーザーのfavorites一覧から情報取得
        $microposts = $user->favorites()->orderBy('created_at', 'desc')->paginate(10);
        // dd($microposts);
        return view('users.favorites', [
           'user' => $user,
           'microposts' => $microposts,
        ]);
    }
}