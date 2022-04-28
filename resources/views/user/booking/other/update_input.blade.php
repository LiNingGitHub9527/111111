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
<div class="inputForm_wrap">
    <form action="{{ route('user.other.booking_update_booking') }}" method="POST" id="confirm_form">
        {{ csrf_field() }}
        <!-- 見出し -->
        <div class="common_main_title">
            <button type="button" class="main_title_back_button" style="display: block;"
                onclick="location.href='{{ '/page/reservation/search_panel?url_param=' . $bookingData['base_info']['url_param'] }}'">戻る</button>
            <p class="main_title_title_text">予約情報を入力</p>
            <p class="main_title_title_text_sp">予約情報を入力</p>
        </div> <!-- common_main_title -->
        <!-- 入力フォーム -->
        @if ($businessType == 3 || $businessType == 4)
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
                <input type="text" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                @elseif ($item['data_type'] == 2)
                <!-- 長文テキスト -->
                <textarea name="{{ 'item_' . $item['id'] }}" data-required="{{ $item['is_required'] }}"
                    class="stayInfo_requestForm">{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}</textarea>
                @elseif ($item['data_type'] == 3)
                <!-- 数値 -->
                <input type="number" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                @elseif ($item['data_type'] == 4)
                <!-- 日付 -->
                <input type="date" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                @elseif ($item['data_type'] == 5)
                <!-- 時間 -->
                <input type="time" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}" step="1">
                @elseif ($item['data_type'] == 6)
                <!-- 日付+時間 -->
                <input type="datetime-local" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], !empty($reservation['base_customer_item_values'][$item['id']]['value']) ? date('Y-m-d\TH:i:s', strtotime($reservation['base_customer_item_values'][$item['id']]['value'])) : '') }}" step="1">
                @elseif ($item['data_type'] == 7)
                <!-- 性別 -->
                <select class="reservation_info_gender" name="{{ 'item_' . $item['id'] }}" data-required="{{ $item['is_required'] }}">
                    <option value disabled selected>選択してください</option>
                    <option value="1" @if(old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value']) == 1) selected @endif>男性</option>
                    <option value="2" @if(old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value']) == 2) selected @endif>女性</option>
                    <option value="3" @if(old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value']) == 3) selected @endif>その他</option>
                </select>
                @elseif ($item['data_type'] == 8)
                <!-- 氏名 -->
                <input type="text" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    placeholder="例）山田太郎" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                @elseif ($item['data_type'] == 9)
                <!-- 電話番号 -->
                <input type="tel" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    placeholder="例）09012345678" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                @elseif ($item['data_type'] == 10)
                <!-- メールアドレス -->
                <input type="email" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    placeholder="例）sample@sample.com" data-required="1"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                    </div>
                    <p class="common_form_error">{{ $errors->first('item_' . $item['id']) }}</p>
                </div>
                <div class="common_inputForm">
                    <div class="common_inputItem">
                        <p class="stayInfo_itemText">
                            メールアドレス確認
                            <span class="required">※必須</span>
                        </p>
                        <input type="email" name="{{ 'item_' . $item['id'] . '_confirm' }}" class="stayInfo_email_form"
                            placeholder="例）sample@sample.com" data-required="1" autocomplete="off"
                            value="{{ old('item_' . $item['id'] . '_confirm', $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                    </div>
                    <p class="common_form_error">{{ $errors->first('item_' . $item['id'] . '_confirm') }}</p>
                </div>
                @elseif ($item['data_type'] == 11)
                <!-- 住所 -->
                <input type="text" name="{{ 'item_' . $item['id'] }}" class="stayInfo_emailConfirm_form"
                    placeholder="例）〇〇県〇〇市〇〇町" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                @elseif ($item['data_type'] == 12)
                <!-- 部屋タイプ名 -->
                <input type="text" name="{{ 'item_' . $item['id'] }}" class="stayInfo_email_form"
                    placeholder="例）スタンダード" data-required="{{ $item['is_required'] }}"
                    value="{{ old('item_' . $item['id'], $reservation['base_customer_item_values'][$item['id']]['value'] ?? '') }}">
                @elseif ($item['data_type'] == 13)
                <!-- チェックイン日 -->
                <input type="date" name="{{ 'item_' . $item['id'] }}" class="reservation_info_date"
                    value="{{ old('item_' . $item['id'], $checkinDate ?? '') }}" data-required="{{ $item['is_required'] }}">
                @elseif ($item['data_type'] == 14)
                <!-- 予約開始時間 -->
                <span>
                    <input type="number" class="reservation_info_date" placeholder="12" min="0" max="99" step="1"
                        name="{{ 'item_' . $item['id'] . '_hour' }}"　data-required="{{ $item['is_required'] }}"
                        value="{{ old('item_' . $item['id'] . '_hour', $startTime ? explode(':', $startTime)[0] : '') }}">
                    <span class="reservation_info_date_separator">:</span>
                    <input type="number" class="reservation_info_date" placeholder="30" min="0" max="59" step="1"
                        name="{{ 'item_' . $item['id'] . '_minute' }}" data-required="{{ $item['is_required'] }}"
                        value="{{ old('item_' . $item['id'] . '_minute', $startTime ? explode(':', $startTime)[1] : '') }}">
                </span>
                @elseif ($item['data_type'] == 15)
                <!-- 予約終了時間 -->
                <span>
                    <input type="number" class="reservation_info_date" placeholder="12" min="0" max="99" step="1"
                        name="{{ 'item_' . $item['id'] . '_hour' }}"　data-required="{{ $item['is_required'] }}"
                        value="{{ old('item_' . $item['id'] . '_hour', $endTime ? explode(':', $endTime)[0] : '') }}">
                    <span class="reservation_info_date_separator">:</span>
                    <input type="number" class="reservation_info_date" placeholder="30" min="0" max="59" step="1"
                        name="{{ 'item_' . $item['id'] . '_minute' }}" data-required="{{ $item['is_required'] }}"
                        value="{{ old('item_' . $item['id'] . '_minute', $endTime ? explode(':', $endTime)[1] : '') }}">
                </span>
                @endif
        @if ($item['data_type'] != 10)
            </div>
            <p class="common_form_error">{{ $errors->first('item_' . $item['id']) }}</p>
        </div>
        @endif
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
                        value="{{ old('email', $reservation->email ?? '') }}">
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
                        value="{{ old('email_confirm', $reservation->email ?? '') }}">
                </div>
                <p class="common_form_error">{{ $errors->first('email_confirm') }}</p>
            </div>
            @endif
        @elseif ($businessType == 2 || $businessType == 5)
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

        <div class="common_inputForm">
            <div class="common_inputItem">
                <p class="stayInfo_itemText">特別リクエスト</p>
                <textarea class="stayInfo_requestForm" name="special_request" value="{{ old('special_request', $reservation->special_request) }}">{{ old('special_request', $reservation->special_request) }}</textarea>
            </div>
            <p class="common_form_error">{{ $errors->first('special_request') }}</p>
        </div>
        @endif

        <div class="common_inputForm">
            <button type="button" class="common_footer_btn reservation_complete" id="confirmBtn">
                変更を確定
            </button>
        </div>
    </form>
</div> <!-- /inputForm_container -->
@endsection

@section('scripts')
<script src="{{ asset('static/user/js/booking/other/inputInfo.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
<script>
    $('#confirmBtn').on('click', function(){
        $('#confirmBtn').LoadingOverlay("show");
    })
</script>
@endsection
