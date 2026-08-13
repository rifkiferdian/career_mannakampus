<?php
$auth = is_array($auth ?? null) ? $auth : (array) session()->get('hrd_auth');
$activeMenu = (string) ($activeMenu ?? '');
$userId = (int) ($auth['user_id'] ?? 0);
$authorization = \Config\Services::authorization();
$isSuperAdmin = $authorization->isSuperAdmin($userId);
$canViewDepartments = $authorization->can($userId, 'departments.view');
$canViewVacancies = $authorization->can($userId, 'vacancies.view');
$canViewVacancyPeriods = $authorization->can($userId, 'vacancy.periods.view');
$canViewRecruitmentSettings = $authorization->can($userId, 'recruitment.settings.view');
$canViewScreeningQuestions = $authorization->can($userId, 'screening.questions.view');
$canViewApplicantPool = $authorization->can($userId, 'applicants.pool.view');
$canViewCandidates = $authorization->can($userId, 'candidates.view');
$canViewHrdTeams = $authorization->can($userId, 'hrd.teams.view');
$canManageHrdTeams = $authorization->can($userId, 'hrd.teams.manage');
$candidateTeams = [];
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
            </a>
        <?php endif ?>
        <?php if ($canViewVacancyPeriods): ?>
            <a<?= $activeClass('vacancy-periods') ?> href="<?= site_url('adminhrdmannakampus/sesi-lowongan') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 14h3M13 14h3"/></svg>
                Sesi Lowongan
            </a>
        <?php endif ?>
        <?php if ($canViewRecruitmentSettings): ?>
            <a<?= $activeClass('recruitment-settings') ?> href="<?= site_url('adminhrdmannakampus/pengaturan-rekrutmen') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/></svg>
                Pengaturan Rekrutmen
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
            </a>
        <?php endif ?>
        <?php if ($canViewCandidates && $candidateTeams !== []): ?>
            <?php foreach ($candidateTeams as $candidateTeam): ?>
                <a<?= $activeMenu === 'candidates' && $currentCandidateTeamId === (int) $candidateTeam['id'] ? ' class="active"' : '' ?> href="<?= site_url('adminhrdmannakampus/kandidat?team_id=' . $candidateTeam['id']) ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg>
                    Pelamar <?= esc($candidateTeam['name']) ?>
                </a>
            <?php endforeach ?>
        <?php elseif ($canViewCandidates): ?>
            <a<?= $activeClass('candidates') ?> href="<?= site_url('adminhrdmannakampus/kandidat') ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 8h5M18.5 5.5v5"/></svg>
                Pelamar Divisi
            </a>
        <?php endif ?>
    </nav>

    <div class="sidebar-user">
        <span class="user-avatar"><?= esc(mb_strtoupper(mb_substr((string) ($auth['name'] ?? 'H'), 0, 1))) ?></span>
        <span><strong><?= esc($auth['name'] ?? 'Admin HRD') ?></strong><small><?= esc($auth['email'] ?? '') ?></small></span>
    </div>
    <form action="<?= site_url('adminhrdmannakampus/logout') ?>" method="post">
        <?= csrf_field() ?>
        <button class="logout-button" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 5H5v14h5M14 8l4 4-4 4M8 12h10"/></svg>
            Keluar
        </button>
    </form>
</aside>
