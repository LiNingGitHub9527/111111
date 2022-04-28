<?php

namespace App\Services\PmsApi\Sync;

use App\Jobs\Pms\ClientDeleteSync;
use App\Jobs\Pms\ClientSync;
use App\Models\Client;

class ClientService
{
    private static $instance = null;

    public static function instance(): ClientService
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $instance = new self();
        self::$instance = $instance;
        return $instance;
    }

    public function syncClient(Client $client)
    {
        if ($client->sync_status != 1) {
            return;
        }
        dispatch(new ClientSync($client->id))->onQueue('pms-sync-job');
    }

    public function syncClientDelete(Client $client)
    {
        if (empty($client->pms_client_id)) {
            return;
        }
        dispatch(new ClientDeleteSync($client->id, $client->pms_client_id))->onQueue('pms-sync-job');
    }

}
