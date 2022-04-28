@extends('user.booking.layouts.index')

@section('content')
@if (empty($notReserve))
<div class="common_main_title">
    <button class="main_title_back_button" style="display: block;">戻る</button>
    <p class="main_title_title_text2">予約確認・変更</p>
</div>
<!-- ホテルポリシーの確認 -->
<div class="hotel_policy_confirm">
    <a href="#hotel_policy_pulldown" id="hotel_policy_jump">
        <div class="hotel_policy_jumper">
            <p class="hotel_policy_title">ホテルポリシーの確認</p>
            <div class="hotel_policy_jump_img">
                <img src="{{ asset('static/common/images/chevron-left-solid 1.png') }}" alt="">
            </div>
        </div>
    </a>
    <a href="#cancel_policy_pulldown" id="cancel_policy_jump">
        <div class="hotel_policy_jumper" style="margin-top: 10px;">
            <p class="hotel_policy_title">キャンセルポリシーの確認</p>
            <div class="hotel_policy_jump_img">
                <img src="{{ asset('static/common/images/chevron-left-solid 1.png') }}" alt="">
            </div>
        </div>
    </a>
</div>
<!-- 宿泊日程-->
<div class="room_type_wrap" id="plan_wrap">
    <div class="room_type_title" id="plan_title">
        <p class="room_type_text">宿泊日程</p>
    </div>
    <div class="room_list_container" id="planList">
        <div class="room_menu_block">
            <div class="room_menu_contents">
                @if ($reservation['stay_type'] == 1)
                <p class="room_menu_main room_menu_main_show">{{ date('Y年n月j日 H:i', strtotime($reservation['checkin_time'])) . ' 〜 ' . date('Y年n月j日 H:i', strtotime($reservation['checkout_end'])) }}</p>
                @elseif ($reservation['stay_type'] == 2)
                <p class="room_menu_main room_menu_main_show">{{ date('Y年n月j日 H:i', strtotime($reservation['checkin_time'])) . ' 〜 ' . date('Y年n月j日 H:i', strtotime($reservation['checkout_time'])) }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- 宿泊プラン選択 -->
<div class="room_type_wrap" id="plan_wrap">
    <div class="room_type_title" id="plan_title">
        <p class="room_type_text">宿泊プラン</p>
        <img src="{{ asset('static/common/images/Group.png') }}" alt="" class="plan_detail_cancel_btn is-hidden">
    </div>
    <div class="room_list_container" id="planList">
        <div class="room_menu_block">
            <div class="room_menu_contents">
                <p class="room_menu_main room_menu_main_show">{{ $plan->name }}</p>
            </div>
        </div>
    </div>
</div>
<div id="room_area_wrapper">
    @foreach ($reservation['rooms'] as $roomNumber => $room)
    <div class="room_type_wrap" id="room_wrap__1">
        <div class="room_type_title">
            <p class="room_type_text">{{ $roomNumber }}部屋目（大人{{ $room['adult_num'] }}人, 子供{{ $room['child_num'] }}人）</p>
            <img src="{{ asset('static/common/images/Group.png') }}" alt="" class="detail_cancel_btn is-hidden">
        </div>
        <div class="room_list_container" id="roomList__1">
            <div class="room_menu_block">
                <div class="room_menu_contents">
                    <p class="room_menu_main room_menu_main_show">{{ $roomTypes[$room['room_type_id']]['name'] }}</p>
                    <div class="room_menu_btn">
                        <button class="room_menu_btn_price">¥{{ number_format($room['amount']) }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="room_type_wrap" id="plan_wrap">
    <div class="room_type_title" id="plan_title">
        <p class="room_type_text">合計額</p>
    </div>
    <div class="room_list_container" id="planList">
        <div class="room_menu_block">
            <div class="room_menu_contents">
                <p class="room_menu_main room_menu_main_show">¥{{ number_format($reservation['accommodation_price']) }}(税込)</p>
            </div>
        </div>
    </div>
</div>
<div class="hotel_policy_confirm">
    <div class="hotel_policy_header" id="hotel_policy_pulldown">
        <p class="hotel_policy_title">ホテルポリシー</p>
        <div class="hotel_policy_jump_img">
            <img class="hotel_policy_opened is-hidden" src="{{ asset('static/common/images/chevron-left-solid_black.png') }}" alt="">
            <img class="hotel_policy_closed" src="{{ asset('static/common/images/chevron-left-solid_black.png') }}" alt="">
        </div>
    </div>
    <div class="hotel_policy_main" id="hotel_policy_main">
        <div class="checkin_menu_container">
            <div class="checkin_menu_block">
                <p>チェックイン</p>
                <p>{{ date('H:i', strtotime($hotel->checkin_start)) }} 〜 {{ date('H:i', strtotime($hotel->checkin_end)) }}</p>
            </div>
            <div class="checkin_menu_block">
                <p>チェックアウト</p>
                <p>{{ date('H:i', strtotime($hotel->checkout_end)) }} まで</p>
            </div>
        </div>
        <div class="introduce_text_block">
            <div class="introduce_text_section">
                <p class="introduce_text_title">住所</p>
                <p class="introduce_text">{{ $hotel->address }}</p>
            </div>
            @foreach ($hotelNotes as $note)
            <div class="introduce_text_section">
                <p class="introduce_text_title">{{ $note['title'] }}</p>
                <p class="introduce_text">{{ $note['content'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</div>
<div class="hotel_policy_confirm" style="margin-top: 10px;">
    <div class="hotel_policy_header" id="cancel_policy_pulldown">
        <p class="hotel_policy_title">キャンセルポリシー</p>
        <div class="hotel_policy_jump_img">
            <img class="hotel_policy_opened is-hidden" src="{{ asset('static/common/images/chevron-left-solid_black.png') }}" alt="">
            <img class="hotel_policy_closed" src="{{ asset('static/common/images/chevron-left-solid_black.png') }}" alt="">
        </div>
    </div>
    <div class="hotel_policy_main" id="cancel_policy_main">
        <div class="introduce_text_block">
            <div class="introduce_text_section">
                <p class="introduce_text_title">キャンセルの場合</p>
                <p class="introduce_text">{{ $cancelDesc }}</p>
            </div>
            <div class="introduce_text_section">
                <p class="introduce_text_title">無断でのキャンセルの場合</p>
                <p class="introduce_text">{{ $noShowDesc }}</p>
            </div>
        </div>
    </div>
</div>
<div class="bottom_btn_area" style="margin: 40px 0 0">
    @if ($isFreeCancel)
        <button type="button" class="common_footer_btn" style="margin-bottom: 20px;" onClick="ShowChangeConfirm()">予約の変更 >></button>
    @endif
    @if($cancelable)
        <button type="button" class="common_footer_sub_btn" onClick="CheckIsCancelAble()">キャンセル >></button>
    @endif
</div>
@else
<div class="common_attention_area">
    <p>{{ $attentionMessage }}</p>
</div>
@endif
<div id="popupArea"></div>

<div class="common_popup_filter" style="display: none;"></div>
<div class="common_popup_block" id="changeConfirmBlock" style="display: none; padding: 20px; width: 85%;">
    <button type="button" class="common_popup_close" onClick="hidePopupBlock(this)">
        <img src="{{ asset('static/common/images/close_btn.png') }}" alt="close">
    </button>
    <p class="common_popup_title">予約を変更しますか？</p>
    <div style="width: 50%; margin: 20px auto 0;">
        <button type="button" class="common_footer_btn reservation_complete" style="margin-bottom: 10px;" id="changeConfirmBtn">予約を変更</button>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('static/user/js/booking/selectPlanRoom.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.7/dist/loadingoverlay.min.js"></script>
<script>
    $('#changeConfirmBtn').on('click', function(){
        $('#changeConfirmBtn').LoadingOverlay("show");
    })

    $(function(){
        $('.common_header_block').hide()
    })

    function ShowChangeConfirm()
    {
        $('#changeConfirmBlock').show()
        $('.common_popup_filter').show()
    }

    $('#changeConfirmBtn').on('click', () => {
        CallChangeReserve().then((ChangeJson) => {
            if (ChangeJson.res == 'ok') {
                console.log(ChangeJson.ur);
                window.location.href = ChangeJson.url
            } else {
                alert(ChangeJson.message)
                window.location.reload()
            }
        })
    })

    async function CallChangeReserve()
    {
        let CsrfToken = "{{ csrf_token() }}"
        let url = "{{ route('user.booking_ajax_reserve_change_confirm') }}";
        let res = await CancelPromise(CsrfToken, url)
        return res
    }

    $('#hotel_policy_pulldown').on('click', function() {
        $('#hotel_policy_main').toggleClass('active');
        $('#hotel_policy_pulldown .hotel_policy_opened').toggleClass('is-hidden');
        $('#hotel_policy_pulldown .hotel_policy_closed').toggleClass('is-hidden');
    });
    $('#cancel_policy_pulldown').on('click', function() {
        $('#cancel_policy_main').toggleClass('active');
        $('#cancel_policy_pulldown .hotel_policy_opened').toggleClass('is-hidden');
        $('#cancel_policy_pulldown .hotel_policy_closed').toggleClass('is-hidden');
    });

    $('#hotel_policy_jump').on('click', function() {
        adjust = 1000;
        scrollbtn = $(this).attr('href');
        target = $(scrollbtn).offset().top + adjust;
        $("html, body").animate({
            scrollTop: target
        }, 'slow', 'swing');
        $('#hotel_policy_main').addClass('active');
        $('#hotel_policy_pulldown .hotel_policy_opened').removeClass('is-hidden');
        $('#hotel_policy_pulldown .hotel_policy_closed').addClass('is-hidden');
        return false;
    });

    $('#cancel_policy_jump').on('click', function() {
        adjust = 1000;
        scrollbtn = $(this).attr('href');
        target = $(scrollbtn).offset().top + adjust;
        $("html, body").animate({
            scrollTop: target
        }, 'slow', 'swing');
        $('#cancel_policy_main').addClass('active');
        $('#cancel_policy_pulldown .hotel_policy_opened').removeClass('is-hidden');
        $('#cancel_policy_pulldown .hotel_policy_closed').addClass('is-hidden');
        return false;
    });

    function CheckIsCancelAble()
    {
        CallCheckCancelCondition()
            .then((CancelPolicyJson) => {
                if (CancelPolicyJson.res == 'ok') {
                    $('#popupArea').html('')
                    $('#popupArea').append(CancelPolicyJson.html)
                } else {
                    alert(CancelPolicyJson.message)
                }
            })
    }

    async function CallCheckCancelCondition(){
        let CsrfToken = "{{ csrf_token() }}"
        let url = "{{ route('user.booking_ajax_check_is_cancelable') }}";
        let res = await CancelPromise(CsrfToken, url)
        return res
    }

    function CancelConfirmClick()
    {
        CallCanceConfirm()
            .then((CancelJson) => {
                if (CancelJson.res == 'ok') {
                    alert('キャンセルしました。')
                    window.location.reload()
                } else {
                    alert(CancelJson.message)
                }
            })
    }

    async function CallCanceConfirm()
    {
        let CsrfToken = "{{ csrf_token() }}"
        let url = "{{ route('user.booking_ajax_reserve_cancel_confirm') }}";
        let res = await CancelPromise(CsrfToken, url)
        return res
    }

</script>
@endsection 