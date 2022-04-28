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

class ClientDeleteSync implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $id;

    private $pmsClientId;

    private $tries;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($id, $pmsClientId, $tries = 3)
    {
        $this->id = $id;
        $this->pmsClientId = $pmsClientId;
        $this->tries = $tries;
    }

    /**
     * Execute the job.
     *
     * @return bool
     */
    public function handle(): bool
    {
        $logger = ClientLogService::instance($this->id, 'jobs/client-delete');
        $logger->info('handle begin: ' . $this->id);
        $isException = false;
        try {
            $data = [
                'id' => $this->pmsClientId,
            ];
            $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $data);
            $result = $apiClient->doRequest('client/delete');
            if ($result->code != 200) {
                throw new PmsApiReturnException("client($this->id) doRequest() return failed", $result);
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
