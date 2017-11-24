@extends('theme::layout.public')

@section('seo_title'){{ parse_seo_template('seo_index_title','default') }}@endsection
@section('jumbotron')
    @if(Auth()->guest())
    <div class="jumbotron text-center">
        <h4>{{ Setting()->get('website_welcome','现在加入Tipask问答网，一起记录站长的世界') }} <a class="btn btn-primary ml-10" href="{{ route('auth.user.register') }}" role="button">立即注册</a> <a class="btn btn-default ml-5" href="{{ route('auth.user.login') }}" role="button">用户登录</a></h4>
    </div>
    @endif
@endsection

@section('content')
    <div class="row">
        <div class="col-xs-12 col-md-9 main">
            <h2 class="h4  mt-30">
                最新动态
            </h2>
            <div class="widget-streams">
                @foreach($doings as $doing)
                <section class="hover-show streams-item">
                    <div class="stream-wrap media">
                        <div class="pull-left">
                            <a href="{{ route('auth.space.index',['user_id'=>$doing->user_id]) }}" target="_blank">
                                <img class="media-object avatar-40" src="{{ get_user_avatar($doing->user_id) }}" alt="{{ $doing->user->name }}">
                            </a>
                        </div>
                        <div class="media-body">
                            <p class="text-muted">
                                <a target="_blank" href="{{ route('auth.space.index',['user_id'=>$doing->user_id]) }}"> {{ $doing->user->name }}</a> {{ $doing->action_text }} ·
                                <time class="timeago">{{ timestamp_format($doing->created_at) }} </time>
                            </p>
                            <h2 class="h4 title"><a href="{{ route('ask.question.detail',['question_id'=>$doing->source_id]) }}" target="_blank">{{ $doing->subject }}</a></h2>
                            @if(in_array($doing->action,['answer','follow_question','append_reward']))
                                <div class="full-text fmt">
                                    {{ str_limit(strip_tags($doing->content),300) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
                @endforeach
            </div>

            <div class="text-center">
                {!! str_replace('/?', '?', $doings->render()) !!}

            </div>
        </div>
        @include('theme::layout.right_menu')
    </div>
@endsection