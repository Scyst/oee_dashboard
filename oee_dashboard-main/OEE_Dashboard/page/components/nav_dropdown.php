<div class="dropdown dropdown-menu-wrapper">
    <button class="dropdown-toggle-btn" data-bs-toggle="dropdown" aria-expanded="false">
        <img src="../icons/menu.png" alt="Menu" width="32" height="32">
    </button>

    <ul class="dropdown-menu dropdown-menu-end custom-dropdown">
        <?php if (isset($_SESSION['user']) && is_array($_SESSION['user'])): ?>
            <li class="dropdown-username" title="User" style="padding: 8px 12px 12px;">
                <img src="../icons/user.png" alt="User">
                <?= htmlspecialchars($_SESSION['user']['username']) ?>
                <small style="display:block; font-size: 0.75rem; color: #aaa;">
                    <?= htmlspecialchars($_SESSION['user']['role'] ?? 'operator') ?>
                </small>
            </li>
        <?php endif; ?>

        <li>
            <a class="dropdown-item-icon" href="OEE_Dashboard.php" title="OEE Dashboard">
                <img src="../icons/dashboard.png" alt="OEE Dashboard">
                <span>OEE Dashboard</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item-icon" href="pdTable.php" title="Production History">
                <img src="../icons/db.png" alt="Production History">
                <span>Production History</span>
            </a>
        </li>
        <li>
            <a class="dropdown-item-icon" href="Stop_Cause.php" title="Stop Causes">
                <img src="../icons/settings.png" alt="Stop Causes">
                <span>Stop & Causes</span>
            </a>
        </li>
        <li><hr class="dropdown-divider" style="padding: 0;"></li>
        <li>
            <a class="dropdown-item-icon" href="paraManageUI.php" title="Parameter Manager">
                <img src="../icons/slider.png" alt="Parameter Manager">
                <span>Parameter Manager</span>
            </a>
        </li>

        <?php if (isset($_SESSION['user'])): ?>
            <li>
                <a class="dropdown-item-icon" href="../auth/logout_to_dashboard.php" title="Logout">
                    <img src="../icons/logout.png" alt="Logout">
                    <span>Logout</span>
                </a>
            </li>
        <?php else: ?>
            <li>
                <a class="dropdown-item-icon" href="../auth/login_form.php" title="Login">
                    <img src="../icons/user.png" alt="Login">
                    <span>Login</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>
