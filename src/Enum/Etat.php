<?php

namespace App\Enum;

enum Etat: string
{
    case CREATED = 'Créée';
    case OPEN = 'Ouverte';
    case CLOSED = 'Clôturée';
    case IN_PROGRESS = 'Activité en cours';
    case PASSED = 'Passée';
    case CANCELLED = 'Annulée';
    case ARCHIVEE = 'Archive';
}
