<?php

namespace App\Http\Controllers\Temairazu;

use Illuminate\Routing\Controller;
use App\Support\Temairazu\Http\Responese;
use App\Http\Requests\Temairazu\AuthRequest;
use Carbon\Carbon;
use App\Services\ScEndPoint\Temairazu\TemairazuService;

class TemairazuController extends Controller
{
    //ログイン認証
    public function tema000(AuthRequest $request)
    {
        $hotel = $request->hotel();
        return $this->success();
    }

    public function notification($cid, $rid)
    {
        if (!app()->isProduction()) {
            TemairazuService::instance()->sendReservationNotification($cid, $rid);
            echo 'sent';
        }
        exit;
    }

    //部屋情報取得
    public function tema005(AuthRequest $request)
    {
        $hotel = $request->hotel();

        try {
            $data = TemairazuService::instance()->getRooms($hotel);

            return $this->csv($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }        
    }

    //プラン情報取得
    public function tema010(AuthRequest $request)
    {
        $hotel = $request->hotel();
        $room = $request->room($hotel->id);

        try {
            $data = TemairazuService::instance()->getPlans($hotel, $room);

            return $this->csv($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    //在庫登録
    public function tema030(AuthRequest $request)
    {
        $hotel = $request->hotel();
        $room = $request->room($hotel->id);

        try {
            $data = TemairazuService::instance()->updateStocks($hotel, $room, $request->all());

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    //料金登録
    public function tema036(AuthRequest $request)
    {
        $hotel = $request->hotel();
        $room = $request->room($hotel->id);
        $plan = $request->plan($hotel->id);

        try {
            $data = TemairazuService::instance()->updatePlanRates($hotel, $room, $plan, $request->all());

            return $this->success();
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    //在庫取得
    public function tema130(AuthRequest $request)
    {
        $hotel = $request->hotel();
        $room = $request->room($hotel->id);

        $start = $request->get('DayStart');
        $end = $request->get('DayEnd');
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $today = Carbon::today();

        //取得対象開始日<=取得対象終了日
        if ($startDate->gt($endDate)) {
            return $this->error('取得対象開始日<=取得対象終了日');
        }
        //指定された期間が月をまたぐ場合はエラーメッセージを表示する
        if ($startDate->month != $endDate->month) {
            return $this->error('月を跨いでリクエストされています、ご確認ください');
        }
        //最大期間は当日から 1 年間
        if ($startDate->lt($today->subYear())) {
            return $this->error('最大期間は当日から 1 年間');
        }

        try {
            $data = TemairazuService::instance()->getStocks($hotel, $room, $startDate, $endDate);

            return $this->csv($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    //料金取得
    public function tema135(AuthRequest $request)
    {
        $hotel = $request->hotel();
        $room = $request->room($hotel->id);
        $plan = $request->plan($hotel->id);

        $start = $request->get('DayStart');
        $end = $request->get('DayEnd');
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $today = Carbon::today();

        //取得対象開始日<=取得対象終了日
        if ($startDate->gt($endDate)) {
            return $this->error('取得対象開始日<=取得対象終了日');
        }
        //指定された期間が月をまたぐ場合はエラーメッセージを表示する
        if ($startDate->month != $endDate->month) {
            return $this->error('月を跨いでリクエストされています、ご確認ください');
        }
        //最大期間は当日から 1 年間
        if ($startDate->lt($today->subYear())) {
            return $this->error('最大期間は当日から 1 年間');
        }

        try {
            $data = TemairazuService::instance()->getPlanRates($hotel, $room, $plan, $startDate, $endDate);

            return $this->csv($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    //予約情報取得
    public function tema201(AuthRequest $request)
    {
        $hotel = $request->hotel();
        $filterType = $request->get('Shubetsu');  //取得種別 0:予約番号 1:宿泊日 2:受付日
        $params = [];
        if ($filterType == 0) {
            $params['reservationCode'] = $request->get('BookingID');
        } elseif ($filterType == 1 || $filterType == 2) {
            $start = $request->get('DayStart');
            $end = $request->get('DayEnd');
            $params['startDate'] = Carbon::parse($start);
            $params['endDate']= Carbon::parse($end);
        } else {
            return $this->error('取得種別エラー');
        }

        try {
            $data = TemairazuService::instance()->getReservations($hotel, $filterType, $params);

            return $this->csv($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function success()
    {
        return Responese::success();
    }

    public function error($message = '')
    {
        return Responese::error($message);
    }

    public function csv($data)
    {
        return Responese::create($data);
    }
}
