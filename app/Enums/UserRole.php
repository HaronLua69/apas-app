<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Adviser = 'adviser';
    case Student = 'student';
}