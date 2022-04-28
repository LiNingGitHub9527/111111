<?php

namespace App\Support\Log\Events;

class ClientLog
{
    public $name = 'ClientLog';
    
    /**
     *
     * @var string
     */
    public $clientId;

    /**
     *
     * @var string
     */
    public $level;

    /**
     *
     * @var string
     */
    public $message;

    /**
     *
     * @var array
     */
    public $context;

    /**
     * Create a new event instance.
     *
     * @param  string  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
     * @param  array  $credentials
     * @return void
     */
    public function __construct($clientId, $level, $message, $context = [])
    {
        $this->clientId = $clientId;
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }
}
