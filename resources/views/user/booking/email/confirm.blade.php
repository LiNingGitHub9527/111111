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
            <div class="mail__common_attention_tx">
                <p>※このメールは送信専用です。お問い合わせの際は、ご予約の施設へ直接お問い合わせくださいませ。</p>
            </div>
            <div class="mail__base_content">
                <p class="mail__center_tx"><?= $hotel->name ?></p>
                <div class="mail__line_content">
                    <div class="mail__content_info">
                        <p class="mail__content_info__strong">
                            予約番号 <span><?= $code ?></span>
                        </p>
                        <p class="mail__content_info__attention">お問い合わせの際は予約番号をお伝えください。</p>
                    </div>
                </div>
                <div class="mail__content_info">
                    <p class="mail__content_info__strong">
                        宿泊料金／合計（税込） <span>￥ <?= number_format($amount) ?></span>
                    </p>
                    <?php if ($paymentMethod == 0) {  ?>
                        <p class="mail__content_info__attention">こちらの予約は「現地決済」です。 当日、施設にて料金をお支払いください。</p>
                    <?php } else { ?>
                        <p class="mail__content_info__attention">こちらの予約は「事前決済」です。</p>
                    <?php } ?>
                <div>
                <a href="<?= $url ?>">
                    <div class="mail__action_btn">
                        <button type="button">詳細を確認する</button>
                    </div>
                </a>
            </div>
            <div class="mail__detail_info">
                <div class="mail__detail_info_block">
                    <p class="mail__detail_info__title">宿泊施設</p>
                    <div class="mail__detail_info__flex">
                        <p class="mail__detail_info__left"><?= $hotel->name ?></p>
                        <p class="mail__detail_info__right"></p>
                    </div>
                </div>
                <div class="mail__detail_info_block">
                    <p class="mail__detail_info__title">プラン名</p>
                    <div class="mail__detail_info__flex">
                        <p class="mail__detail_info__left"><?= $plan->name ?></p>
                        <p class="mail__detail_info__right"></p>
                    </div>
                </div>
                <div class="mail__detail_info_block">
                    <p class="mail__detail_info__title">人数</p>
                    <?php $i = 1;  ?>
                    <?php foreach ($planRooms as $planRoom) { ?>
                        <p class="mail__detail_info__label"><?= $i ?>部屋目</p>
                        <div class="mail__detail_info__flex">
                            <p class="mail__detail_info__left">
                                大人<?= $planRoom->adult_num ?>名 / 子供<?= $planRoom->child_num ?>名<br>
                                <span class="mail__content_info__attention text-left" style="font-size: 10px;">※お部屋ごとにキッズポリシーを適用できる人数に上限があるため、ご入力された人数と表記が異なる場合がございます。</span>
                            </p>
                            <p class="mail__detail_info__right"></p>
                        </div>
                        <?php $i++; ?>
                    <?php } ?>
                </div>
                <div class="mail__detail_info_block">
                    <p class="mail__detail_info__title">宿泊日</p>
                    <div class="mail__detail_info__flex">
                        <?php if ($stayType == 1) {?>
                            <p class="mail__detail_info__left"><?= date('Y年n月j日', strtotime($checkinDate)) ?><span style="font-size: 10px; color: #767676;">チェックイン</span> <br>
                            <?= date('Y年n月j日', strtotime($checkoutDate)) ?><span style="font-size: 10px; color: #767676;">チェックアウト</span></p>
                        <?php } else {  ?>
                            <p class="mail__detail_info__left"><?= date('Y年n月j日 H:i', strtotime($checkinDate)) ?><span style="font-size: 10px; color: #767676;">チェックイン</span> <br>
                            <?= date('Y年n月j日 H:i', strtotime($checkoutDate)) ?><span style="font-size: 10px; color: #767676;">チェックアウト</span></p>
                        <?php } ?>
                        <p class="mail__detail_info__right"></p>
                    </div>
                </div>
                <div class="mail__detail_info_block">
                    <p class="mail__detail_info__title">客室</p>
                    <?php $i = 1;  ?>
                    <?php foreach ($planRooms as $planRoom) { ?>
                        <p class="mail__detail_info__label"><?= $i ?>部屋目</p>
                        <div class="mail__detail_info__flex">
                            <p class="mail__detail_info__left">
                                <?= $planRoom->name ?>
                            </p>
                            <p class="mail__detail_info__right">￥ <?= number_format($planRoom->amount) ?></p>
                        </div>
                        <?php $i++; ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

