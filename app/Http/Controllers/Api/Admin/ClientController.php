<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\ClientRequest;
use App\Models\Client;
use App\Services\MailService;
use App\Services\PmsApi\Sync\ClientService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClientController extends ApiBaseController
{
    public function list(Request $request)
    {
        $query = Client::query();
        $clientlId = $request->get('client_id');
        if (!empty($clientlId)) {
            $query->where('id', $clientlId);
        }

        $list = $query->orderBy('id', 'DESC')->paginate(20);

        $records = [];
        foreach ($list as $client) {
            $row = [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'hotel_num' => $client->hotel_num,
                'tel' => $client->tel,
                'email' => $client->email,
                'initial_password' => $client->initial_password,
                'person_in_charge' => $client->person_in_charge,
            ];
            $records[] = $row;
        }
        $data = [
            'records' => $records,
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pages' => $list->lastPage(),
        ];
        return $this->success($data);
    }

    public function detail($id)
    {
        $client = Client::find($id);
        if (!empty($client)) {
            $detail = [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'address' => $client->address,
                'tel' => $client->tel,
                'person_in_charge' => $client->person_in_charge,
                'email' => $client->email,
                'sync_status' => $client->sync_status,
            ];

            $data = [
                'detail' => $detail,
            ];
            return $this->success($data);
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function update(ClientRequest $request)
    {
        $id = $request->get('id');
        $client = Client::find($id);
        if (!empty($client)) {
            $data = $request->all();
            $client->fill($data);
            $client->save();
            ClientService::instance()->syncClient($client);
            return $this->success();
        } else {
            return $this->error('データが存在しません', 404);
        }
    }

    public function create(ClientRequest $request)
    {
        $client = new Client;
        $data = $request->all();
        $initialPassword = Str::random(8);
        $data['initial_password'] = $initialPassword;
        $data['password'] = bcrypt($initialPassword);
        $client->fill($data);
        $client->save();
        ClientService::instance()->syncClient($client);
        return $this->success();
    }

    public function delete($id)
    {
        $client = Client::find($id);
        if (empty($client)) {
            return $this->error('データが存在しません', 404);
        }

        $client->email = $client->email . '::DELETED';
        $client->save();
        try {
            \DB::transaction(function () use ($client) {
                $client->delete();
            });
        } catch (\Exception $e) {
            Log::info('delete failed :' . $e);
            return $this->error('delete failed', 500);
        }
        ClientService::instance()->syncClientDelete($client);
        return $this->success();
    }

    public function search(Request $request)
    {
        $records = [];
        $q = $request->get('q');
        if (!empty($q)) {
            $list = Client::where('company_name', 'like', '%' . $q . '%')->limit(10)->get();
            if (!empty($list)) {
                foreach ($list as $item) {
                    $records[] = [
                        'id' => $item->id,
                        'title' => $item->company_name,
                        'description' => $item->address,
                    ];
                }
            }
        }
        $data = [
            'records' => $records,
        ];
        return $this->success($data);
    }


    public function send_email($id)
    {
        $client = Client::find($id);
        if (!empty($client)) {
            $from = config('mail.from.address');
            $to = $client->email;
            $subject = '初期パスワード';
            $content = $client->initial_password;
            MailService::instance()->send($from, $to, $subject, $content);
            return $this->success();
        } else {
            return $this->error('send message error', 404);
        }
    }

}
