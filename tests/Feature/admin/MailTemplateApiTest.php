<?php

namespace Tests\Feature\admin;

use Tests\TestCase;
use App\Models\MailTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class MailTemplateApiTest extends TestCase
{
    use WithoutMiddleware;
    public function testDetail()
    {
        DB::table('mail_templates')->truncate();
        MailTemplate::create([
            "subject" => "Test Detail Subject",
            "body" => "Test Detail body",
            "bcc" => "TestDetailBcc@exmaple.com",
            "type" => 1
        ]);

        $response = $this->get("/api/admin/mail-template/detail/1");
        $response->assertOK();
        $response->assertJson([
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS',
            'data' => [
                'detail' => [
                    'id' => 1,
                    "subject" => "Test Detail Subject",
                    "body" => "Test Detail body",
                    "bcc" => "TestDetailBcc@exmaple.com",
                    "type" => 1
                ]
            ]
        ]);
        $response->assertJsonStructure([
            'code',
            'status',
            'message',
            'data' => [
                'detail' => [
                    'id',
                    'subject',
                    'body',
                    'bcc',
                    'type'
                ]
            ]
        ]);
        DB::table('mail_templates')->truncate();
    }

    public function testSave()
    {
        DB::table('mail_templates')->truncate();
        $payload = [
            "subject" => "Test Save Subject",
            "body" => "Test Save body",
            "bcc" => "TestSaveBcc@exmaple.com",
            "type" => 1
        ];

        $response = $this->postJson("/api/admin/mail-template/save", $payload);
        $response->assertOK();
        $response->assertJson([
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS'
        ]);

        $this->assertDatabaseHas('mail_templates', [
            "subject" => "Test Save Subject",
            "body" => "Test Save body",
            "bcc" => "TestSaveBcc@exmaple.com",
            "type" => 1
        ]);


        $response = $this->postJson("/api/admin/mail-template/save", [
            "subject" => "Test Another Save Subject",
            "body" => "TestAnother  Save body",
            "bcc" => "TestAnotherSaveBcc@exmaple.com",
            "type" => 1
        ]);
        $response->assertOK();
        $response->assertJson([
            'code' => 200,
            'status' => 'OK',
            'message' => 'SUCCESS'
        ]);

        $this->assertDatabaseHas('mail_templates', [
            "subject" => "Test Another Save Subject",
            "body" => "TestAnother  Save body",
            "bcc" => "TestAnotherSaveBcc@exmaple.com",
            "type" => 1
        ]);

        DB::table('mail_templates')->truncate();
    }
}
