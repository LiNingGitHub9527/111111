var submitStatus = false;
jQuery(function($){
    $('#confirmBtn').on('click', () => {
        $(this).addClass('is-disabled-btn')
        $('#confirm_form').submit()
    })

    $('#prepay_confirmBtn').on('click', () => {
        $('input:visible, select:visible').each(function(key, value){
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
        $('input:visible, select:visible').each(function(key, value){
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

    // $('#confirmPrepay').on('click', () => {
    //     $('#confirmBtn').attr('disabled', 'disabled');
    //     // $("#confirmPrepay").LoadingOverlay("show");
    //     const cardNumber = $('#credit_card_number').val()
    //     const limitMonth = $('#credit_limit_month').val()
    //     const limitYear = $('#credit_limit_year') .val()
    //     const CVC = $('#credit_security_code').val()
    //     const firstName = $('input[name="first_name"]').val()
    //     const lastName = $('input[name="last_name"]').val()
    //     const name = firstName + lastName
    //     const firstNameKana = $('input[name="first_name_kana"]').val()
    //     const lastNameKana = $('input[name="last_name_kana"]').val()
    //     const email = $('input[name="email"]').val()
    //     const emailConfirm = $('input[name="email_confirm"]').val()
    //     const address1 = $('input[name="address1"]').val()
    //     const address2 = $('input[name="address2"]').val()
    //     const tel = $('input[name="tel"]').val()
    //     const checkinTime = $('select[name="checkin_time"]').val()
    //     const specialRequest = $('input[name="special_request"]').val()
    //     const postForm = $('#confirm_form')

    //     $.ajax({
    //         url: '/booking/prepay',
    //         type: 'POST',
    //         data: {
    //             card_number: cardNumber,
    //             expiration_month: limitMonth,
    //             expiration_year: limitYear,
    //             cvc: CVC,
    //             name: name,
    //             first_name: firstName,
    //             last_name: lastName,
    //             first_name_kana: firstNameKana,
    //             last_name_kana: lastNameKana,
    //             email: email,
    //             email_confirm: emailConfirm,
    //             address1: address1,
    //             address2: address2,
    //             tel: tel,
    //             checkin_time: checkinTime,
    //             special_request: specialRequest,
    //             _token: $('meta[name="csrf-token"]').attr('content'),
    //         }
    //     }).then((data) => {
    //         if (data.res == 'ok') {
    //             $(postForm).submit();
    //         } else {
    //             if (data.code == 1422) {
    //                 let message = Object.values(data.message);
    //                     $.each(message, function(item, index){
    //                         for (let i = 0; i < 1; i++) {
    //                             alert(index[i])
    //                         }
    //                 })
    //             } else {
    //                 alert(data.message)
    //             }
    //             submitStatus = true;
    //             $('#confirmBtn').removeAttr('disabled');
    //             $('#loader-bg').hide()
    //         }
    //     })
    // })
});