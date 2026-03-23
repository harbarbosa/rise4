<?php

namespace Demo\Config;

use CodeIgniter\Events\Events;

Events::on('pre_system', function () {
    helper("projectanalizer_general");
});