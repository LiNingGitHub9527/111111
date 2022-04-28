<div class="plan_detail_wrap">
    <ul class="detail_img_box">
        <li class="slide_img"><img src="{{ $plan->cover_image }}" alt=""></li>
    </ul>
    <div class="detail_main">
        <p class="detail_title">{{ $plan->name }}</p>
        <div class="tag_box">
            @if ($plan->is_meal)
            <p>
                <img src="{{ asset('static/common/images/restaurant 1.png') }}" alt="">
                {{ $plan->meal_type_kana }}
            </p>
            @endif
        </div>
        <div class="facility_title">
            <p class="facility_text">プラン説明</p>
        </div>
        <div class="facility_detail">
            <p style="margin-right: 0;">{{ $plan->description }}</p>
        </div>
        <div class="facility_title">
            <p class="facility_text">キャンセルポリシー</p>
        </div>
        <div class="facility_detail">
            <p style="margin-right: 0;">{{ $plan->cancel_desc }}</p>
            <br>
            <p style="margin-right: 0;">{{ $plan->no_show_desc }}</p>
        </div>
    </div>
    <button type="button" class="common_footer_btn detail_select_btn" href="#" onclick="selectPlan(this)" data-plan_token="{{ $planToken ?? '' }}">
        <div class="reservation_complete">
            このプランを選択
        </div>
    </button>
</div>