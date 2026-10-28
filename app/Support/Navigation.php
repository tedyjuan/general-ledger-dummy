<?php

namespace App\Support;

class Navigation
{
    public const DEFAULT_ACTIONS = ['read', 'create', 'update', 'delete'];

    /** Semua item ber-key: dipakai halaman checklist & seeder */
    public static function permissionables(): array
    {
        $out = [];

        foreach (config('navigation.menu') as $item) {
            foreach ($item['children'] ?? [$item] as $leaf) {
                if (! isset($leaf['key'])) {
                    continue;
                }
                $out[$leaf['key']] = [
                    'label'   => $leaf['label'],
                    'actions' => $leaf['actions'] ?? self::DEFAULT_ACTIONS,
                ];
            }
        }

        return $out;
    }

    /** Menu yang boleh dilihat user (untuk sidebar) */
    public static function forUser($user): array
    {
        $result = [];

        foreach (config('navigation.menu') as $item) {
            if (isset($item['children'])) {
                $children = array_values(array_filter(
                    $item['children'],
                    fn($child) => self::allowed($user, $child)
                ));

                if ($children) {
                    $item['children'] = $children;
                    $result[] = $item;
                }
            } elseif (self::allowed($user, $item)) {
                $result[] = $item;
            }
        }

        return $result;
    }

    private static function allowed($user, array $item): bool
    {
        if (! $user || ! isset($item['key'])) {
            return false;
        }

        // Menu tampil jika user punya aksi pertama (biasanya "read")
        $action = ($item['actions'] ?? self::DEFAULT_ACTIONS)[0];

        return $user->can($item['key'] . ',' . $action);
    }
}
