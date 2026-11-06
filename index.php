<?php require __DIR__.'/src/bootstrap.php'; go(base_url().(me() ? '/app.php' : '/login.php'));
