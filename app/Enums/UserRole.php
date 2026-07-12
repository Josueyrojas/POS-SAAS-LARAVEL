<?php

namespace App\Enums;

enum UserRole: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';    // Plataforma. business_id = null. Global.
    case BUSINESS_ADMIN = 'BUSINESS_ADMIN'; // Dueño/administrador del inquilino.
    case EMPLOYEE = 'EMPLOYEE';           // Cajero/operador del inquilino.
}
