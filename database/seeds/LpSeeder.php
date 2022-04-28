<?php

use Illuminate\Database\Seeder;
use App\Models\Lp;

class LpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Lp $lp)
    {
        if (config('migrate.seeds.skip_lp')) {
            return;
        }

        $insertData = $this->getInsertData();
        $lp->insert($insertData);
    }

    private function getInsertData()
    {
        $insertData = [
            [
                'client_id' => 2,
                'hotel_id' => 1,
                'original_lp_id' => 1,
                'title' => '限定クリスマス企画',
                'cover_image' => 'staging/staff/20200709/202007091932535f06f25583e2f.jpg',
                'form_id' => 1,
                'public_status' => 1,
                'url_param' => 'LnlvLWsuYTExMT',
                'created_at' => now(),
            ],
            [
                'client_id' => 2,
                'hotel_id' => 1,
                'original_lp_id' => 1,
                'title' => '１日限定50%OFFキャンペーン',
                'cover_image' => 'staging/staff/20200709/202007091932535f06f25583e2f.jpg',
                'form_id' => 1,
                'public_status' => 1,
                'url_param' => '9660451080498819',
                'created_at' => now(),
            ],
        ];
        return $insertData;
    }
}
