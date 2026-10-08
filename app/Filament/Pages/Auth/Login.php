<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected function getEmailFormComponent(): TextInput
    {
        return parent::getEmailFormComponent()
            ->extraInputAttributes(['name' => 'email', 'autocomplete' => 'username']);
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return parent::getPasswordFormComponent()
            ->extraInputAttributes(['name' => 'password', 'autocomplete' => 'current-password']);
    }
}
