<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Hotel;
use App\Jobs\Mail\SaleMail;
use App\Models\MailTemplate;
use Illuminate\Http\Request;
use App\Services\ExcelService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\PmsApi\Sync\HotelService;
use App\Http\Requests\Api\Admin\MailTemplateRequest;
use App\Jobs\Mail\BulkSaleMail;

class SaleController extends Controller
{
    public function list(Request $request)
    {
        list($monthDayStart, $monthDayEnd) = getMonthDayStart($request->get('month'));
        list($start, $end) = getFormatedStartEndDatesForSaleMail($monthDayStart, $monthDayEnd);

        $hotels = app()->make('CommonHotelService')->getHotelsWithReservationWithDateRange($start, $end)->paginate(20);
        $data = [];

        foreach ($hotels as $hotel) {
            $data[] = [
                "hotel_id" => $hotel->id,
                "hotel_name" => $hotel->name,
                "sale_price" => $hotel->total_sale_price,
                "commission_price" => $hotel->total_accommodation_commission,
                "payment_commission_price" => $hotel->total_payment_commission,
                "transfer_amount" => $hotel->transfer_amount
            ];
        }

        return $this->success([
            'records' => $data,
            'total' => $hotels->total(),
            'page' => $hotels->currentPage(),
            'pages' => $hotels->lastPage(),
            'last_month' => Carbon::parse($monthDayStart)->subMonth()->format("Y-m-d"),
            'next_month' => $monthDayStart->isCurrentMonth() ? null : Carbon::parse($monthDayStart)->addMonth()->format("Y-m-d"),
            'month_start' => $monthDayStart->format('Y年m月d日'),
            'month_end' => $monthDayEnd->format('Y年m月d日')
        ]);
    }

    public function csvDownload(Request $request)
    {
        list($monthDayStart, $monthDayEnd) = getMonthDayStart($request->get('month'));
        list($start, $end) = getFormatedStartEndDatesForSaleMail($monthDayStart, $monthDayEnd);
        $shortMonth = $monthDayStart->format("Ym");

        $hotels = app()->make('CommonHotelService')->getHotelsWithReservationWithDateRange($start, $end)->get();

        $fileName = rawurlencode('振込一覧_' . $shortMonth.'.csv');
        $headers = getDownloadFileResponseHeader($fileName, 'text/csv');


        $callback = function() use($hotels) {
            $file = fopen('php://output', 'w');
            foreach ($hotels as $hotel) {
                fputcsv($file, [
                    1,
                    $hotel->bank_code,
                    $hotel->branch_code,
                    $hotel->deposit_type,
                    $hotel->account_number,
                    mb_convert_encoding($hotel->recipient_name, "SJIS", "UTF8"),
                    $hotel->transfer_amount,
                    ""
                ]);
            }
    
            fputcsv($file, [
                2,
                "",
                "",
                "",
                "",
                $hotels->count(),
                $hotels->sum('transfer_amount'),
                ""
            ]);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function sendMail(MailTemplateRequest $request, $id) {
        list($monthDayStart, $monthDayEnd) = getMonthDayStart($request->get('month'));
        list($start, $end) = getFormatedStartEndDatesForSaleMail($monthDayStart, $monthDayEnd);

        $exists = app()->make('CommonHotelService')->getHotelsWithReservationWithDateRange($start, $end)->where('id', $id)->exists();
        if(!$exists) {
            return $this->error('データが存在しません', 404);
        }
        dispatch(new SaleMail($id, $request->get('month'), $request->subject, $request->body, $request->bcc, getSaleMailAttachmentName($monthDayStart)))->onQueue('mail-job');
        return $this->success();
    }

    public function bulkSend(MailTemplateRequest $request) {
        dispatch(new BulkSaleMail($request->get('month'), $request->subject, $request->body, $request->bcc))
            ->onConnection('database')
            ->onQueue('mail-job');
        return $this->success();
    }

    public function pdfDownload(Request $request, $id) {
        list($monthDayStart, $monthDayEnd) = getMonthDayStart($request->get('month'));
        list($start, $end) = getFormatedStartEndDatesForSaleMail($monthDayStart, $monthDayEnd);

        $hotel = app()->make('CommonHotelService')->getHotelsWithReservationWithDateRange($start, $end)->where('id', $id)->first();
        if(empty($hotel)) {
            return $this->error('データが存在しません', 404);
        }
        $hotelService = app()->make('CommonHotelService');
        $pdf = $hotelService->getSalePdf($hotel->client->company_name, $hotel->name, $hotel->transfer_amount);
        $fileName = rawurlencode(getSaleMailDownloadName($hotel, $monthDayStart));
        return response(base64_encode($pdf->output()), 200, getDownloadFileResponseHeader($fileName, 'application/pdf'));
    }
}
