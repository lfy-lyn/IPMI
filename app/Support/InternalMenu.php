<?php

namespace App\Support;

use App\Models\Member;

/**
 * Menu navigasi aplikasi internal, disusun berdasarkan hak akses.
 *
 * Aturan tampil menu SAMA dengan layout lama (layouts/internal.blade.php):
 *  - menu pengurus pusat (Ketua Umum / Sekretaris): Pendaftaran, Tahun Ajaran,
 *    Kelas, Penetapan Kelas, Kenaikan Kelas;
 *  - menu untuk setiap akun yang tertaut dengan data anggota: Anggota, Kegiatan,
 *    Program Rutin, Materi, Pengumuman.
 *
 * Ini hanya mengatur TAMPILAN menu. Otorisasi sebenarnya tetap dilakukan di
 * controller/policy masing-masing route.
 */
class InternalMenu
{
    /**
     * @return array<int, array{label: ?string, items: array<int, array{label: string, url: string, pattern: string, icon: string}>}>
     */
    public static function groups(?Member $member): array
    {
        $pusat = $member !== null && $member->isPengurusPusat();

        $groups = [
            [
                'label' => null,
                'items' => [
                    self::item('Dashboard', route('dashboard'), 'dashboard', 'grid'),
                ],
            ],
        ];

        $keanggotaan = [];
        if ($pusat) {
            $keanggotaan[] = self::item('Pendaftaran', route('admin.membership.index'), 'admin/pendaftaran*', 'user-plus');
        }
        if ($member) {
            $keanggotaan[] = self::item('Anggota', route('admin.members.index'), 'admin/anggota*', 'users');
        }
        self::push($groups, 'Keanggotaan', $keanggotaan);

        $akademik = [];
        if ($pusat) {
            $akademik[] = self::item('Tahun Ajaran', route('academic-years.index'), 'admin/tahun-ajaran*', 'calendar-range');
            $akademik[] = self::item('Kelas', route('classes.index'), 'admin/kelas*', 'layers');
            $akademik[] = self::item('Penetapan Kelas', route('member-classes.index'), 'admin/penetapan-kelas*', 'check-square');
            $akademik[] = self::item('Kenaikan Kelas', route('class-promotion.index'), 'admin/kenaikan-kelas*', 'trending-up');
        }
        self::push($groups, 'Akademik', $akademik);

        $aktivitas = [];
        if ($member) {
            $aktivitas[] = self::item('Kegiatan', route('activities.index'), 'admin/kegiatan*', 'calendar');
            $aktivitas[] = self::item('Program Rutin', route('programs.index'), 'admin/program*', 'repeat');
        }
        self::push($groups, 'Aktivitas', $aktivitas);

        $informasi = [];
        if ($member) {
            $informasi[] = self::item('Materi', route('materials.index'), 'admin/materi*', 'book-open');
            $informasi[] = self::item('Pengumuman', route('announcements.index'), 'admin/pengumuman*', 'megaphone');
        }
        self::push($groups, 'Informasi', $informasi);

        return $groups;
    }

    /**
     * Item menu yang sedang aktif berdasarkan request saat ini (atau null).
     *
     * @param  array<int, array{label: ?string, items: array<int, array{label: string, url: string, pattern: string, icon: string}>}>  $groups
     * @return array{label: string, url: string, pattern: string, icon: string, group: ?string}|null
     */
    public static function active(array $groups): ?array
    {
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                if (request()->is($item['pattern'])) {
                    return $item + ['group' => $group['label']];
                }
            }
        }

        return null;
    }

    /**
     * Inisial untuk avatar (maks. 2 huruf) dari nama atau email.
     */
    public static function initials(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '?';
        }

        $words = preg_split('/[\s@._-]+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $letters = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $letters .= mb_strtoupper(mb_substr($word, 0, 1));
        }

        return $letters !== '' ? $letters : '?';
    }

    private static function item(string $label, string $url, string $pattern, string $icon): array
    {
        return compact('label', 'url', 'pattern', 'icon');
    }

    private static function push(array &$groups, string $label, array $items): void
    {
        if ($items !== []) {
            $groups[] = ['label' => $label, 'items' => $items];
        }
    }
}
