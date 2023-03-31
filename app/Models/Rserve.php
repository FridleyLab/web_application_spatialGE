<?php

namespace App\Models;

use Phpml\Math\Rserve\PHPRserve;

class Rserve {




    public function test() {
        $connection = new PHPRserve('127.0.0.1', 8787, 'user', 'password');
        $result = $connection->eval('2+2');
    }


}
