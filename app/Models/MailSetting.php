<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
        ];
    }

    public function isConfigured(): bool
    {
        return filled($this->host)
            && filled($this->port)
            && filled($this->username)
            && filled($this->password)
            && filled($this->from_email);
    }
}
