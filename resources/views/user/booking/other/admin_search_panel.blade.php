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
    id="user__other__search_panel__searchPanelForm"
    data-lp_param="{{ $lpParam }}"
    data-room_types="{{ json_encode($roomTypes) }}"
    data-hotel="{{ $hotel }}"
    data-hotel_notes="{{ json_encode($hotelNotes) }}"
    data-cancel_desc_message="{{ $cancelDesc }}"
    data-no_show_desc_message="{{ $noShowDesc }}"
    data-reservation_block_list_url="{{ route('user.other.get_reservation_block') }}"
    data-info_input_url="{{ route('user.other.booking_info_input') }}"
    data-info_input_render_url="{{ route('user.other.render_admin_booking_info_input') }}"
    data-close_button_image_url="{{ asset('static/common/images/close_btn.png') }}"
    data-close_image_url="{{ asset('static/common/images/Group.png') }}"
    data-errors="{{ $errors }}"
    data-line_user_id="{{ $lineUserId }}"
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
<script src="{{ mix('/js/user/other/admin_search_panel.js') }}"></script>
@endsection
