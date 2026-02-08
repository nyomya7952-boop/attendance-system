<?php

namespace App\Enums;

enum AttendanceRequestStatus: string
{
    case SUBMITTED = '承認待ち';
    case APPROVED = '承認済み';
}

