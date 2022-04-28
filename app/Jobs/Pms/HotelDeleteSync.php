<?php

namespace App\Jobs\Pms;

use App\Exceptions\PmsApiReturnException;
use App\Services\ClientLogService;
use App\Support\Api\ApiClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HotelDeleteSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;

    private $clientId;

    private $crmBaseId;

    private $tries;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $clientId, $crmBaseId, $tries = 3)
    {
        $this->id = $id;
        $this->clientId = $clientId;
        $this->crmBaseId = $crmBaseId;
        $this->tries = $tries;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle(): bool
    {
        $logger = ClientLogService::instance($this->clientId, 'jobs/hotel-delete');
        $logger->info('handle begin: ' . $this->id);
        $isException = false;
        try {
            $data = [
                'id' => $this->crmBaseId,
            ];
            $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $data);
            $result = $apiClient->doRequest('base/delete');
            if ($result->code != 200) {
                throw new PmsApiReturnException("hotel($this->id) doRequest() return failed", $result);
            }
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
                dispatch(new ClientDeleteSync($this->id, $this->tries))->onQueue('pms-sync-job')->delay($this->getDelay());
            }
        }
        return true;
    }

    private function getDelay(): int
    {
        return [90, 30, 10][$this->tries];
    }
}
