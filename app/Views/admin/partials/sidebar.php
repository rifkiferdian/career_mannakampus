<?php
$auth = is_array($auth ?? null) ? $auth : (array) session()->get('hrd_auth');
$activeMenu = (string) ($activeMenu ?? '');
$userId = (int) ($auth['user_id'] ?? 0);
$authorization = \Config\Services::authorization();
$isSuperAdmin = $authorization->isSuperAdmin($userId);
$canViewDepartments = $authorization->can($userId, 'departments.view');
$canViewVacancies = $authorization->can($userId, 'vacancies.view');
$canViewVacancyPeriods = $authorization->can($userId, 'vacancy.periods.view');
$canViewProcessTemplates = $authorization->can($userId, 'recruitment.templates.view');
$canViewRecruitmentSettings = $authorization->can($userId, 'recruitment.settings.view');
$canViewRecommendationAspects = $authorization->can($userId, 'recommendation.aspects.view');
$canViewScreeningQuestions = $authorization->can($userId, 'screening.questions.view');
$canViewApplicantPool = $authorization->can($userId, 'applicants.pool.view');
$canViewApplicantBlacklist = $authorization->can($userId, 'applicants.blacklist.view');
$canViewCandidates = $authorization->can($userId, 'candidates.view');
$canViewHrdTeams = $authorization->can($userId, 'hrd.teams.view');
$canViewSchedules = $authorization->can($userId, 'schedules.view');
$activeVacancyCount = 0;
if ($canViewVacancies) {
    $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    $activeVacancyCount = (int) (db_connect()->table('vacancies AS vacancies')
        ->select('COUNT(DISTINCT vacancies.id) AS total', false)
        ->join('departments', 'departments.id = vacancies.department_id')
        ->join('vacancy_recruitment_periods AS periods', 'periods.vacancy_id = vacancies.id')
        ->whereIn('periods.status', ['open', 'scheduled'])
        ->where('periods.deleted_at', null)
        ->where('vacancies.deleted_at', null)
        ->where('vacancies.status !=', 'archived')
        ->where('departments.is_active', 1)
        ->groupStart()->where('periods.opened_at', null)->orWhere('periods.opened_at <=', $now)->groupEnd()
        ->groupStart()->where('periods.closed_at', null)->orWhere('periods.closed_at >=', $now)->groupEnd()
        ->get()->getRowArray()['total'] ?? 0);
}
$unassignedApplicantCount = 0;
if ($canViewApplicantPool) {
    $unassignedApplicantCount = (int) (db_connect()->table('applications AS applications')
        ->select('COUNT(DISTINCT applications.applicant_id) AS total', false)
        ->join('applicants', 'applicants.id = applications.applicant_id')
        ->join('applicant_blacklists AS active_blacklist', 'active_blacklist.applicant_id = applicants.id AND active_blacklist.revoked_at IS NULL AND active_blacklist.starts_at <= NOW() AND (active_blacklist.is_permanent = 1 OR active_blacklist.ends_at >= NOW())', 'left', false)
        ->where('applicants.assigned_hrd_team_id', null)
        ->where('active_blacklist.id', null)
        ->where('applications.deleted_at', null)
        ->where('applicants.deleted_at', null)
        ->get()->getRowArray()['total'] ?? 0);
}
$activeBlacklistCount = 0;
$activeHistoricalBlacklistCount = 0;
if ($canViewApplicantBlacklist) {
    $blacklistNow = date('Y-m-d H:i:s');
    $activeBlacklistCount = (int) db_connect()->table('applicant_blacklists')
        ->where('revoked_at', null)
        ->where('starts_at <=', $blacklistNow)
        ->groupStart()->where('is_permanent', 1)->orWhere('ends_at >=', $blacklistNow)->groupEnd()
        ->countAllResults();
    $activeHistoricalBlacklistCount = (int) db_connect()->table('historical_blacklists')
        ->where('revoked_at', null)
        ->where('starts_at <=', $blacklistNow)
        ->groupStart()->where('is_permanent', 1)->orWhere('ends_at >=', $blacklistNow)->groupEnd()
        ->countAllResults();
}
$canManageHrdTeams = $authorization->can($userId, 'hrd.teams.manage');
$candidateTeams = [];
$newCandidateCounts = [];
$currentCandidateTeamId = 0;
if ($canViewCandidates) {
    $teamBuilder = db_connect()->table('hrd_teams AS teams')->select('teams.id, teams.name')->where('teams.is_active', 1)->orderBy('teams.name');
    if (! $canManageHrdTeams) {
        $teamBuilder->join('hrd_team_users AS team_users', 'team_users.hrd_team_id = teams.id')->where('team_users.user_id', $userId);
    }
    $candidateTeams = $teamBuilder->get()->getResultArray();
    $requestedCandidateTeamId = max(0, (int) service('request')->getGet('team_id'));
    $availableCandidateTeamIds = array_map('intval', array_column($candidateTeams, 'id'));
    $membershipCandidateTeamId = (int) (db_connect()->table('hrd_team_users')->select('hrd_team_id')->where('user_id', $userId)->get()->getRowArray()['hrd_team_id'] ?? 0);
    $defaultCandidateTeamId = in_array($membershipCandidateTeamId, $availableCandidateTeamIds, true) ? $membershipCandidateTeamId : (int) ($candidateTeams[0]['id'] ?? 0);
    $currentCandidateTeamId = in_array($requestedCandidateTeamId, $availableCandidateTeamIds, true) ? $requestedCandidateTeamId : $defaultCandidateTeamId;
    if ($availableCandidateTeamIds !== []) {
        $newCandidateRows = db_connect()->table('applications AS applications')
            ->select('applicants.assigned_hrd_team_id, COUNT(DISTINCT applications.applicant_id) AS total', false)
            ->join('applicants', 'applicants.id = applications.applicant_id')
            ->join('applicant_blacklists AS active_blacklist', 'active_blacklist.applicant_id = applicants.id AND active_blacklist.revoked_at IS NULL AND active_blacklist.starts_at <= NOW() AND (active_blacklist.is_permanent = 1 OR active_blacklist.ends_at >= NOW())', 'left', false)
            ->whereIn('applicants.assigned_hrd_team_id', $availableCandidateTeamIds)
            ->where('active_blacklist.id', null)
            ->where('applications.application_status', 'lamaran_baru')
            ->where('applications.deleted_at', null)
            ->where('applicants.deleted_at', null)
            ->groupBy('applicants.assigned_hrd_team_id')
            ->get()->getResultArray();
        $newCandidateCounts = array_column($newCandidateRows, 'total', 'assigned_hrd_team_id');
    }
}
$activeClass = static fn (string $menu): string => $activeMenu === $menu ? ' class="active"' : '';
?>
<aside class="admin-sidebar" id="admin-sidebar">
    <a href="<?= site_url('adminhrdmannakampus/dashboard') ?>" class="admin-brand sidebar-brand">
        <img src="<?= base_url('assets/img/Logo_Manna_Kampus.png') ?>" alt="Manna Kampus">
    </a>
    <span class="sidebar-caption">HRD Administration</span>

    <nav class="admin-nav" aria-label="Navigasi dashboard HRD">
        <a<?= $activeClass('dashboard') ?> href="<?= site_url('adminhrdmannakampus/dashboard') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13h6V4H4v9Zm10 7h6v-9h-6v9ZM4 20h6v-3H4v3Zm10-13h6V4h-6v3Z"/></svg>
            Dashboard
        </a>
        <?php if ($canViewSchedules): ?>
            <a<?= $activeClass('recruitment-calendar') ?> href="<?= site_url('adminhrdmannakampus/kalender-rekrutmen') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M8 3v4M16 3v4M3.5 10h17M8 14h3M13 14h3M8 17h3"/></svg>
                Kalender Rekrutmen
            </a>
        <?php endif ?>
        <a<?= $activeClass('profile') ?> href="<?= site_url('adminhrdmannakampus/profil') ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
            Profil &amp; Keamanan
        </a>
        <?php if ($isSuperAdmin): ?>
            <a<?= $activeClass('access') ?> href="<?= site_url('adminhrdmannakampus/akses') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><path d="M3 19a5 5 0 0 1 10 0M16 7h5M18.5 4.5v5M15 15h6M18 12v6"/></svg>
                User &amp; Akses
            </a>
        <?php endif ?>
        <?php if ($canViewDepartments): ?>
            <a<?= $activeClass('departments') ?> href="<?= site_url('adminhrdmannakampus/departemen') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-4 8 4v12M8 20v-5h8v5M8 10h2M14 10h2"/></svg>
                Departemen
            </a>
        <?php endif ?>
        <?php if ($canViewVacancies): ?>
            <a<?= $activeClass('vacancies') ?> href="<?= site_url('adminhrdmannakampus/lowongan') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="16" height="12" rx="2"/><path d="M9 7V5h6v2M4 12h16"/></svg>
                Lowongan
                <?php if ($activeVacancyCount > 0): ?><span class="sidebar-notification-badge" title="<?= $activeVacancyCount ?> lowongan aktif saat ini" aria-label="<?= $activeVacancyCount ?> lowongan aktif saat ini"><?= $activeVacancyCount > 99 ? '99+' : $activeVacancyCount ?> Aktif</span><?php endif ?>
            </a>
        <?php endif ?>
        <?php if ($canViewVacancyPeriods): ?>
            <a<?= $activeClass('vacancy-periods') ?> href="<?= site_url('adminhrdmannakampus/sesi-lowongan') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h3M13 14h3"/></svg>
                Sesi Lowongan
            </a>
        <?php endif ?>
        <?php if ($canViewProcessTemplates): ?>
            <a<?= $activeClass('process-templates') ?> href="<?= site_url('adminhrdmannakampus/template-tahapan') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h4v4H5zM15 5h4v4h-4zM10 7h5M5 15h4v4H5zM15 15h4v4h-4zM10 17h5M17 9v6"/></svg>
                Template Tahapan
            </a>
        <?php endif ?>
        <?php if ($canViewRecruitmentSettings): ?>
            <a<?= $activeClass('recruitment-settings') ?> href="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/></svg>
                Template Penolakan
            </a>
        <?php endif ?>
        <?php if ($canViewRecommendationAspects): ?>
            <a<?= $activeClass('recommendation-aspects') ?> href="<?= site_url('adminhrdmannakampus/aspek-penilaian') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h5M8 12h8M8 16h4"/><path d="m15 7 1 1 2-2"/></svg>
                Aspek Nilai
            </a>
        <?php endif ?>
        <?php if ($canViewScreeningQuestions): ?>
            <a<?= $activeClass('screening-questions') ?> href="<?= site_url('adminhrdmannakampus/pertanyaan-screening') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg>
                Pertanyaan Screening
            </a>
        <?php endif ?>
        <?php if ($canViewHrdTeams): ?>
            <a<?= $activeClass('hrd-teams') ?> href="<?= site_url('adminhrdmannakampus/tim-hrd') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="8" r="3"/><path d="M3 19a5 5 0 0 1 10 0M16 8h5M18.5 5.5v5M16 15h5"/></svg>
                Tim HRD
            </a>
        <?php endif ?>
        <?php if ($canViewApplicantPool): ?>
            <a<?= $activeClass('applicant-pool') ?> href="<?= site_url('adminhrdmannakampus/list-pelamar') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg>
                List Pelamar
                <?php if ($unassignedApplicantCount > 0): ?><span class="sidebar-notification-badge" title="<?= $unassignedApplicantCount ?> pelamar baru belum dipilih divisi HRD" aria-label="<?= $unassignedApplicantCount ?> pelamar baru belum dipilih divisi HRD"><?= $unassignedApplicantCount > 99 ? '99+' : $unassignedApplicantCount ?> Baru</span><?php endif ?>
            </a>
        <?php endif ?>
        <?php if ($canViewApplicantBlacklist): ?>
            <a<?= $activeClass('applicant-blacklist') ?> href="<?= site_url('adminhrdmannakampus/blacklist-pelamar') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8.5 8.5 7 7m0-7-7 7"/></svg>
                Blacklist Pelamar
                <?php if ($activeBlacklistCount > 0): ?><span class="sidebar-notification-badge" title="<?= $activeBlacklistCount ?> blacklist aktif" aria-label="<?= $activeBlacklistCount ?> blacklist aktif"><?= $activeBlacklistCount > 99 ? '99+' : $activeBlacklistCount ?></span><?php endif ?>
            </a>
            <a<?= $activeClass('historical-blacklist') ?> href="<?= site_url('adminhrdmannakampus/blacklist-historis') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg>
                Blacklist Historis
                <?php if ($activeHistoricalBlacklistCount > 0): ?><span class="sidebar-notification-badge" title="<?= $activeHistoricalBlacklistCount ?> blacklist historis aktif" aria-label="<?= $activeHistoricalBlacklistCount ?> blacklist historis aktif"><?= $activeHistoricalBlacklistCount > 99 ? '99+' : $activeHistoricalBlacklistCount ?></span><?php endif ?>
            </a>
        <?php endif ?>
        <?php if ($canViewCandidates && $candidateTeams !== []): ?>
            <?php foreach ($candidateTeams as $candidateTeam): ?>
                <a<?= $activeMenu === 'candidates' && $currentCandidateTeamId === (int) $candidateTeam['id'] ? ' class="active"' : '' ?> href="<?= site_url('adminhrdmannakampus/kandidat?team_id=' . $candidateTeam['id']) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg>
                    Pelamar <?= esc($candidateTeam['name']) ?>
                    <?php $newCandidateCount = (int) ($newCandidateCounts[(int) $candidateTeam['id']] ?? 0); ?>
                    <?php if ($newCandidateCount > 0): ?><span class="sidebar-notification-badge" title="<?= $newCandidateCount ?> pelamar berstatus Lamaran Baru" aria-label="<?= $newCandidateCount ?> pelamar berstatus Lamaran Baru"><?= $newCandidateCount > 99 ? '99+' : $newCandidateCount ?></span><?php endif ?>
                </a>
            <?php endforeach ?>
        <?php elseif ($canViewCandidates): ?>
            <a<?= $activeClass('candidates') ?> href="<?= site_url('adminhrdmannakampus/kandidat') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg>
                Pelamar Divisi
            </a>
        <?php endif ?>
    </nav>

    <footer class="admin-sidebar-footer">
        <strong>HRD Manna Kampus</strong>
        <small>&copy; <?= date('Y') ?> Recruitment System</small>
    </footer>
</aside>

<div class="admin-header-account" aria-label="Akun pengguna aktif">
    <div class="admin-header-identity">
        <span class="user-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($auth['name'] ?? 'H'), 0, 1))) ?></span>
        <span><strong><?= esc($auth['name'] ?? 'Admin HRD') ?></strong><small><?= esc($auth['email'] ?? '') ?></small></span>
    </div>
    <form class="admin-header-logout-form" action="<?= site_url('adminhrdmannakampus/logout') ?>" method="post">
        <?= csrf_field() ?>
        <button class="admin-header-logout-button" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg>
            <span>Keluar</span>
        </button>
    </form>
</div>
