@extends('user.booking.layouts.index')

@section('content')
<p class="reservation_completed_title">予約が完了しました！</p>
<p class="reservation_completed_text">ご入力のメールアドレスに確認メールをお送りしました。予約情報はそちらのメールをご確認ください。</p>                
<a class="common_footer_btn" href="{{ $userShowUrl }}">
    <div class=" reservation_complete">
        予約情報確認
    </div>
</a>
@endsection