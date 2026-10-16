<?php

namespace App;

enum Role: string
{
    case ADMIN = 'admin';
    case GURU = 'guru';
    case SISWA = 'siswa';
}
