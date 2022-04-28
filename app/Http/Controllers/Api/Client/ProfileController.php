<?php

namespace App\Http\Controllers\Api\Client;

use App\Http\Requests\Api\Client\ProfileRequest;
use App\Http\Requests\Api\PasswordRequest;
use App\Services\PmsApi\Sync\ClientService;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfileController extends ApiBaseController
{
    public function account(Request $request)
    {
        $user = $this->user();

        $account = [
            'id' => $user->id,
            'name' => $user->person_in_charge,
            'email' => $user->email,
        ];

        $data = [
            'account' => $account,
        ];

        return $this->success($data);
    }

    public function update(ProfileRequest $request)
    {
        $user = $this->user();

        $user->person_in_charge = $request->name;
        $user->save();
        ClientService::instance()->syncClient($user);
        return $this->success();
    }

    public function changepwd(PasswordRequest $request)
    {
        $user = $this->user();
        $oldpwd = $request->oldpwd;
        $newpwd = $request->newpwd;

        $checked = Hash::check($oldpwd, $user->password);
        if (!$checked) {
            return $this->error(['oldpwd' => 'パスワード入力データが正しくありません。'], 1422);
        }

        try {
            \DB::transaction(function () use ($user, $newpwd) {
                $user->initial_password = null;
                $user->password = bcrypt($newpwd);
                $user->save();
            });
        } catch (\Exception $e) {
            Log::info('change password failed :' . $e->getMessage());
            return $this->error();
        }
        return $this->success();

    }
}
