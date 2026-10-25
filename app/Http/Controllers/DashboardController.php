<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\IncidentalActivity;
use App\Models\Member;
use App\Models\MembershipApplication;
use App\Models\Program;
use App\Models\ProgramSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Dashboard internal. Hanya menyajikan data yang BENAR-BENAR ada di database
 * dan memakai aturan visibilitas yang sama dengan halaman daftar/detail terkait:
 *  - kegiatan   : sama dengan IncidentalActivity::canBeAccessedBy()
 *  - program    : sama dengan Program::canBeViewedBy() (hanya program aktif)
 *  - pengumuman : sama dengan Announcement::canBeAccessedBy() (hanya yang sudah terbit)
 *  - pendaftaran: hanya Ketua Umum / Sekretaris
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $member = $request->user()->member;

        if (! $member) {
            return view('dashboard', ['member' => null]);
        }

        $member->loadMissing(['division', 'positions']);

        $isPusat = $member->isPengurusPusat();
        $isKadiv = $member->isKetuaDivisi();
        $today = Carbon::today();

        // --- Anggota aktif -------------------------------------------------
        // Ketua Divisi hanya melihat anggota divisinya (sama dengan daftar anggota).
        $memberCount = Member::where('status', 'active')
            ->when(! $isPusat && $isKadiv, fn ($q) => $q->where('division_id', $member->division_id))
            ->count();

        // --- Kegiatan mendatang / sedang berjalan --------------------------
        $activityQuery = IncidentalActivity::query()
            ->where(function ($q) use ($today) {
                $q->where('status', 'ongoing')
                    ->orWhere(function ($w) use ($today) {
                        $w->where('status', 'upcoming')
                            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$today->toDateString()]);
                    });
            });

        if (! $isPusat) {
            if ($isKadiv) {
                // Mengikuti IncidentalActivity::canBeAccessedBy() untuk Ketua Divisi.
                $activityQuery->where('division_id', $member->division_id);
            } else {
                $activityQuery->where(fn ($q) => $q->whereNull('division_id')
                    ->orWhere('division_id', $member->division_id));
            }
        }

        $activityCount = (clone $activityQuery)->count();
        $activities = (clone $activityQuery)->with('division')
            ->orderBy('start_date')->limit(5)->get();

        // --- Program rutin aktif & sesi mendatang --------------------------
        $programScope = fn ($q) => $q->where('status', 'active')
            ->when(! $isPusat, fn ($w) => $w->where(fn ($d) => $d->whereNull('division_id')
                ->orWhere('division_id', $member->division_id)));

        $programCount = Program::query()->tap($programScope)->count();

        $sessions = ProgramSession::query()
            ->whereDate('session_date', '>=', $today)
            ->whereHas('program', $programScope)
            ->with('program.division')
            ->orderBy('session_date')->orderBy('start_time')
            ->limit(5)->get();

        // --- Pengumuman terbit ---------------------------------------------
        $announcementQuery = Announcement::query()->published();
        if (! $isPusat) {
            $announcementQuery->where(function ($q) use ($member, $isKadiv) {
                $q->where('target_type', 'all')
                    ->orWhere(fn ($w) => $w->where('target_type', 'division')
                        ->where('division_id', $member->division_id));
                if ($isKadiv) {
                    $q->orWhere('target_type', 'pengurus');
                }
            });
        }
        $announcementCount = (clone $announcementQuery)->count();
        $announcements = (clone $announcementQuery)->with('division')
            ->orderByDesc('published_at')->limit(4)->get();

        // --- Pendaftaran menunggu (hanya pengurus pusat) --------------------
        $pendingCount = 0;
        $pendingApplications = collect();
        if ($isPusat) {
            $pendingQuery = MembershipApplication::where('status', 'pending');
            $pendingCount = (clone $pendingQuery)->count();
            $pendingApplications = (clone $pendingQuery)->with('division')
                ->orderBy('created_at')->limit(5)->get();
        }

        // --- Kartu statistik -----------------------------------------------
        $stats = [
            [
                'label' => 'Anggota aktif',
                'value' => $memberCount,
                'hint' => (! $isPusat && $isKadiv) ? 'Divisi '.($member->division?->name ?? '-') : 'Seluruh IPMI',
                'icon' => 'users',
                'url' => route('admin.members.index'),
                'tone' => 'teal',
            ],
            [
                'label' => 'Kegiatan mendatang',
                'value' => $activityCount,
                'hint' => 'Akan datang atau sedang berjalan',
                'icon' => 'calendar',
                'url' => route('activities.index'),
                'tone' => 'teal',
            ],
            [
                'label' => 'Program rutin aktif',
                'value' => $programCount,
                'hint' => 'Program yang sedang berjalan',
                'icon' => 'repeat',
                'url' => route('programs.index'),
                'tone' => 'teal',
            ],
        ];

        $stats[] = $isPusat
            ? [
                'label' => 'Pendaftaran menunggu',
                'value' => $pendingCount,
                'hint' => $pendingCount > 0 ? 'Perlu ditinjau' : 'Tidak ada antrean',
                'icon' => 'user-plus',
                'url' => route('admin.membership.index', ['status' => 'pending']),
                'tone' => $pendingCount > 0 ? 'amber' : 'teal',
            ]
            : [
                'label' => 'Pengumuman terbit',
                'value' => $announcementCount,
                'hint' => 'Untuk Anda',
                'icon' => 'megaphone',
                'url' => route('announcements.index'),
                'tone' => 'teal',
            ];

        // --- Pintasan sesuai izin (semua route sudah ada) --------------------
        $shortcuts = [];
        if ($isPusat) {
            $shortcuts[] = ['label' => 'Tinjau pendaftaran', 'icon' => 'user-plus', 'url' => route('admin.membership.index', ['status' => 'pending'])];
        }
        if ($isPusat || $isKadiv) {
            $shortcuts[] = ['label' => 'Tambah kegiatan', 'icon' => 'calendar', 'url' => route('activities.create')];
            $shortcuts[] = ['label' => 'Tambah program rutin', 'icon' => 'repeat', 'url' => route('programs.create')];
        }
        if ($isPusat) {
            $shortcuts[] = ['label' => 'Buat pengumuman', 'icon' => 'megaphone', 'url' => route('announcements.create')];
        }
        $shortcuts[] = ['label' => 'Lihat materi divisi', 'icon' => 'book-open', 'url' => route('materials.index')];

        return view('dashboard', [
            'member' => $member,
            'isPusat' => $isPusat,
            'stats' => $stats,
            'activities' => $activities,
            'sessions' => $sessions,
            'announcements' => $announcements,
            'pendingApplications' => $pendingApplications,
            'pendingCount' => $pendingCount,
            'shortcuts' => $shortcuts,
        ]);
    }
}
