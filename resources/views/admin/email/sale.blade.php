<!DOCTYPE html>
<html lang="ja">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<head>
    <style>
        body {
            font-family: "游ゴシック体", YuGothic, "游ゴシック", "Yu Gothic", sans-serif;
        }

        .main_inner {
            margin: 0 auto;
            width: 1000px;
        }

        .header_captured {
            text-align: right;
            font-weight: bold;
            font-size: 40px;
            margin: 0 auto;
            margin-right: 50px;
        }

        .mail__detail_info {
            position: relative;
        }

        .mail__detail_info__flex_left {
            margin-left: 20px;
            margin-bottom: 30px;
            width: 500px;
            border: 1px solid;
        }

        .mail__information__left {
            width: 200px;
            padding-left: 30px;
            line-height: 20px;
            display: inline-block;
            font-size: 14px;
            letter-spacing: 0.1em;
        }

        .mail__information__right {
            display: inline-block;
            width: 200px;
            line-height: 20px;
            font-size: 14px;
            margin-right: 30px;
            letter-spacing: 0.1em;
        }

        .mail__detail_info__flex_right {
            margin-left: 100px;
            width: 400px;
            position: absolute;
            left: 500px;
            top: 10px;
        }

        .detail_address {
            width: 230px;
            font-weight: bold;
            font-size: 18px;
        }

        .address_info {
            font-size: 14px;
            color: #696969;
        }

        .image {
            position: absolute;
            width: 100px;
            left: 250px;
            top: 20px;
        }

        .mail__detail_info_block {
            border: 1px solid;
            display: flex;
            width: 900px;
            height: 500px;
            margin-left: 30px;
            position: relative;
        }

        .mail_detail_info_block_left {
            width: 540px;
            border-right: 1px solid;
            height: 100%;
        }

        .mail__detail_info_block_right {
            position: absolute;
            width: 359px;
            height: 100%;
            top: 0px;
            left: 541px;
        }

        .mail__detail_info_footer {
            height: 150px;
            margin-left: 30px;
        }

        .mail_accommodation_detail_left {
            height: 6%;
            background-color: #D3D3D3;
            text-align: center;
            color: #000;
            border-bottom: 1px solid;
        }

        .mail_accommodation_detail_right {
            height: 6%;
            background-color: #D3D3D3;
            text-align: center;
            color: #000;
            border-bottom: 1px solid;
        }

        .mail_accommodation_detail_content {
            height: 76%;
        }

        .mail_accommodation_detail_content_left {
            padding-left: 20px;
            padding-top: 30px;
            position: relative;
        }

        .mail_title {
            width: 230px;
        }

        .mail_content {
            position: relative;
            left: 230px;
            bottom: 18px;
        }

        .mail_accommodation_detail_content p {
            margin-top: 30px;
        }

        .mail_accommodation_detail_content_right {
            width: 60%;
        }

        .mail_content_text {
            height: 76%;
        }

        .mail_content_info_strong {
            height: 6%;
            border-top: 1px solid;
            text-align: right;
            padding-right: 30px;
        }

        .mail_content_info_price {
            height: 6%;
            width: 330px;
            text-align: right;
            padding-right: 30px;
        }
    </style>
</head>

<body>
    <div>
        <div class="main_inner">
            <div>
                <p class="header_captured">明細書</p>
            </div>
            <div class="mail__detail_info">
                <div class="mail__detail_info__flex_left">
                    <div class="mail__information__left">
                        <p>会社名</p>
                        <p>ホテル名</p>
                        <p>振込金額</p>
                    </div>
                    <div class="mail__information__right">
                        <p>{{$companyName}}</p>
                        <p>{{$hotelName}}</p>
                        <p>￥{{ number_format($transferAmount)}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>