<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isTeacher(): bool
    {
        return $this === self::Teacher;
    }

    public function isStudent(): bool
    {
        return $this === self::Student;
    }
}
