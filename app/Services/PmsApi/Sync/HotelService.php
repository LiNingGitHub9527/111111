<?php

namespace App\Services\PmsApi\Sync;

use App\Jobs\Pms\HotelDeleteSync;
use App\Jobs\Pms\HotelSync;
use App\Models\Hotel;

class HotelService
{
    private static $instance = null;

    public static function instance(): HotelService
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $instance = new self();
        self::$instance = $instance;
        return $instance;
    }

    public function syncHotel(Hotel $hotel)
    {
        if ($hotel->sync_status != 1) {
            return;
        }
        dispatch(new HotelSync($hotel->id, $hotel->client_id))->onQueue('pms-sync-job');
    }

    public function syncHotelDelete(Hotel $hotel)
    {
        if (empty($hotel->crm_base_id)) {
            return;
        }
        dispatch(new HotelDeleteSync($hotel->id, $hotel->client_id, $hotel->crm_base_id))->onQueue('pms-sync-job');
    }
}
