<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class DeepSeek extends BaseConfig
{
    public $apiUrl = 'https://api.openai.com/v1/chat/completions';
    public $model = 'gpt-4o';
}