<?php

namespace App\Http\Controllers;

abstract class BaseAppController extends Controller
{
    public ?bool $is_siswa {
        get => $this->cekRole('siswa');
    }

    public ?bool $is_guru {
        get => $this->cekRole('guru');
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function cekRole(string $role)
    {

        return $this->user->role == $role;

    }
}
