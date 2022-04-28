<?php

namespace App\Jobs\Pms;

use App\Exceptions\PmsApiReturnException;
use App\Models\Client;
use App\Models\Hotel;
use App\Services\ClientLogService;
use App\Support\Api\ApiClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ClientSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;

    private $tries;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $tries = 3)
    {
        $this->id = $id;
        $this->tries = $tries;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle(): bool
    {
        $logger = ClientLogService::instance($this->id, 'jobs/client-sync');
        $logger->info('handle begin: ' . $this->id);
        $isException = false;
        try {
            $client = Client::find($this->id);
            if (empty($client)) {
                $logger->warning("client($this->id) client not exists");
                return true;
            }
            $data = [
                'company_name' => $client->company_name,
                'address' => $client->address,
                'tel' => $client->tel,
                'nocode_client_id' => $client->id,
                'person_in_charge' => $client->person_in_charge,
                'email' => $client->email,
            ];
            $hotelList = Hotel::where('client_id', $client->id)->whereNull('crm_base_id')->get();
            if (!empty($hotelList)) {
                $hotels = [];
                foreach ($hotelList as $hotel) {
                    $item = [
                        'name' => $hotel->name,
                        'address' => $hotel->address,
                        'phone' => $hotel->tel,
                        'avatar' => $hotel->logo_img,
                        'nocode_hotel_id' => $hotel->id,
                        'business_type' => $hotel->business_type
                    ];
                    $hotels[] = $item;
                }
                $data['hotels'] = $hotels;
            }
            $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $data);
            $result = $apiClient->doRequest('client');
            if ($result->code != 200) {
                throw new PmsApiReturnException("client($this->id) doRequest() return failed", $result);
            }
            $pmsClientId = $result->data->pms_client_id;
            if (empty($pmsClientId) || $pmsClientId == 0) {
                throw new PmsApiReturnException("client($this->id) doRequest() result data error", $result);
            }
            $now = Carbon::now();
            $client->pms_client_id = $pmsClientId;
            $client->last_sync_time = $now;
            $client->save();
            $pmsBaseIds = $result->data->pms_base_ids;
            foreach ($pmsBaseIds as $pmsBaseId) {
                $hotelId = $pmsBaseId->hotel_id;
                $pmsBaseId = $pmsBaseId->pms_base_id;
                $hotel = Hotel::find($hotelId);
                $hotel->crm_base_id = $pmsBaseId;
                $hotel->sync_status = 1;
                $hotel->last_sync_time = $now;
                $hotel->save();
            }
            $logger->info("client($this->id) handle end");
        } catch (PmsApiReturnException $e) {
            $logger->info($e->getMessage() . ':' . $e->getObj());
            $isException = true;
        } catch (\Exception $e) {
            $logger->error("client($this->id) handle error:" . $e->getMessage());
            $isException = true;
        }
        if ($isException) {
            if ($this->tries > 0) {
                $this->tries--;
                $logger->info("client($this->id) handle has exception,retry...");
                dispatch(new ClientSync($this->id, $this->tries))->onQueue('pms-sync-job')->delay($this->getDelay());
            }
        }
        return true;
    }

    private function getDelay(): int
    {
        return [90, 30, 10][$this->tries];
    }
}
