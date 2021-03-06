<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    /**
     * このユーザが所有する投稿。（ Micropostモデルとの関係を定義）
     */
    public function microposts()
    {
        return $this->hasMany(Micropost::class);
    }
    /**
     * このユーザがフォロー中のユーザ。（ Userモデルとの関係を定義）
     */
     //$user->followings で $user が フォローしているUser達を取得できる
     //User が フォローしているUser達
    public function followings()
    {
        //第一引数：Userモデル、第二引数：中間テーブル名、第三・四：user_id のUserは follow_id のUserをフォローしている
        return $this->belongsToMany(User::class, 'user_follow', 'user_id', 'follow_id')->withTimestamps();
    }

    /**
     * このユーザをフォロー中のユーザ。（ Userモデルとの関係を定義）
     */
     //$user->followers で $user が フォローしているUser達を取得できる
     //User を フォローしているUser達
    public function followers()
    {
        //第三・四：follow_id のUserは user_id のUserからフォローされている
        return $this->belongsToMany(User::class, 'user_follow', 'follow_id', 'user_id')->withTimestamps();
    }
    /**
     * $userIdで指定されたユーザをフォローする。
     *
     * @param  int  $userId
     * @return bool
     */
     //この$userIdは、bladeから$idとしてUserFollowControllerに渡され、そこでfollowメソッドに$idが渡され、このfollowメソッド内で$useIdに名前が変わりそこに$idが入っている
    public function follow($userId)
    {
        // すでにフォローしているか
        $exist = $this->is_following($userId);
        // 対象が自分自身かどうか
        $its_me = $this->id == $userId;

        if ($exist || $its_me) {
            // フォロー済み、または、自分自身の場合は何もしない
            return false;
        } else {
            // 上記以外はフォローする
            $this->followings()->attach($userId);
            return true;
        }
    }

    /**
     * $userIdで指定されたユーザをアンフォローする。
     *
     * @param  int  $userId
     * @return bool
     */
    public function unfollow($userId)
    {
        // すでにフォローしているか
        $exist = $this->is_following($userId);
        // 対象が自分自身かどうか
        $its_me = $this->id == $userId;

        if ($exist && !$its_me) {
            // フォロー済み、かつ、自分自身でない場合はフォローを外す
            $this->followings()->detach($userId);
            return true;
        } else {
            // 上記以外の場合は何もしない
            return false;
        }
    }

    /**
     * 指定された $userIdのユーザをこのユーザがフォロー中であるか調べる。フォロー中ならtrueを返す。
     *
     * @param  int  $userId
     * @return bool
     */
    public function is_following($userId)
    {
        // フォロー中ユーザの中に $userIdのものが存在するか
        return $this->followings()->where('follow_id', $userId)->exists();
    }
    /**
     * このユーザとフォロー中ユーザの投稿に絞り込む。
     */
    public function feed_microposts()
    {
        // このユーザがフォロー中のユーザのidを取得して配列にする
        //pluck()　引数として与えられたテーブルのカラムの値だけを抜き出す命令
        //toArray()　通常の配列に変換
        $userIds = $this->followings()->pluck('users.id')->toArray();
        // このユーザのidもその配列に追加
        $userIds[] = $this->id;
        //microposts テーブルのデータのうち $userIds 配列のいずれかの値と合致するuser_idを持つものに絞り込んで値を返す
        return Micropost::whereIn('user_id', $userIds);
    }
    
    public function favorites()
    {
        return $this->belongsToMany(Micropost::class, 'favorites', 'user_id', 'micropost_id')->withTimestamps();
    }
    public function unfavorites()
    {
        return $this->belongsToMany(User::class, 'favorites', 'micropost_id', 'user_id')->withTimestamps();
    }
    
    public function favorite($micropostId)
    {
        // すでにFavしているか
        $exist = $this->is_favorite($micropostId);

        if ($exist) {
            // Fav済み、または、自分自身の場合は何もしない
            return ;
        } else {
            // 上記以外はフォローする
            $this->favorites()->attach($micropostId);
            return ;
        }
    }

    public function unfavorite($micropostId)
    {
        // すでにFavしているか
        $exist = $this->is_favorite($micropostId);

        if ($exist) {
            // フォロー済み、かつ、自分自身でない場合はフォローを外す
            $this->favorites()->detach($micropostId);
            return ;
        } else {
            // 上記以外の場合は何もしない
            return ;
        }
    }

    public function is_favorite($micropostId)
    {
        // Fav中micropostの中に $micropostIdのものが存在するか
        return $this->favorites()->where('micropost_id', $micropostId)->exists();
    }
    
    /**
     * このユーザに関係するモデルの件数をロードする。
     */
    public function loadRelationshipCounts()
    {
        $this->loadCount(['microposts', 'followings', 'followers', 'favorites']);
    }
}
