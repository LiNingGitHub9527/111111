<?php

namespace Tests\Feature\mail;

use Tests\TestCase;
use App\Models\Hotel;
use App\Models\Client;
use App\Jobs\Mail\SaleMail;
use App\Models\MailTemplate;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaleMailTest extends TestCase
{
    public function testJob()
    {
        $client = factory(Client::class)->create([
            'email' => '' // insert your mail for testing
        ]);
        $hotel = factory(Hotel::class)->create([
            'client_id' => $client->id,
            'bank_code' => '1231231313',
            'branch_code' => '123123123',
            'deposit_type' => 1,
            'account_number' => '123123123',
            'recipient_name' => 'asdf'
        ]);

        $reservation = factory(Reservation::class)->create([
            'client_id' => $client->id,
            'hotel_id' => $hotel->id,
            'checkin_start' => '2021-12-08 01:00:00'
        ]);
        $mailTemplate = MailTemplate::create([
            "body" => "Hello,【担当者名】\n【ホテル名】\n【施設名】",
            "subject" => "Test email",
            "bcc" => '', // insert your mail for testing
            "type" => 1
        ]);
        $job = new SaleMail($hotel->id, '2021-12-08',  $mailTemplate->subject, $mailTemplate->body, $mailTemplate->bcc);
        $result = $job->handle();
        $this->assertTrue($result);

        $job = new SaleMail($hotel->id, '2021-12-08', 'Something Other than that. 【担当者名】', 'Some Subject', '');
        $result = $job->handle();
        $this->assertTrue($result);
    }
}
