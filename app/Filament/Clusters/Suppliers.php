<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Suppliers extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    //etiqueta navbar
    protected static ?string $navigationLabel = 'Proveedores';
    //nombre del cluster
    protected static ?string $clusterBreadcrumb = 'Proveedor';
}
