<?php
$cartQuantity = $cartQuantity ?? 0;
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark" data-bs-theme="dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= route('home'); ?>">Epinx</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= route('store.home'); ?>">Store</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= route('store.search'); ?>?q=">Search</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= route('cart.view'); ?>">Cart <span class="badge bg-primary" aria-label="Items in cart"><?= (int) $cartQuantity; ?></span></a></li>
            </ul>
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <button class="btn btn-sm btn-outline-light" type="button" id="themeToggle" aria-label="Toggle light or dark mode">
                        <span class="d-inline" data-light-label="🌞" data-dark-label="🌜">🌞</span>
                    </button>
                </li>
                <?php if (!empty($_SESSION['auth_user_id'])): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= route('account'); ?>">Account</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= route('logout'); ?>">Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= route('login'); ?>">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= route('register'); ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
