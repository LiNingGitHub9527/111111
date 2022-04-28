<!DOCTYPE html>
<html lang="ja">
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
    			<p class="header_captured">領収書</p>
    		</div>
    		<div class="mail__detail_info">
    			<div class="mail__detail_info__flex_left">
    				<div class="mail__information__left">
    					<p>予約コード</p>
    					<p>請求日</p>
    					<p>氏名</p>
    					<p>住所</p>
    					<p>メールアドレス</p>
    				</div>
    				<div class="mail__information__right">
    					<p>{{ $data['reservation_code'] }}</p>
    					<p>{{ $data['nowData'] }}</p>
    					<p>{{ $data['reservation_name'] }}</p>
    					<p>{{ $data['reservation_address'] }}</p>
    					<p>{{ $data['reservation_email'] }}</p>
    				</div>
    			</div>
    			<div class="mail__detail_info__flex_right">
    				<div class="detail_address">
    					<p>株式会社7garden</p>
    					<span class="address_info">東京都中央区八重洲2丁目8-7 福岡ビル 4F</span>
    					<p>TEL:<span class="address_info">&nbsp;&nbsp;&nbsp;&nbsp;03-6676-4662</span></p>
    				</div>
    				<div>
    					<img src="{{ asset('static/common/images/lALPDhYBRavnON7NAZDNAZA_400_400.png') }}"  class="image">
    				</div>
    			</div>
    		</div>
	    	<div class="mail__detail_info_block">
	    		<div class="mail_detail_info_block_left">
	    			<div class="mail_accommodation_detail_left">宿泊内容</div>
	    			<div class="mail_accommodation_detail_content">
                        <div class="mail_accommodation_detail_content_left">
                            <div class="mail_title">施設名</div>
                            <div class="mail_content">{{ $data['hotel_name'] }}</div>
                        </div>
                        <div class="mail_accommodation_detail_content_left">
                            <div class="mail_title">期間</div>
                            <div class="mail_content"><?= date('Y/m/d', strtotime($data['checkin_time'])) ?> ~ <?= date('Y/m/d', strtotime($data['checkout_time'])) ?></div>
                        </div>
                        <div class="mail_accommodation_detail_content_left">
                            <div class="mail_title">部屋タイプ</div>
                            <div class="mail_content">
                                @foreach ($roomTypes as $roomType)
                                <span>{{ $roomType['name'] }}</span><br>
                                @endforeach
                            </div>
                        </div>
                        <div class="mail_accommodation_detail_content_left">
                            <div class="mail_title">部屋数</div>
                            <div class="mail_content">{{ $data['room_num'] }}</div>
                        </div>
	    			</div>
    				<div class="mail_content_info_strong">合計金額（税抜）</div>
    				<div class="mail_content_info_strong">消費税額</div>
    				<div class="mail_content_info_strong">クレジットカード請求額（税込）</div>
	    		</div>
	    		<div class="mail__detail_info_block_right">
	    			<div class="mail_accommodation_detail_right">合計金額</div>
    				<div class="mail_content_text"></div>
					<div class="mail_content_info_price">￥{{ number_format($data['maxPrice']) }}</div>
					<div class="mail_content_info_price">
                        @if ($isHotel) 
                            ￥{{number_format(ceil($data['maxPrice'] / 10))}}
                        @else
                            ￥{{ number_format(ceil($data['accommodation_price'] / 10)) }}
                        @endif
                    </div>
					<div class="mail_content_info_price">￥{{ number_format($data['accommodation_price']) }}</div>
	    		</div>
	    	</div>
	    	<div class="mail__detail_info_footer">
	    		<div>
	    			<p>※この領収書は自動的に発行されています</p>
	    		</div>
	    	</div>
    	</div>
    </div>
</body>
</html>

