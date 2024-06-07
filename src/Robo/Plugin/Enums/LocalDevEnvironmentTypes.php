<?php

namespace Lucid\Robo\Plugin\Enums;

enum LocalDevEnvironmentTypes: string
{
    case DDEV = 'ddev';
    case LANDO = 'lando';
}
