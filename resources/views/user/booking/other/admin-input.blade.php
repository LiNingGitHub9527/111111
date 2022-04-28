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
                <p class="checkIn_time">{{ $checkinDate . ' ' . $startTime  }}</p>
            </div>
        </div>
        <div class="common_stay_time">
            <div class="checkOut_img">
                <img src="{{ asset('static/common/images/logout 1.png') }}" alt="logo">
            </div>
            <div class="checkTime_container">
                <p class="checkOut_title">チェックアウト<p>
                <p class="checkOut_time">{{ $checkinDate . ' ' . $endTime  }}</p>
            </div>
        </div>
    </div>
    <div class="stay_fee_container">
        @foreach ($roomTypes as $types)
        <div class="common_plan_fee">
            <p>{{ $types['room_name'] }}</p>
            <p><span>¥</span> {{ number_format($types['amount']) }}</p>
        </div>
        @endforeach
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
    <form action="{{ route('user.other.booking_confirm_booking') }}" method="POST" id="confirm_form">
        {{ csrf_field() }}
        <!-- 見出し -->
        <div class="common_main_title">
            <button type="button" class="main_title_back_button" style="display: block;"
                onclick="location.href='{{ url()->previous() }}'">戻る</button>
            <p class="main_title_title_text">予約情報を入力</p>
            <p class="main_title_title_text_sp">予約情報を入力</p>
        </div> <!-- common_main_title -->
        <!-- 入力フォーム -->
        @if ($businessType == 3 || $businessType == 4)
        <div class="common_inputForm">
            <div class="common_inputItem">
                <div class="text-xl font-bold align-middle pb-3">金額</div>
                <p>
                    <div class="flex">
                        <div class="w-5 text-xl font-bold align-middle">¥</div>
                        <div><input type="number" name="{{ 'price' }}" class="stayInfo_email_form"
                            data-required=true value="{{ $types['amount'] }}"></div>
                    </div>
                </p>
            </div>
        </div>
        @foreach ($baseCustomerItems as $item)
        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">
                    {{ $item['name'] }}
                    @if ($item['is_required'] == 1 || $item['data_type'] == 10)
                    <span class="required">※必須</span>
                    @endif
                </p>
                @if ($item['data_type'] == 1)
                <!-- 短文テキスト -->
                <input type="text" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    value="{{ old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) }}" data-required="{{ $item['is_required'] }}">
                @elseif ($item['data_type'] == 2)
                <!-- 長文テキスト -->
                <textarea name="{{ 'item_' . $item['id'] }}" data-required="{{ $item['is_required'] }}"
                    class="stayInfo_requestForm">{{ old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) }}</textarea>
                @elseif ($item['data_type'] == 3)
                <!-- 数値 -->
                <input type="number" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    value="{{ old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) }}" data-required="{{ $item['is_required'] }}">
                @elseif ($item['data_type'] == 4)
                <!-- 日付 -->
                <input type="date" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date"
                    value="{{ old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) }}" data-required="{{ $item['is_required'] }}">
                @elseif ($item['data_type'] == 5)
                <!-- 時間 -->
                <input type="time" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date"
                    value="{{ old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) }}" step="1" data-required="{{ $item['is_required'] }}">
                @elseif ($item['data_type'] == 6)
                <!-- 日付+時間 -->
                <input type="datetime-local" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date"
                    value="{{ old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) }}" step="1" data-required="{{ $item['is_required'] }}">
                @elseif ($item['data_type'] == 7)
                <!-- 性別 -->
                <select class="reservation_info_gender" name="{{ 'item_' . $item['id'] }}" data-required="{{ $item['is_required'] }}">
                    <option value disabled selected>選択してください</option>
                    <option value="1">男性</option>
                    <option value="2">女性</option>
                    <option value="3">その他</option>
                </select>
                @elseif ($item['data_type'] == 8)
                <!-- 氏名 -->
                <input type="text" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    placeholder="例）山田太郎" data-required="{{ $item['is_required'] == 1 }}"
                    value="{{ empty($lineGuestInfo) ? old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) :  old('item_' . $item['id'], $lineGuestInfo['firstName'] . $lineGuestInfo['lastName'] ?? '')}}">
                @elseif ($item['data_type'] == 9)
                <!-- 電話番号 -->
                <input type="tel" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    {{ (!empty($item['disabled'])&&$item['disabled']) ? 'readonly' : '' }}
                    placeholder="例）09012345678" data-required="{{ $item['is_required'] }}"
                    value="{{ empty($lineGuestInfo) ? old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) : old('item_' . $item['id'], $lineGuestInfo['tel'] ?? '') }}">
                @elseif ($item['data_type'] == 10)
                <!-- メールアドレス -->
                <input type="email" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    placeholder="例）sample@sample.com" data-required="{{ $item['is_required'] }}"
                    {{(!empty($item['disabled'])&&$item['disabled'])? 'readonly' : '' }}
                    value="{{ empty($lineGuestInfo) ? old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) :  old('item_' . $item['id'], $lineGuestInfo['email'] ?? '') }}">
                <p class="stayInfo_itemText">メールアドレス確認</p>
                <input type="email" name="{{ 'item_' . $item['id'] . '_confirm' }}" class="stayInfo_email_form"
                    {{ (!empty($item['disabled'])&&$item['disabled']) ? 'readonly' : '' }}
                    placeholder="例）sample@sample.com" data-required="{{ $item['is_required'] }}" autocomplete="off"
                    value="{{ empty($lineGuestInfo) ? old('item_' . $item['id'] . '_confirm', !empty($item['value']) ? $item['value']: null) :  old('item_' . $item['id'] . '_confirm', $lineGuestInfo['email'] ?? '') }}">
                <p class="common_form_error">{{ $errors->first('item_' . $item['id'] . '_confirm') }}</p>
                @elseif ($item['data_type'] == 11)
                <!-- 住所 -->
                <input type="text" name="{{ 'item_' . $item['id'] }}" class="stayInfo_emailConfirm_form"
                    placeholder="例）〇〇県〇〇市〇〇町" data-required="{{ $item['is_required'] }}"
                    value="{{ empty($lineGuestInfo) ? old('item_' . $item['id'], !empty($item['value']) ? $item['value']: null) : old('item_' . $item['id'], $lineGuestInfo['address'] ?? '') }}">
                @elseif ($item['data_type'] == 12)
                <!-- 部屋タイプ名 -->
                <input type="text" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    placeholder="例）スタンダード" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $roomTypes[0]['room_name'], '') }}">
                @elseif ($item['data_type'] == 13)
                <!-- チェックイン日 -->
                <input type="date" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date"
                    value="{{ old('item_' . $item['id'], $checkinDate ?? '') }}" data-required="{{ $item['is_required'] }}">
                @elseif ($item['data_type'] == 14)
                <!-- 予約開始時間 -->
                <input type="time" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date"
                    value="{{ old('item_' . $item['id'], $startTime ?? '') }}" step="60" data-required="{{ $item['is_required'] }}">
                @elseif ($item['data_type'] == 15)
                <!-- 予約終了時間 -->
                <input type="time" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date"
                    value="{{ old('item_' . $item['id'], $endTime ?? '') }}" step="60" data-required="{{ $item['is_required'] }}">
                @endif
            </div>
            <p class="common_form_error">{{ $errors->first('item_' . $item['id']) }}</p>
        </div>
        @endforeach
        @if ($isNotExistsEmail)
            <div class="common_inputForm">
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">
                        メールアドレス
                        <span class="required">※必須</span>
                    </p>
                    <input type="email" name="email" class="stayInfo_email_form"
                        placeholder="例）sample@sample.com" data-required="1"
                        value="{{ empty($lineGuestInfo) ? old('email') :  old('email', $lineGuestInfo['email'] ?? '') }}">
                </div>
                <p class="common_form_error">{{ $errors->first('email') }}</p>
            </div>
            <div class="common_inputForm">
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">
                        メールアドレス確認
                        <span class="required">※必須</span>
                    </p>
                    <input type="email" name="email_confirm" class="stayInfo_email_form"
                        placeholder="例）sample@sample.com" data-required="1" autocomplete="off"
                        value="{{ empty($lineGuestInfo) ? old('email_confirm') :  old('email_confirm', $lineGuestInfo['email'] ?? '') }}">
                </div>
                <p class="common_form_error">{{ $errors->first('email_confirm') }}</p>
            </div>
            @endif
        @elseif ($businessType == 2 || $businessType == 5)
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
                    <input type="text" name="first_name_kana" class="stayInfo_lastName_form" placeholder="例）タハラ"
                        value="{{ empty($lineGuestInfo) ? old('first_name_kana') :  old('first_name_kana', $lineGuestInfo['firstNameKana'] ?? '')}}">
                </div>
                <div class="common_inputItem">
                    <p class="stayInfo_itemText">フリガナ（名）</p>
                    <input type="text" name="last_name_kana" class="stayInfo_firstName_form" placeholder="例）ダイキ"
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
        <select class="stayInfo_checkIn" name="checkin_time" style="display: none;" readonly>
            <option value="{{ date('H:i', strtotime($checkinDate)) }}" selected></option>
        </select>

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">特別リクエスト</p>
                <textarea class="stayInfo_requestForm" name="special_request" value="{{ old('special_request') }}">{{ old('special_request') }}</textarea>
            </div>
            <p class="common_form_error">{{ $errors->first('remarks') }}</p>
        </div>
        @endif

        <!-- 決済方法 -->
        @if ($form->prepay == 0 || $form->prepay == 1)

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
        @if(!$isAdminSearch)
            @if ($form->prepay == 0 || $form->prepay == 2)
            <div class="common_inputForm">
                <button type="button" class="common_footer_btn reservation_complete is-disabled-btn" id="prepay_confirmBtn">
                    事前決済で予約　＞＞
                </button>
            </div>
            @endif
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
<script src="{{ asset('static/user/js/booking/other/inputInfo.js') }}"></script>
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
