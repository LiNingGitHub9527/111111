<div class="common_popup_filter"></div>
<div class="common_popup_block" id="prepayForm" style="padding: 20px; width: 85%;">
    <button type="button" class="common_popup_close" onClick="hidePopupBlock(this)">
        <img src="{{ asset('static/common/images/close_btn.png') }}" alt="close">
    </button>
    <p class="common_popup_title">キャンセル料金：　{{ ($cancelFeeData['cancel_fee'] != 0) ? '￥' . number_format($cancelFeeData['cancel_fee']) : '無料' }}</p>
    <div class="common_popup_input_area">
        <p class="introduce_text">
            キャンセルポリシーに応じてキャンセル料が発生します。<br>
            確認がある場合は、事前に施設に直接お問い合わせくださいませ。<br>

            @if ($paymentMethod == 0 && !$cancelFeeData['is_free_cancel'])
                <p class="introduce_text" style="text-decoration: underline;">
                    この予約は既に無料キャンセル期間を過ぎています。
                    キャンセルの場合は<br>
                    施設にお問い合わせくださいませ。
                </p>
            @endif
        </p>
    </div>
    <div style="width: 50%; margin: 20px auto 0;">
        @if ($paymentMethod == 0 && !$cancelFeeData['is_free_cancel'])
        <button type="button" class="common_footer_btn reservation_complete" style="margin-bottom: 10px; background: #f1f1f1;">予約をキャンセル</button>
        @else
        <button type="button" class="common_footer_btn reservation_complete" style="margin-bottom: 10px;" onClick="CancelConfirmClick()">予約をキャンセル</button>
        @endif
    </div>
</div>