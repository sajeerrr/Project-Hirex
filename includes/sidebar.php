<?php
function getSidebar($activePage = '') {
?>
<aside class="sidebar" id="sidebar">
    <div class="logo">Hire<span class="x">X</span></div>

    <nav>
        <div class="nav-group">
            <div class="nav-label">Main Menu</div>

            <a href="dashboard.php" class="nav-item <?php echo $activePage=='dashboard'?'active':''; ?>">
                <?php echo getIcon('dashboard',18); ?> Dashboard
            </a>

            <a href="profile.php" class="nav-item <?php echo $activePage=='profile'?'active':''; ?>">
                <?php echo getIcon('user',18); ?> My Profile
            </a>

            <a href="messages.php" class="nav-item <?php echo $activePage=='messages'?'active':''; ?>">
                <?php echo getIcon('message',18); ?> Messages
            </a>

            <a href="#" class="nav-item">
                <?php echo getIcon('calendar',18); ?> My Bookings
            </a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Preferences</div>
            <a href="#" class="nav-item"><?php echo getIcon('bookmark',18); ?> Saved Workers</a>
            <a href="#" class="nav-item"><?php echo getIcon('card',18); ?> Payments</a>
            <a href="#" class="nav-item"><?php echo getIcon('settings',18); ?> Settings</a>
        </div>

        <div class="nav-group">
            <div class="nav-label">Support</div>
            <a href="#" class="nav-item"><?php echo getIcon('help',18); ?> Help Center</a>
            <a href="#" class="nav-item"><?php echo getIcon('phone',18); ?> Contact Us</a>
        </div>
    </nav>

    <div class="signout-container">
        <a href="logout.php" class="signout-btn">
            <?php echo getIcon('logout',18); ?> Sign Out
        </a>
    </div>
</aside>
<?php } ?>