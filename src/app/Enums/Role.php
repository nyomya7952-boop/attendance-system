<?php

namespace App\Enums;

enum Role: int
{
    case GENERAL_USER = 1;  // 一般ユーザー
    case ADMIN = 2;         // 管理者

    /**
     * ロールのラベルを取得
     */
    public function label(): string
    {
        return match($this) {
            self::GENERAL_USER => '一般ユーザー',
            self::ADMIN => '管理者',
        };
    }

    /**
     * すべてのロールを取得
     */
    public static function all(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}