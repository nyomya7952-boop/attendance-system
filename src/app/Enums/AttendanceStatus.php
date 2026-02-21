<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case OUTSIDE_WORK = '勤務外';
    case CLOCKED_IN = '出勤中';
    case ON_BREAK = '休憩中';
    case CLOCKED_OUT = '退勤済';

    /**
     * ステータスのラベルを取得
     */
    public function label(): string
    {
        return $this->value;
    }

    /**
     * すべてのステータスを取得
     */
    public static function all(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}

