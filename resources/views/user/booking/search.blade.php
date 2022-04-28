@extends('user.booking.layouts.index')

@section('content')
<div class="common_main_title">
    <button class="main_title_back_button" style="display: block;" onclick="history.back();">戻る</button>
    <p class="main_title_title_text2">宿泊プラン</p>
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
</div>
@if (!empty(session('error')))
<div class="common_attention_area">
    <p>{{ session('error') }}</p>
</div>
@endif
<!-- 宿泊プラン選択 -->
@php
    $merge_plan_id = function(string $k, array $v): array {
        return array_merge($v, ['id' => intval($k)]);
    };
    $plans = array_map(
        $merge_plan_id, array_keys($planRooms), array_values($planRooms));
@endphp

@if (count($plans) > 0 && isset($plans[0]['status']) && $plans[0]['status'] == 'preview')
<style>
    .facility_title {
        box-sizing: content-box !important;
    }
</style>
<div class="room_type_wrap" id="plan_wrap">
    <div class="room_type_title" id="plan_title">
        <p class="room_type_text">宿泊プランをご選択ください</p>
        <img src="{{ asset('static/common/images/Group.png') }}" alt="" class="plan_detail_cancel_btn is-hidden">
        <button class="cancel_btn plan_cancel_btn" id="choseRoomCancel">取消</button>
    </div>
    <div class="room_list_container" id="planList" style="height: unset;">
        @php $i=0  @endphp
        @foreach ($planRooms as $planId => $plan)
            @if ($i <= 10)
                <div class="room_menu_block">
                    <div class="room_menu_img">
                        <img src="{{ $plan['cover_image'] }}" alt="">
                    </div>
                    <div class="room_menu_contents">
                        <a class="room_menu_main" onclick="PlanShowDetail(this)" data-plan_token="{{ $plan['plan_token'] ?? '' }}" data-html="{{$plan['planDetailHtml'] ?? ''}}" data-status="{{$plan['status'] ?? ''}}">{{ $plan['plan_name'] }}</a>
                        <div class="room_menu_btn">
                            <button class="room_menu_btn_choose plan_choose_btn" style="padding: 1px 6px;" onClick="selectPlan(this)" data-plan_token="{{ $plan['plan_token'] ?? '' }}">このプランを選択</button>
                        </div>
                    </div>
                </div>
            @else
                <div class="room_menu_block is-hidden">
                    <div class="room_menu_img">
                        <img src="{{ $plan['cover_image'] }}" alt="">
                    </div>
                    <div class="room_menu_contents">
                        <a class="room_menu_main" onclick="PlanShowDetail(this)" data-plan_token="{{ $plan['plan_token'] }}">{{ $plan['plan_name'] }}</a>
                        <div class="room_menu_btn">
                            <button class="room_menu_btn_choose plan_choose_btn" onClick="selectPlan(this)" data-plan_token="{{ $plan['plan_token'] }}">このプランを選択</button>
                        </div>
                    </div>
                </div>
            @endif
            @php $i++  @endphp
        @endforeach
    </div>
    @if ((count($planRooms) - 10) > 0)
        <div class="room_type_footer" id="plan_footer">
            <p>残りのプランを見る - {{ (count($planRooms) - 10) >= 0  }}件</p>
        </div>
    @else
        <div class="room_type_footer" id="plan_footer" style="pointer-events: none;">
            <p>全てのプランが表示されています</p>
        </div>
    @endif
</div>
<form action="{{ route('user.booking_info_input') }}" method="get" id="selectConfirm">
    <div class="footer_fix_btn">
        <button type="button" class="common_footer_btn is-disabled-btn" id="roomConfirmBtn">予約を進める >></button>
    </div>
</form>
@else
<div
    id="user__booking__search__planSelect"
    data-plans="{{ json_encode($plans) }}"
    data-close_image_url="{{ asset('static/common/images/Group.png') }}"
    data-plan_detail_url="{{ route('user.booking_ajax_plan_detail') }}"
    data-restaurant_image_url="{{ asset('static/common/images/restaurant 1.png') }}"
    data-plan_room_type_url="{{ route('user.booking_ajax_plan_room_type') }}"
    data-selected_room_data_url="{{ route('user.booking_ajax_plan_room_type_selected') }}"
    data-room_detail_url="{{ route('user.booking_ajax_plan_room_type_detail') }}"
    data-info_input_url="{{ route('user.booking_info_input') }}"
    data-selected_room_cancel_url="{{ route('user.booking_ajax_plan_room_type_select_cancel') }}"
></div>
@endif

<!-- ホテルポリシー -->
<div class="hotel_policy_confirm">
    <div class="hotel_policy_header" id="hotel_policy_pulldown" style="cursor: pointer;">
        <p class="hotel_policy_title">ホテルポリシー</p>
        <div class="hotel_policy_jump_img">
            <img class="hotel_policy_opened is-hidden" src="{{ asset('static/common/images/chevron-left-solid_black.png') }}" alt="">
            <img class="hotel_policy_closed" src="{{ asset('static/common/images/chevron-left-solid_black.png') }}" alt="">
        </div>
    </div>
    <div class="hotel_policy_main">
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
@endsection

@section('scripts')
<script>
    var csrfToken = '{{ csrf_token() }}';
</script>
<script>
    $('#roomConfirmBtn').on('click', function(){
        $('#roomConfirmBtn').LoadingOverlay("show");
    })

    let status = []

    $(function() {
        if ($('.room_menu_main').data('status') == 'preview') {
            $('#plan_wrap').find('.plan_detail_cancel_btn').removeClass('is-hidden')
            $('#planList').hide();
            $('#plan_footer').hide();
            let planDetailHtml = $('.room_menu_main').data('html')
            $('#plan_title').after(planDetailHtml);
        }

        $('#hotel_policy_jump').on('click', function() {
            adjust = 1000;
            scrollbtn = $(this).attr('href');
            target = $(scrollbtn).offset().top + adjust;
            $("html, body").animate({
                scrollTop: target
            }, 'slow', 'swing');
            $('.hotel_policy_main').addClass('active');
            $('.hotel_policy_opened').removeClass('is-hidden');
            $('.hotel_policy_closed').addClass('is-hidden');
            return false;
        });

        $('#hotel_policy_pulldown').on('click', function() {
            $('.hotel_policy_main').toggleClass('active');
            $('.hotel_policy_opened').toggleClass('is-hidden');
            $('.hotel_policy_closed').toggleClass('is-hidden');
        });
    });

   // #####################################
   function PlanShowDetail(obj)
    {
        if ($(obj).data('status') == 'preview'){
            $('#plan_wrap').find('.plan_detail_cancel_btn').removeClass('is-hidden');
            $('#planList').hide();
            $('#plan_footer').hide();
            let planDetailHtml = $('.room_menu_main').data('html');
            $('#plan_title').after(planDetailHtml);
        }
    }

    function selectPlan(obj)
    {
        if($('.room_menu_main').data('status')){
            alert('プレビュー画面ではこのボタンはクリックできません');
        }
    }

    $(document).on('click', '.plan_detail_cancel_btn', function(){
        $('#plan_wrap').find('.plan_detail_wrap').remove();
        $('#plan_wrap').find('.plan_detail_cancel_btn').addClass('is-hidden');
        $('#planList').show();
        $('#plan_footer').show();
    })

    // $(document).on('click', '.room_selected_cancel_btn', function(){
    //     let roomToken = $(this).data('room_token')
    //     let roomNum = $(this).data('room_num')
    //     CallCancelSelectedRoom(roomToken, roomNum)
    //         .then((cancelRoomJson) => {
    //             if (cancelRoomJson.res == 'ok') {
    //                 $(this).attr('data-room_token', '')
    //                 let targetWrap = $(this).closest('.room_type_wrap')
    //                 $(targetWrap).find('.ajax__room_selected_area').html('')
    //                 $(targetWrap).find('.room_menu_block').show()
    //                 $(targetWrap).find('.room_type_order').show()
    //                 $(targetWrap).find('.room_type_footer').show()
    //                 $(targetWrap).find('.room_type_title').find('.room_selected_cancel_btn').removeClass('active')
    //                 $('#roomConfirmBtn').addClass('is-disabled-btn');

    //                 let targetClass
    //                 let exceptClass
    //                 let isExcept
    //                 let exceptCurrentId = '#roomList__' + cancelRoomJson.room_num
    //                 let roomLists = $('.only_room_type_container')
    //                 $.each(roomLists, function(index, value){
    //                     if ( $.inArray($(value).attr('id'), cancelRoomJson.selectedNums) > 0)  {
    //                         $.each(cancelRoomJson.showRoomTokens, function(index, token){
    //                             targetClass = '.room_token__' + token
    //                             $(value).find(targetClass).show()
    //                         })
    //                     }
    //                 })
    //             } else {
    //                 alert(cancelRoomJson.message)
    //             }
    //         })
    // })
    // ############################
</script>
<script src="{{ mix('/js/user/booking/search.js') }}"></script>
@endsection