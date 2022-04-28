jQuery(function($){

    // 予約ボタンをクリックされた際に、検索パネルをajaxでレンダリング する
    RenderSearchPanel = (UrlParam, CSRFToken) => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: '/page/search_panel/render',
                type: 'POST',
                data: {
                    url_param: UrlParam,
                    _token: CSRFToken,
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
});