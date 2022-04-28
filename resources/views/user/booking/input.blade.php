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
            <p><span>¥</span> {{ number_format($roomAmount['tax']) }}</p>
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
<div>
    <p class="common_form_error">{{ $errors->first('card_number') }}</p>
    <p class="common_form_error">{{ $errors->first('expiration_month') }}</p>
    <p class="common_form_error">{{ $errors->first('expiration_year') }}</p>
    <p class="common_form_error">{{ $errors->first('cvc') }}</p>
</div>
<div class="inputForm_wrap">
    @if ($bookingData['stay_type'] == 1)
    <form action="{{ route('user.booking_confirm_booking') }}" method="POST" id="confirm_form">
    @else
    <form action="{{ route('user.booking_confirm_dayuse_booking') }}" method="POST" id="confirm_form">
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
                    <input type="text" name="first_name" class="stayInfo_lastName_form" placeholder="例）山田" 
                        value="{{ empty($lineGuestInfo) ? old('first_name') :  old('first_name', $lineGuestInfo['firstName'] ?? '')}}">
                </div>
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">氏名（名）</p>
                    <input type="text" name="last_name" class="stayInfo_firstName_form" placeholder="例）太郎" 
                        value="{{ empty($lineGuestInfo) ? old('last_name') : old('last_name', $lineGuestInfo['lastName'] ?? '' )}}">
                </div>
            </div>
            <p class="common_form_error">{{ $errors->first('first_name') }}</p>
            <p class="common_form_error">{{ $errors->first('last_name') }}</p>
        </div>

        <div class="common_inputForm">
            <div class="stayInfo_name">
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">フリガナ（姓）</p>
                    <input type="text" name="first_name_kana" class="stayInfo_lastName_form" placeholder="例）ヤマダ" 
                        value="{{ empty($lineGuestInfo) ? old('first_name_kana') :  old('first_name_kana', $lineGuestInfo['firstNameKana'] ?? '')}}">
                </div>
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">フリガナ（名）</p>
                    <input type="text" name="last_name_kana" class="stayInfo_firstName_form" placeholder="例）タロウ" 
                        value="{{ empty($lineGuestInfo) ? old('last_name_kana') :  old('last_name_kana', $lineGuestInfo['lastNameKana'] ?? '') }}">
                </div>
            </div>
            <p class="common_form_error">{{ $errors->first('first_name_kana') }}</p>
            <p class="common_form_error">{{ $errors->first('last_name_kana') }}</p>
        </div>

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">メールアドレス</p>
                <input type="email" name="email" class="stayInfo_email_form" placeholder="例）sample@sample.com" 
                    value="{{ empty($lineGuestInfo) ? old('email') :  old('email', $lineGuestInfo['email'] ?? '') }}">
            </div>
            <p class="common_form_error">{{ $errors->first('email') }}</p>
        </div>

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">メールアドレス確認</p>
                <input type="email" name="email_confirm" class="stayInfo_emailConfirm_form" placeholder="例）sample@sample.com" 
                    value="{{ empty($lineGuestInfo) ? old('email_confirm') :  old('email_confirm', $lineGuestInfo['email'] ?? '') }}">
            </div>
            <p class="common_form_error">{{ $errors->first('email_confirm') }}</p>
        </div>
        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">住所</p>
                <input type="text" name="address1" class="stayInfo_emailConfirm_form" placeholder="例）〇〇県〇〇市〇〇町" 
                    value="{{ empty($lineGuestInfo) ? old('address1') : old('address1', $lineGuestInfo['address'] ?? '') }}">
            </div>
            <p class="common_form_error">{{ $errors->first('address1') }}</p>
        </div>

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">番地</p>
                <input type="text" name="address2" class="stayInfo_emailConfirm_form" placeholder="例）1-2-3 アパート101" value="{{ old('address2') }}">
            </div>
            <p class="common_form_error">{{ $errors->first('address2') }}</p>
        </div>

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">電話番号</p>
                <input type="tel" name="tel" class="stayInfo_email_form" placeholder="例）09012345678" 
                    value="{{ empty($lineGuestInfo) ? old('tel') : old('tel', $lineGuestInfo['tel'] ?? '') }}">
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
                    <option value="{{ $time }}" @if(old('checkin_time') == $time ) selected @endif>{{ $time }}頃</option>
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
                <textarea class="stayInfo_requestForm" name="special_request" value="{{ old('special_request') }}">{{ old('special_request') }}</textarea>
            </div>
            <p class="common_form_error">{{ $errors->first('remarks') }}</p>
        </div>

        <!-- プラン選択 -->
        @if ($plan->prepay == 0 || $plan->prepay == 1)
        
        <div class="common_inputForm">
            @if (!empty($errors))
            <button type="button" class="common_footer_btn reservation_complete" id="confirmBtn">
            @else
            <button type="button" class="common_footer_btn reservation_complete is-disabled-btn" id="confirmBtn">
            @endif
                現地決済で予約　＞＞
            </button>
        </div>
        @endif
        @if ($plan->prepay == 0 || $plan->prepay == 2)
        <div class="common_inputForm">
            <button type="button" class="common_footer_btn reservation_complete is-disabled-btn" id="prepay_confirmBtn">
                事前決済で予約　＞＞
            </button>
        </div>
        @endif
        <div>
            <input type="hidden" id="card_number" name="card_number" value="">
            <input type="hidden" id="expiration_month" name="expiration_month" value="">
            <input type="hidden" id="expiration_year" name="expiration_year" value="">
            <input type="hidden" id="cvc" name="cvc" value="">
            <input type="hidden" id="payment_method" name="payment_method" value="0">
        </div>
    </form>
</div> <!-- /inputForm_container -->

<div class="common_popup_filter" style="display: none;"></div>
<div class="common_popup_block" id="prepayForm" style="display: none; padding: 20px; width: 85%;">
    <button type="button" class="common_popup_close" onClick="hidePopupBlock(this)">
        <img src="{{ asset('static/common/images/close_btn.png') }}" alt="close">
    </button>
    <p class="common_popup_title">決済情報を入力</p>
    <div class="common_popup_input_area">
        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">カード番号</p>
                <input type="tel" autocomplete="cc-number" placeholder="**** **** **** ****" size="17" id="credit_card_number" name="cardnumber"/>
            </div>
        </div>
        <div class="common_inputForm">
            <div class="stayInfo_name">
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">有効期限</p>
                    <input type="text" name="" class="stayInfo_lastName_form" placeholder="例）06" value="" id="credit_limit_month">
                </div>
                <div class="common_inputItem">
                    <p class="stayInfo_itemText"></p>
                    <input type="text" name="" class="stayInfo_firstName_form" placeholder="例）2024" value="" id="credit_limit_year">
                </div>
            </div>
        </div>
        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">セキュリティコード</p>
                <input type="tel" name="" class="stayInfo_email_form" placeholder="例）123" value="" id="credit_security_code">
            </div>
        </div>
    </div>
    <button type="button" class="common_footer_btn reservation_complete" id="confirmPrepay">
        決済
    </button>
</div>
@endsection

@section('scripts')
<script src="{{ asset('static/user/js/booking/inputInfo.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
<script>
    $('#confirmPrepay').on('click', function(){
        $('#confirmPrepay').LoadingOverlay("show");
    })

    $('.common_popup_close').on('click', function(){
        if (submitStatus == true) {
            $('#confirmPrepay').LoadingOverlay("hide");
            submitStatus = false
        }
    })

    $('#confirmBtn').on('click', function(){
        $('#confirmBtn').LoadingOverlay("show");
    })

    $('#confirmPrepay').on('click', () => {
        $('#card_number').val($('#credit_card_number').val())
        $('#expiration_month').val($('#credit_limit_month').val())
        $('#expiration_year').val($('#credit_limit_year') .val())
        $('#cvc').val($('#credit_security_code').val())
        $('#payment_method').val(1)
        $('#confirm_form').submit()
    })
</script>
@endsection