var submitStatus = false;
jQuery(function($){
    $('#confirmBtn').on('click', () => {
        $(this).addClass('is-disabled-btn')
        $('#confirm_form').submit()
    })

    $('#prepay_confirmBtn').on('click', () => {
        $('input:visible[data-required=1], select:visible[data-required=1]').each(function(key, value){
            if ($(value).val() == '') {
                $('#confirmBtn').addClass('is-disabled-btn');
                $('#prepay_confirmBtn').addClass('is-disabled-btn');
                alert('入力項目を入力してください')
                return false
            } else {
                $('#confirmBtn').removeClass('is-disabled-btn');
                $('#prepay_confirmBtn').removeClass('is-disabled-btn');
                $('#prepayForm').show()
                $('.common_popup_filter').show()
            }
        })
    })

    $('#confirm_form').on('change', () => {
        checkAllInput()
    })

    checkAllInput = () => {
        $('input:visible[data-required=1], select:visible[data-required=1]').each(function(key, value){
            if ($(value).val() == '') {
                $('#confirmBtn').addClass('is-disabled-btn');
                $('#prepay_confirmBtn').addClass('is-disabled-btn');
                return false
            } else {
                $('#confirmBtn').removeClass('is-disabled-btn');
                $('#prepay_confirmBtn').removeClass('is-disabled-btn');
            }
        })
    }

    $(document).on('ready', checkAllInput());

});