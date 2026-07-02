<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a User model into the mobile API user object.
 *
 * Included in login success response under data.user.
 * Excludes sensitive fields (password, otp_code, remember_token).
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone_no' => $this->phone_no,
            'profile_picture' => $this->profile_picture
                ? asset('storage/profile_pictures/'.$this->profile_picture)
                : null,
            'status' => (int) $this->status, // 1 = active, 0 = inactive
            'roles' => $this->getRoleNames()->values()->all(),
        ];
    }
}
