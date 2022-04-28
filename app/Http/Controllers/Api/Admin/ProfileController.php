<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Requests\Api\Admin\ProfileRequest;
use App\Http\Requests\Api\PasswordRequest;
use Hash;

class ProfileController extends ApiBaseController
{
    public function account(Request $request)
    {
        $user = $this->user();

        $account = [
            'id' => $user->id,
            'name' => $user->name,
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

        $user->name = $request->name;
        $user->save();

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

        $user->password = bcrypt($newpwd);
        $user->save();
        return $this->success();
    }
}
