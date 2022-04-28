<?php

namespace App\Jobs\Pms;

use App\Exceptions\PmsApiReturnException;
use App\Models\Hotel;
use App\Services\ClientLogService;
use App\Support\Api\ApiClient;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HotelSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;

    private $clientId;

    private $tries;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $clientId, $tries = 3)
    {
        $this->id = $id;
        $this->clientId = $clientId;
        $this->tries = $tries;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle(): bool
    {
        $logger = ClientLogService::instance($this->clientId, 'jobs/hotel-sync');
        $logger->info('handle begin: ' . $this->id);
        $isException = false;
        try {
            $hotel = Hotel::find($this->id);
            if (empty($hotel)) {
                $logger->warning("hotel($this->id) hotel not exists");
                return true;
            }
            $client = $hotel->client;
            if (empty($client)) {
                $logger->warning("hotel($this->id) client not exists");
                return true;
            }
            $now = Carbon::now();
            if (empty($client->pms_client_id)) {
                $logger->info("hotel($this->id) send client first...");
                $data = [
                    'company_name' => $client->company_name,
                    'address' => $client->address,
                    'tel' => $client->tel,
                    'nocode_client_id' => $client->id,
                    'person_in_charge' => $client->person_in_charge,
                    'email' => $client->email,
                ];
                $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $data);
                $result = $apiClient->doRequest('client');
                if ($result->code != 200) {
                    throw new PmsApiReturnException("hotel($this->id) send client($client->id) doRequest() return failed", $result);
                }
                $pmsClientId = $result->data->pms_client_id;
                if (empty($pmsClientId) || $pmsClientId == 0) {
                    throw new PmsApiReturnException("hotel($this->id) send client($client->id) doRequest() result data error", $result);
                }
                $client->pms_client_id = $pmsClientId;
                $client->last_sync_time = $now;
                $client->save();
                $logger->info("hotel($this->id) send client($client->id) handle success...");
            }
            $data = [
                'name' => $hotel->name,
                'address' => $hotel->address,
                'phone' => $hotel->tel,
                'avatar' => $hotel->logo_img,
                'nocode_hotel_id' => $hotel->id,
                'client_id' => $client->pms_client_id,
                'business_type' => $hotel->business_type
            ];
            $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $data);
            $result = $apiClient->doRequest('base');
            if ($result->code != 200) {
                throw new PmsApiReturnException("hotel($this->id) doRequest() return failed", $result);
            }
            $crmBaseId = $result->data->pms_base_id;
            if (empty($crmBaseId) || $crmBaseId == 0) {
                throw new PmsApiReturnException("hotel($this->id) doRequest() result data error", $result);
            }
            $hotel->crm_base_id = $crmBaseId;
            $hotel->sync_status = 1;
            $hotel->last_sync_time = $now;
            $hotel->save();
            $logger->info("hotel($this->id) handle end");
        } catch (PmsApiReturnException $e) {
            $logger->info($e->getMessage() . ':' . $e->getObj());
            $isException = true;
        } catch (\Exception $e) {
            $logger->error("hotel($this->id) handle error:" . $e->getMessage());
            $isException = true;
        }
        if ($isException) {
            if ($this->tries > 0) {
                $this->tries--;
                $logger->info("hotel($this->id) handle has exception,retry...");
                dispatch(new HotelSync($this->id, $this->tries))->onQueue('pms-sync-job')->delay($this->getDelay());
            }
        }
        return true;
    }

    private function getDelay(): int
    {
        return [90, 30, 10][$this->tries];
    }
}
