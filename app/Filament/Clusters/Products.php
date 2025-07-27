<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Products extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    //etiqueta navbar
    protected static ?string $navigationLabel = 'Productos';
    //nombre del cluster
    protected static ?string $clusterBreadcrumb = 'Productos';
}
