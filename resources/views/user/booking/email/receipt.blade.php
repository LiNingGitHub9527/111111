<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .mail_inner {
            max-width: 600px;
            margin: 0 auto;
        }

        .mail__common_attention_tx {
            background: #F9FBFE;
            text-align: left;
            padding: 30px 10%;
            max-width: 600px;
            width: 60%;
            margin: 0 auto;
        }

        .mail__common_attention_tx p {
            color: #000;
            font-size: 12px;
            line-height: 18px;
            letter-spacing: 0.1em;
        }

        .mail__base_content {
            text-align: center;
            margin: 40px auto;
            max-width: 600px;
            width: 60%;
        }

        .mail__content_tx {
            padding: 20px 0 0;
            word-break: break-word;
            font-size: 14px;
            letter-spacing: 0.1em;
        }

        .mail__line_content {
            border-top: 1px solid #E0E0E0;
            border-bottom: 1px solid #E0E0E0;
            margin-bottom: 40px;
        }

        .mail__line_content {
            
        }

        .mail__content_info {
            padding: 20px 0;
        }

        .mail__content_info__strong {
            color: #000;
            letter-spacing: 0.1em;
            font-size: 14px;
        }

        .mail__content_info__strong span {
            font-size: 18px;
        }

        .mail__content_info__attention {
            font-size: 12px;
            letter-spacing: 0.1em;
            color: #2185D0;
        }

        .mail__action_btn {
            margin: 40px 0;
        }

        .mail__action_btn button {
            background: #2185D0;
            border-radius: 3px;
            color: #FFF;
            border: transparent;
            font-size: 14px;
            padding: 10px 30px;
            width: 100%;
            max-width: 600px;
        }

        .mail__detauk_info {
            margin: 40px 0;
        }

        .mail__detail_info_block {
            margin: 15px 0;
            text-align: left;
            border-bottom: 1px dotted #E0E0E0;
        }

        .mail__detail_info_title{
            font-size: 18px;
            letter-spacing: 0.1em;
            font-weight: 700;
        }

        .mail__detail_info__flex {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .mail__detail_info__left {
            font-size: 14px;
            letter-spacing: 0.1em;
            margin-right: auto;
        }

        .mail__detail_info__right {
            text-align: right;
            font-size: 14px;
            letter-spacing: 0.1em;
            margin-left: auto;
        }

        .text-left {
            text-align: left;
        }

        .mail__detail_info__label {
            background: #E0E1E2;
            color: #767676;
            padding: 5px;
            display: inline-block;
        }

        @media only screen and (max-width: 500px) {
            .mail_inner {
                width: 90%;
            } 

            .mail__base_content {
                width: 100%!important;
            }
        }
    </style>
</head>
<body>
    <div class="mail_wrapper">
        <div class="main_inner">
            {{ $reservationName }}さま<br><br>
            <div class="mail__common_attention_tx">
                <p>※このメールは送信専用です。お問い合わせの際は、ご予約の施設へ直接お問い合わせくださいませ。</p>
            </div>
            <div class="mail__base_content">
                <p class="mail__center_tx">{{ $hotelName }}</p>
                <div class="mail__line_content">
                    <div class="mail__content_info">
                        <p class="mail__content_info__strong">
                            この度は【 {{ $hotelName }} 】へご滞在いただきまして、誠にありがとうございました。<br>
                            今回のご滞在の領収書を本メールにてお送りいたします。<br><br>

                            領収書に関してご不明点等ございましたら、<br>
                            お手数をおかけいたしますが、以下までご連絡ください。<br>
                            ——<br>
                            tuna カスタマーサクセスチーム
                        </p>
                    </div>
                </div>
                <div class="mail__content_info">
                    <p class="mail__content_info__strong">
                        経理担当<br>
                        mail：cs@7garden.co.jp<br>
                        ——<br><br>
                        またのご滞在を心よりお待ちしております。
                    </p>
                <div>
            </div>
        </div>
    </div>
</body>
</html>

