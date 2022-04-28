@extends('user.booking.layouts.index')

@section('content')
@if($isRequestReservation == 1)
<p class="reservation_completed_title">予約の申し込みが完了しました！</p>
<div class="common_attention_area">
    <p>ご予約はまだ確定していません</p>
</div>
<p class="reservation_completed_text">ご予約内容を確認いたしますので、しばらくお待ち下さい。 ご予約内容は下記のリンク、もしくはメールをご確認ください。</p>
@else
<p class="reservation_completed_title">予約が完了しました！</p>
<p class="reservation_completed_text">ご入力のメールアドレスに確認メールをお送りしました。予約情報はそちらのメールをご確認ください。</p>
@endif
<a class="common_footer_btn" href="{{ $userShowUrl }}">
    <div class="reservation_complete">
        予約情報確認
    </div>
</a>
@endsection
