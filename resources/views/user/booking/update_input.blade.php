@extends('user.booking.layouts.index')
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

@section('content')
<div class="stay_time_fee_wrap">
    <div class="stay_time_container">
        <div class="common_stay_time">
            <div class="checkIn_img">
                <img src="{{ asset('static/common/images/enter 1.png') }}" alt="logo">
            </div>
            <div class="checkTime_container">
                <p class="checkIn_title">チェックイン</p>
                @if ($bookingData['stay_type'] == 1)
                <p class="checkIn_time">{{ $bookingData['checkin_start_end'] }}</p>
                @else
                <p class="checkIn_time">{{ $bookingData['base_info']['checkin_date_time'] }}</p>
                @endif
            </div>
        </div>
        <div class="common_stay_time">
            <div class="checkOut_img">
                <img src="{{ asset('static/common/images/logout 1.png') }}" alt="logo">
            </div>
            <div class="checkTime_container">
                <p class="checkOut_title">チェックアウト<p>
                @if ($bookingData['stay_type'] == 1)
                <p class="checkOut_time">{{ $bookingData['checkout_end'] }}</p>
                @else
                <p class="checkIn_time">{{ $bookingData['base_info']['checkout_date_time'] }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="stay_fee_container">
        @foreach ($roomFees as $fee)
        <div class="common_plan_fee">
            <p>{{ $fee['room_name'] }}</p>
            <p><span>¥</span> {{ number_format($fee['amount']) }}</p>
        </div>
        @endforeach
        {{-- <div class="common_plan_fee">
            <p>消費税</p>
            <p><span>¥</span> {{ $roomAmount['tax'] }}</p>
        </div> --}}
    </div>
    <div class="stay_totalFee_container">
        <p>合計（消費税込み）</p>
        <p>¥{{ number_format($roomAmount['sum']) }}</p>
    </div>
</div> <!-- /stay_time_fee_wrap -->
@if (!empty(session('error')))
<div class="common_attention_area">
    <p>{{ session('error') }}</p>
</div>
@endif
<div class="inputForm_wrap">
    @if ($bookingData['stay_type'] == 1)
    <form action="{{ route('user.booking_update_booking') }}" method="POST" id="confirm_form">
    @else
    <form action="{{ route('user.booking_update_dayuse_booking') }}" method="POST" id="confirm_form">
    @endif
        {{ csrf_field() }}
        <!-- 見出し -->
        <div class="common_main_title">
            <button type="button" class="main_title_back_button" style="display: block;" onclick="history.back();">戻る</button>
            <p class="main_title_title_text">予約情報を入力</p>
            <p class="main_title_title_text_sp">予約情報を入力</p>
        </div> <!-- common_main_title -->
        <!-- 入力フォーム -->
        <div class="common_inputForm">
            <div class="stayInfo_name">
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">氏名（姓）</p>
                    <input type="text" name="first_name" class="stayInfo_lastName_form" placeholder="例）山田" value="{{ old('first_name', $reservation->first_name) }}">
                </div>
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">氏名（名）</p>
                    <input type="text" name="last_name" class="stayInfo_firstName_form" placeholder="例）太郎" value="{{ old('last_name', $reservation->last_name) }}">
                </div>
            </div>
            <p class="common_form_error">{{ $errors->first('first_name') }}</p>
            <p class="common_form_error">{{ $errors->first('last_name') }}</p>
        </div>

        <div class="common_inputForm">
            <div class="stayInfo_name">
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">フリガナ（姓）</p>
                    <input type="text" name="first_name_kana" class="stayInfo_lastName_form" placeholder="例）ヤマダ" value="{{ old('first_name_kana', $reservation->first_name_kana) }}">
                </div>
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">フリガナ（名）</p>
                    <input type="text" name="last_name_kana" class="stayInfo_firstName_form" placeholder="例）タロウ" value="{{ old('last_name_kana', $reservation->last_name_kana) }}">
                </div>
            </div>
            <p class="common_form_error">{{ $errors->first('first_name') }}</p>
            <p class="common_form_error">{{ $errors->first('last_name') }}</p>
        </div>

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">メールアドレス</p>
                <input type="email" name="email" class="stayInfo_email_form" placeholder="例）sample@sample.com" value="{{ old('email', $reservation->email) }}">
            </div>
            <p class="common_form_error">{{ $errors->first('email') }}</p>
        </div>

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">メールアドレス確認</p>
                <input type="email" name="email_confirm" class="stayInfo_emailConfirm_form" placeholder="例）sample@sample.com" value="{{ old('email_confirm', $reservation->email) }}">
            </div>
            <p class="common_form_error">{{ $errors->first('email_confirm') }}</p>
        </div>
        
        <input type="hidden" name="address" value="{{ old('address', $reservation->address) }}">

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">電話番号</p>
                <input type="tel" name="tel" class="stayInfo_email_form" placeholder="例）09012345678" value="{{ old('tel', $reservation->tel) }}">
            </div>
            <p class="common_form_error">{{ $errors->first('tel') }}</p>
        </div>
        @if ($bookingData['stay_type'] == 1)
        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">チェックイン予定時間</p>
                <select class="stayInfo_checkIn" name="checkin_time">
                    <option value="">選択してください</option>
                    @foreach($checkinScTimes as $time)
                    <option value="{{ $time }}" @if(old('checkin_time', date('H:i', strtotime($reservation->checkin_time))) == $time ) selected @endif>{{ $time }}頃</option>
                    @endforeach
                </select>
            </div>
            <p class="common_form_error">{{ $errors->first('checkin_time') }}</p>
        </div>
        @else
            <select class="stayInfo_checkIn" name="checkin_time" style="display: none;" readonly>
                <option value="{{ date('H:i', strtotime($bookingData['base_info']['checkin_date_time'])) }}" selected></option>
            </select>
        @endif

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">特別リクエスト</p>
                <textarea class="stayInfo_requestForm" name="special_request" value="{{ old('special_request', $reservation->special_request) }}">{{ old('special_request', $reservation->special_request) }}</textarea>
            </div>
            <p class="common_form_error">{{ $errors->first('special_request') }}</p>
        </div>

        <div class="common_inputForm">
            <button type="button" class="common_footer_btn reservation_complete" id="confirmBtn">
                変更を確定
            </button>
        </div>
    </form>
</div> <!-- /inputForm_container -->
@endsection

@section('scripts')
<script src="{{ asset('static/user/js/booking/inputInfo.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
<script>
    $('#confirmBtn').on('click', function(){
        $('#confirmBtn').LoadingOverlay("show");
    })
</script>
@endsection