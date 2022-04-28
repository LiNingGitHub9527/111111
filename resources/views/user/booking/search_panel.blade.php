@extends('user.booking.layouts.index')

@section('content')

@if (empty($notReserve))
<div class="common_main_title" style="margin-bottom: 16px;">
        <button class="main_title_back_button" style="display: block;" onclick="history.back();">戻る</button>
    <p class="main_title_title_text2">お部屋選択</p>
</div>
@if (!empty(session('error')))
<div class="common_attention_area">
    <p>{{ session('error') }}</p>
</div>
@endif
<div
    id="user__booking__search_panel__searchPanelForm"
    data-stay_search_data_url="{{ route('user.booking_stay_search') }}"
    data-dayuse_search_data_url="{{ route('user.booking_dayuse_search') }}"
    data-max_child_age="{{ $maxChildAge }}"
    data-max_adult_num="{{ $maxAdultNum }}"
    data-max_child_num="{{ $maxChildNum }}"
    data-kids_policies="{{ json_encode($kidsPolicies) }}"
    data-lp_param="{{ $lpParam }}"
    data-close_button_image_url="{{ asset('static/common/images/close_btn.png') }}"
    data-show_dayuse_switch="{{ $isDayuse && empty($hideSwitch) }}"
    data-checkin_time_url="{{ route('user.booking_ajax_get_select_checkin_time') }}"
    data-stay_time_url="{{ route('user.booking_ajax_get_select_stay_time') }}"
    data-errors="{{ $errors }}"
    data-close_image_url="{{ asset('static/common/images/Group.png') }}"
    data-plan_detail_url="{{ route('user.booking_ajax_plan_detail') }}"
    data-restaurant_image_url="{{ asset('static/common/images/restaurant 1.png') }}"
    data-plan_room_type_url="{{ route('user.booking_ajax_plan_room_type') }}"
    data-selected_room_data_url="{{ route('user.booking_ajax_plan_room_type_selected') }}"
    data-room_detail_url="{{ route('user.booking_ajax_plan_room_type_detail') }}"
    data-info_input_url="{{ route('user.booking_info_input') }}"
    data-selected_room_cancel_url="{{ route('user.booking_ajax_plan_room_type_select_cancel') }}"
    data-hotel="{{ $hotel }}"
    data-hotel_notes="{{ json_encode($hotelNotes) }}"
>
</div>
@else
<div class="common_attention_area">
    <p>{{ $attentionMessage }}</p>
</div>
@endif

@endsection

@section('scripts')
<script>
    var csrfToken = '{{ csrf_token() }}';
</script>
<script src="{{ mix('/js/user/booking/search_panel.js') }}"></script>
@endsection