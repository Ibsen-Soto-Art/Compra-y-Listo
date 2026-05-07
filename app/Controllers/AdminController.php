<?php

namespace App\Controllers;

use App\Core\Controller;

class AdminController extends Controller {

    public function dashboard(): void {
        $this->requireAuth();
        require ROOT_PATH . '/public/dashboard.php';
    }
}
