<?php

class AppUserIdHelper
{
    public static function generate(): string
    {
        $letters = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 3));
        $numbers = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return $letters . '-' . $numbers;
    }

    public static function generateUnique($db): string
    {
        do {
            $id = self::generate();
            $stmt = $db->prepare(
                "SELECT 1 FROM users WHERE app_user_id = ? LIMIT 1"
            );
            $stmt->execute([$id]);
        } while ($stmt->fetch());

        return $id;
    }
}
