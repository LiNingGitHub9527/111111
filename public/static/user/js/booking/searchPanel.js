jQuery(function($){
    $('#add_search_room').on('click', (e) => {
        const addRoomNum = $('#add_search_room').attr('value')
        const clone = $('.static-search_panel__select_room_clone').clone()
        const roomNumTitle = addRoomNum + '部屋目'
        const deleteRoomTx = addRoomNum + '部屋目削除'
        clone.find('.static-search_panel__item_title').text(roomNumTitle)
        clone.find('.static-search_panel__delete_btn').text(deleteRoomTx);
        clone.find('.static-search_panel__delete_btn').val(addRoomNum)
        $('.static-search_panel__select_room_wrap').append(clone.html());
        const nextRoomNum = Number(addRoomNum) + 1;
        const addRoomTx = String(nextRoomNum) + '部屋目追加'
        $('#add_search_room').attr('value', nextRoomNum)
        $('#add_search_room').text(addRoomTx)
        $('input[name="room_num"]').val(addRoomNum)
    });

    deleteSelectRoom = (obj) => {
        let wrapper = $(obj).parent('.static-search_panel__title_flex').parent('.static-search_panel__item_wrap')
        let thisNumber = $(obj).attr('value')
        let allAfterSelectRoom = $(wrapper).nextAll('.static-search_panel__item_wrap')
        $(wrapper).slideUp()
        $(wrapper).remove()

        thisNumber = Number(thisNumber)
        let roomNumTitle
        let deleteRoomTx
        $.each(allAfterSelectRoom, (key, value) => {
            roomNumTitle = thisNumber + '部屋目'
            deleteRoomTx = thisNumber + '部屋目削除'
            $(value).find('.static-search_panel__item_title').text(roomNumTitle)
            $(value).find('.static-search_panel__delete_btn').text(deleteRoomTx)
            $(value).find('.static-search_panel__delete_btn').attr('value', thisNumber)
            thisNumber += 1
        })
        const currentRoomNum = $('#add_search_room').attr('value')
        const updateRoomNum = Number(currentRoomNum) - 1
        const addRoomBtnTx = String(updateRoomNum) + '部屋目追加'
        $('#add_search_room').attr('value', updateRoomNum)
        $('#add_search_room').text(addRoomBtnTx)
        const postCurrentRoomNum = updateRoomNum - 1
        $('input[name="room_num"]').val(postCurrentRoomNum)
    }
});