<?php

namespace App\Http\Controllers;


use App\Models\Article;
use App\Models\Authentication;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Exchange;
use App\Models\FriendshipLink;
use App\Models\Goods;
use App\Models\Notice;
use App\Models\Question;
use App\Models\Recommendation;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Taggable;
use App\Models\User;
use App\Models\UserData;
use App\Models\UserTag;
use Carbon\Carbon;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
   
   public function index($categorySlug='all',$filter='newest')
    {

        $question = new Question();

        if(!method_exists($question,$filter)){
            abort(404);
        }

        $currentCategoryId = 0;
        if( $categorySlug != 'all' ){
            $category = Category::where("slug","=",$categorySlug)->first();
            if(!$category){
                abort(404);
            }
            $currentCategoryId = $category->id;
        }

        $questions =  call_user_func([$question,$filter] , $currentCategoryId );

        /*热门话题*/
        $hotTags =  Taggable::globalHotTags('questions');

        $categories = load_categories('questions');
        $hotUsers = Cache::remember('ask_hot_users',Setting()->get('website_cache_time',1),function() {
            return  UserData::activities(8);
        });
        return view('theme::home.ask')->with(compact('questions','hotUsers','hotTags','filter','categories','currentCategoryId','categorySlug'));
    }

    /*问答模块*/
    public function ask($categorySlug='all',$filter='newest')
    {

        $question = new Question();

        if(!method_exists($question,$filter)){
            abort(404);
        }

        $currentCategoryId = 0;
        if( $categorySlug != 'all' ){
            $category = Category::where("slug","=",$categorySlug)->first();
            if(!$category){
                abort(404);
            }
            $currentCategoryId = $category->id;
        }

        $questions =  call_user_func([$question,$filter] , $currentCategoryId );

        /*热门话题*/
        $hotTags =  Taggable::globalHotTags('questions');

        $categories = load_categories('questions');
        $hotUsers = Cache::remember('ask_hot_users',Setting()->get('website_cache_time',1),function() {
            return  UserData::activities(8);
        });
        return view('theme::home.ask')->with(compact('questions','hotUsers','hotTags','filter','categories','currentCategoryId','categorySlug'));
    }


    public function blog($categorySlug='all', $filter='newest')
    {
        $article = new Article();
        if(!method_exists($article,$filter)){
            abort(404);
        }

        $currentCategoryId = 0;
        if( $categorySlug != 'all' ){
            $category = Category::where("slug","=",$categorySlug)->first();
            if(!$category){
                abort(404);
            }
            $currentCategoryId = $category->id;
        }

        $articles = call_user_func([$article,$filter],$currentCategoryId);

        /*热门文章*/
        $hotArticles = Cache::remember('hot_articles',Setting()->get('website_cache_time',1),function() {
            return  Article::recommended(0,8);
        });

        $hotUsers = UserData::activeInArticles();
        /*热门话题*/
        $hotTags =  Taggable::globalHotTags('articles');
        $categories = load_categories('articles');

        return view('theme::home.blog')->with(compact('articles','hotUsers','hotTags','filter','categories','currentCategoryId','categorySlug','hotArticles'));
    }

    public function topic( $categorySlug='all')
    {

        $currentCategoryId = 0;
        if( $categorySlug != 'all' ){
            $category = Category::where("slug","=",$categorySlug)->first();
            if(!$category){
                abort(404);
            }
            $currentCategoryId = $category->id;
        }

        $categories = load_categories('tags');

        $topics = Tag::where("category_id","=",$currentCategoryId)->orderBy('followers','DESC')->paginate(20);
        return view('theme::home.topic')->with(compact('topics','categories','currentCategoryId','categorySlug'));
    }


    public function user()
    {
        $users = UserData::orderBy('credits','desc')->orderBy('coins','desc')->orderBy('answers','desc')->paginate(20);
        return view('theme::home.user')->with('users',$users);

    }

    public function experts(Request $request,$categorySlug='all',$provinceId='all'){
        $categories = load_categories('experts');
        $hotProvinces = Cache::remember('hot_expert_cities',Setting()->get('website_cache_time',1),function() {
            return  Authentication::select('province', DB::raw('COUNT(user_id) as total'))->groupBy('province')->orderBy('total','desc')->get();
        });
        $query = Authentication::leftJoin('user_data', 'user_data.user_id', '=', 'authentications.user_id')->where('user_data.authentication_status','=',1);
        $categoryId = 0;
        if( $categorySlug != 'all' ){
            $category = Category::where("slug","=",$categorySlug)->first();
            if($category){
                $categoryId = $category->id;
                $query->where("authentications.category_id","=",$categoryId);
            }
        }

        if($provinceId != 'all'){
            $query->where("authentications.province","=",$provinceId);
        }

        $word = $request->input('word','');
        if($word){
            $query->where("authentications.real_name",'like',"$word%");
        }
        $experts = $query->orderBy('user_data.answers','DESC')
            ->orderBy('user_data.articles','DESC')
            ->orderBy('authentications.updated_at','DESC')
            ->select('authentications.user_id','authentications.real_name','authentications.description','authentications.title','user_data.coins','user_data.credits','user_data.followers','user_data.supports','user_data.answers','user_data.articles','user_data.authentication_status')
            ->paginate(16);
        return view('theme::home.expert')->with(compact('experts','categories','hotProvinces','categorySlug','categoryId','provinceId','word'));
    }


    public function shop()
    {
        $goods = Goods::where('status','>',0)->where('remnants','>',0)->orderBy('coins','asc')->paginate(16);
        $exchanges = Exchange::newest();
        return view('theme::home.shop')->with(compact('goods','exchanges'));
    }

}
