<?php

namespace App\Enums;

enum UserType: string
{
    case Viewer = 'viewer';
    case User = 'user';
    case Admin = 'admin';
}
