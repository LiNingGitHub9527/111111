<?php

use Illuminate\Database\Seeder;
use App\Models\HotelNote;
use App\Models\Hotel;

class HotelNoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(HotelNote $note)
    {
        if (config('migrate.seeds.skip_hotel_note')) {
            return;
        }
        $insertData = $this->getInsertData();
        foreach ($insertData as $data) {
            $note->insert($data);
        }
    }

    public function getInsertData()
    {
        $hotels = Hotel::get()->toArray();

        $res = [];
        foreach ($hotels as $hotel) {
            $insertData = [
                [
                    'hotel_id' => $hotel['id'],
                    'title' => 'チェックイン方法',
                    'content' => '当ホテルは非対面のセルフチェックインシステムを採用しております。宿泊日前にチェックインとチェックアウトに必要な予約/QRコードをメールにてお送りいたしますので、ご確認ください。ご到着時に、専用端末に予約コードを入力し、表示案内に従って手続きをお願いします。',
                ],
                [
                    'hotel_id' => $hotel['id'],
                    'title' => '禁煙',
                    'content' => '全室禁煙となっております。',
                ],
                [
                    'hotel_id' => $hotel['id'],
                    'title' => '現地決済に関する注意事項',
                    'content' => 'システムの都合上、現地決済の場合、現金利用不可のカード決済のみの対応となっております。',
                ],
            ];
            array_push($res, $insertData);
        }

        return $res;
    }
}
