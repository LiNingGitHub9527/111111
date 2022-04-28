jQuery(function($){
    const weeks = ['日', '月', '火', '水', '木', '金', '土']
    const config = {
        show: 1,
    }

    CallCreateInCalendar = (nowYear, nowMonth) => {
        let appendCalendar = CreateCalendar(nowYear, nowMonth, 'チェックイン', 1)
        $('.static-search_panel__wrapper').hide();
        $('#calendar_check_in').html('')
        $('#calendar_check_out').html('')
        $('#calendar_check_in').append(appendCalendar)
        $('.static-search_panel__wrapper_in').show();
        $('.static-search_panel__filter').show()
    }

    CallCreateOutCalendar = (nowYear, nowMonth) => {
        let appendCalendar = CreateCalendar(nowYear, nowMonth, 'チェックアウト', 2)
        $('.static-search_panel__wrapper').hide();
        $('#calendar_check_in').html('')
        $('#calendar_check_out').html('')
        $('#calendar_check_out').append(appendCalendar)
        $('.static-search_panel__wrapper_out').show();
        $('.static-search_panel__filter').show()
    }

    CreateCalendar = (nowYear, nowMonth, leadTx, inOrOut) => {
        const startDate = new Date(nowYear, nowMonth - 1, 1);
        const endDate = new Date(nowYear, nowMonth, 0);
        const endDayCount = endDate.getDate() // 月の末日
        const lastMonthEndDate = new Date(nowYear, nowMonth - 1, 0) // 前月の最後の日の情報
        const lastMonthendDayCount = lastMonthEndDate.getDate() // 前月の末日
        const startDay = startDate.getDay() // 月の最初の日の曜日を取得

        if (nowMonth == 0) {
            nowYear -= 1;
            nowMonth = 12;
        }
        const prev = new Date(nowYear, nowMonth - 1, 1);
        const prevYear = prev.getFullYear();
        const prevMonth = prev.getMonth();

        const next = new Date(nowYear, nowMonth, 1);
        const nextYear = next.getFullYear();
        const nextMonth = next.getMonth() + 1;


        let calendarHtml = '';

        let leadTxHtml = '<p class="inout_lead_tx">' + leadTx + '</p>'
        calendarHtml += '<section>'
        if (inOrOut == 1) {
            calendarHtml += '<div class="calendar_header"><button class="prev_wrap" type="button" onClick="CallCreateInCalendar(' +  prevYear + ',' + prevMonth + ')"><img id="prev_in" src="/static/common/images/prev.svg"></button><h1>' + nowYear  + '.' + nowMonth + leadTxHtml + '</h1><button class="prev_wrap" type="button" onClick="CallCreateInCalendar(' +  nextYear + ',' + nextMonth + ')"><img id="prev_in" src="/static/common/images/next.svg"></button></div>'
        } else {
            calendarHtml += '<div class="calendar_header"><button class="prev_wrap" type="button" onClick="CallCreateOutCalendar(' +  prevYear + ',' + prevMonth + ')"><img id="prev_in" src="/static/common/images/prev.svg"></button><h1>' + nowYear  + '.' + nowMonth + leadTxHtml + '</h1><button class="prev_wrap" type="button" onClick="CallCreateOutCalendar(' +  nextYear + ',' + nextMonth + ')"><img id="prev_in" src="/static/common/images/next.svg"></button></div>'
        }

        calendarHtml += '<table class="calendar_table">'
        calendarHtml = AddWeekRow(calendarHtml);
        calendarHtml = AddDateRows(calendarHtml, startDay, lastMonthendDayCount, endDayCount, nowYear, nowMonth, inOrOut);
        calendarHtml += '</table>'
        calendarHtml += '</section>'

        return calendarHtml;
    }

    AddWeekRow = (calendarHtml) => {
        for (let i = 0; i < weeks.length; i++) {
            calendarHtml += '<td class="dayWeek">' + weeks[i] + '</td>'
        }
        return calendarHtml
    }

    AddDateRows = (calendarHtml, startDay, lastMonthendDayCount, endDayCount, nowYear, nowMonth, inOrOut) => {
        let dayCount = 1
        let dataDate;
        for (let w = 0; w < 6; w++) {
            calendarHtml += '<tr>'
    
            for (let d = 0; d < 7; d++) {
                if (w == 0 && d < startDay) {
                    // 1行目で1日の曜日の前
                    let num = lastMonthendDayCount - startDay + d + 1
                    calendarHtml += '<td class="date is-disabled"></td>'
    
                } else if (dayCount > endDayCount) {
    
                    // 末尾の日数を超えた
                    let num = dayCount - endDayCount
                    calendarHtml += '<td class="date is-disabled"></td>'
                    dayCount++
                } else {
                    dataDate = nowYear + '/' + nowMonth + '/' + dayCount
                    calendarHtml += '<td class="date" data-date="' + dataDate +'" onClick="setDateValue(this, ' + inOrOut + ')">' + dayCount + '</td>'
    
                    dayCount++
                }
            }
            calendarHtml += '</tr>'
        }
    
        return calendarHtml
    }

    // event listner
    $(document).on('mouseover', '.date', () => {
        $(this).css('background', '#EABEBF')
    });
    
    $(document).on('mouseout', '.date', () => {
        $(this).css('background', 'rgba(220,220,220,0.1)')
    });

    setDateValue = (obj, inOrOut) => {
        let dateVal = $(obj).data('date')
        if (inOrOut == 1) {
            $('#checkin_input').val(String(dateVal))
            $('.static-search_panel__wrapper_in').hide()
            $('.static-search_panel__filter').hide()
        } else if (inOrOut == 2) {
            $('#checkout_input').val(String(dateVal))
            $('.static-search_panel__wrapper_out').hide()
            $('.static-search_panel__filter').hide()
        }
    }
});