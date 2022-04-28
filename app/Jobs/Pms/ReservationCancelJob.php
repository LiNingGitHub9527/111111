<?php

namespace App\Jobs\Pms;

use Illuminate\Bus\Queueable;
use App\Support\Api\ApiClient;
use App\Services\ClientLogService;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Exceptions\PmsApiReturnException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ReservationCancelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data, $apiClient, $tries,$id;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data, $tries = 3)
    {
        $this->apiClient = new ApiClient(config("signature.gb_signature_api_key"), $data);
        $this->tries = $tries;
        $this->id = $data['id'];
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logger = ClientLogService::instance($this->id, 'jobs/reservation-cancel');
        $logger->info('handle begin: ' . $this->id);
        $result = $this->apiClient->doRequest("save_reservation");
        if ($result->code != 200) {
            throw new PmsApiReturnException("client($this->id) doRequest() return failed", $result);
        }
        $logger->info("reservation cancel handle end");
    }

    private function getDelay(): int
    {
        return [90, 30, 10][$this->tries];
    }
}
