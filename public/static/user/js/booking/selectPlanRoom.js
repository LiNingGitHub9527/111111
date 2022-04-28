jQuery(function($){

    // プラン選択した際に、プランの詳細htmlを返す
    RenderPlanDetail = (planToken, CsrfToken, url) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    plan_token: planToken,
                    _token: CsrfToken,
                },
                dataType: 'json',
            }).then(
                (data) => {
                  resolve(data);
                },
                () => {
                  reject();
                }
            )
        });
    }

    // プランを選択した際に、プランに紐づく部屋タイプをレンダリングし、プランを選択済みの状態にする
    RenderPlanRoom = (planToken, CsrfToken, url) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    plan_token: planToken,
                    _token: CsrfToken,
                }
            }).then(
                (data) => {
                  resolve(data);
                },
                () => {
                  reject();
                }
            )
        });
    }

    // 部屋タイプ名をクリックした際に、部屋タイプの詳細のhtmlをレンダリング して返す
    RenderRoomDetail = (roomToken, CsrfToken, url) => {
        console.log(roomToken)
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    room_token: roomToken,
                    _token: CsrfToken,
                }
            }).then(
                (data) => {
                  resolve(data);
                },
                () => {
                  reject();
                }
            )
        });
    }

    // 部屋タイプが選択された際に、部屋タイプの選択済みのhtmlをレンダリング して返す
    RenderSelectedRoom = (roomToken, CsrfToken, url) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    room_token: roomToken,
                    _token: CsrfToken,
                }
            }).then(
                (data) => {
                  resolve(data);
                },
                () => {
                  reject();
                }
            )
        });
    }

    // 部屋選択の取り消しがクリックされた際に、セッションからその部屋を削除する
    CancelSelectedRoom = (roomToken, roomNum, CsrfToken, url) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    room_token: roomToken,
                    room_num: roomNum,
                    _token: CsrfToken,
                }
            }).then(
                (data) => {
                  resolve(data);
                },
                () => {
                  reject();
                }
            )
        });
    }

    // 予約確認ページでキャンセルボタンがクリックされた際に、キャンセルポリシーを確認する
    // 表示するポップアップのhtmlを返す
    CancelPromise = (CsrfToken, url) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: CsrfToken,
                }
            }).then(
                (data) => {
                  resolve(data);
                },
                () => {
                  reject();
                }
            )
        });
    }
});