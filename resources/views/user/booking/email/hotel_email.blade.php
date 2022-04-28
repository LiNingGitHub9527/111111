<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: "游ゴシック体", YuGothic, "游ゴシック", "Yu Gothic", sans-serif;
        }

        span {
            margin: 0 auto;
        }

        .email_inner {
            margin: 0 auto;
            margin-left: 100px;
        }

        .email_inner p {
            margin-top: 0;
            margin-bottom: 0;
        }

        h3, h4 {
            margin: 0 auto;
        }

        .title {
            margin-left: 50px;
        }

        .title span {
            color: #71717a;
        }

        .email_footer {
            margin-left: 20px;
            color: #71717a;
        }

        a {
            color: blue;
        }
    </style>
</head>
<body>
    <div class="email_inner">
        <h3>{{ $emailContent['hotel_name'] }}</h3><br><br>
        <p class="title"><span>tuna</span>から{{ $emailContent['state'] }}【{{ $emailContent['reservation_name'] }}様({{ $emailContent['checkin_time'] }} ~ {{ $emailContent['checkout_time'] }})】のお知らせです。</p><br><br>
        =======================================<br>
        <span>宿泊者氏名：{{ $emailContent['reservation_name'] }}</span><br>
        <span>電話番号：{{ $emailContent['tel'] }}</span><br>
        <span>メールアドレス：{{ $emailContent['reservation_email'] }}</span><br>
        <span>住所：{{ $emailContent['address'] }}</span><br><br>
        =======================================<br>
        <span>予約番号：{{ $emailContent['reservation_code'] }}</span><br>
        <span>チェックイン日：{{ $emailContent['checkin_time'] }}</span><br>
        <span>チェックアウト日：{{ $emailContent['checkout_time'] }}</span><br>
        <span>適応プラン名：{{ $emailContent['plan_name'] }}</span><br>
        <span>予約詳細URL：<a href="{{ $emailContent['url'] }}">{{ $emailContent['url'] }}</a></span><br><br>
        <span>お部屋詳細：</span><br><br>
        @foreach ($emailContent['room_type_detail'] as $roomType)
            <span>{{ $roomType['room_type_name'] }}- &nbsp;(大人：{{ $roomType['adult_num'] }}人,子供：{{ $roomType['child_num'] }}人)</span><br>
            <span>￥{{ number_format($roomType['accommodation_price']) }}</span><br><br>
        @endforeach
        <span>決済方法：{{ $emailContent['payment_method'] }}</span><br>
        <span>決済金額：￥{{ number_format($emailContent['accommodation_price']) }}</span><br>
        <br>
        ==========================================<br><br>
        <div class="email_footer">
            <h3><span>株式会社7garden<br>cs@7garden.co.jo</span></h3>
        </div>
    </div>
</body>
</html>