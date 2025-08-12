<?php

namespace App\Filament\Clusters\Products\Resources;

use App\Filament\Clusters\Products;
use App\Filament\Clusters\Products\Resources\ProductResource\Pages;
use App\Filament\Clusters\Products\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Count;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Products::class;

    protected static ?string $modelLabel = 'Producto';
    
    protected static ?string $navigationLabel = 'Productos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre')
                    ->placeholder('Ingrese el nombre del producto')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),

                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->placeholder('Ingrese una descripción (opcional).')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\Select::make('brand_id')
                    ->label('Marca')
                    ->placeholder('Seleccione una marca.')
                    ->relationship('brand', 'name', function ($query) {
                        return $query->where('is_available', true)->whereNull('deleted_at');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->loadingMessage('Cargando...')
                    ->optionsLimit(20),

                Forms\Components\Select::make('model_cap_id')
                    ->label('Modelo')
                    ->placeholder('Seleccione un modelo.')
                    ->relationship('modelCap', 'name', function ($query) {
                        return $query->where('is_available', true)->whereNull('deleted_at');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->loadingMessage('Cargando...')
                    ->optionsLimit(20),

                Forms\Components\Select::make('category_id')
                    ->label('Categoría')
                    ->placeholder('Seleccione una categoría.')
                    ->relationship('category', 'name', function ($query) {
                        return $query->where('is_available', true)->whereNull('deleted_at');
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->native(false)
                    ->loadingMessage('Cargando...')
                    ->optionsLimit(20),

                Forms\Components\Toggle::make('is_available')
                    ->label('Disponible')
                    ->helperText('Seleccione si el producto está disponible.')
                    ->onColor('success')
                    ->offColor('danger')
                    ->onIcon('heroicon-m-check-circle')
                    ->offIcon('heroicon-m-x-circle')
                    ->required(),
                Forms\Components\FileUpload::make('image_1')
                    ->label('Imagen 1')
                    ->image()
                    ->maxParallelUploads(1)
                    ->nullable()
                    ->preserveFilenames()
                    ->visibility('public'),
                Forms\Components\FileUpload::make('image_2')
                    ->label('Imagen 2')
                    ->image()
                    ->maxParallelUploads(1)
                    ->preserveFilenames()
                    ->nullable()
                    ->visibility('public'),
                Forms\Components\FileUpload::make('image_3')
                    ->label('Imagen 3')
                    ->preserveFilenames()
                    ->image()
                    ->maxParallelUploads(1)
                    ->nullable()
                    ->visibility('public')

            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Productos')
            ->columns([
                Panel::make([
                    Stack::make([
                        Tables\Columns\ImageColumn::make('image_1')
                            ->disk('public')
                            ->visibility('public')
                            ->label('Imagen')
                            ->size(80)
                            ->alignCenter(),

                            Tables\Columns\TextColumn::make('name')
                            ->label('Nombre')
                            ->alignCenter()
                            ->searchable()
                            ->sortable()
                            ->weight('bold'),

                            Tables\Columns\IconColumn::make('is_available')
                            ->label('Disponible')
                            ->boolean()
                            ->sortable()
                            ->summarize([
                                Count::make()
                                ->prefix('Total de productos: ')
                            ])
                            // ->onColor('success')
                            // ->offColor('danger')
                            // ->onIcon('heroicon-m-check-circle')
                            // ->offIcon('heroicon-m-x-circle')
                            // ->disabled(fn () => auth()->user()->role === 'empleado')
                            ->alignCenter(),

                            Tables\Columns\TextColumn::make('updated_at')
                            ->label('Actualizado')
                            ->dateTime('d/m/Y H:i')
                            ->alignCenter()
                            ->color('gray'),
                    ])->space(1) // Espacio entre elementos del stack
                ])
                
            ])
            
            ->searchOnBlur()
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),

                // Tables\Filters\SelectFilter::make('activos')
                // ->options([
                //     true => 'Disponibles',
                //     false => 'No Disponibles'
                // ])->attribute('is_available')
                // ->native(false),

                Tables\Filters\SelectFilter::make('categoria')
                    ->native(false)
                    ->relationship('category', 'name', fn (Builder $query) => $query->where('is_available',true)->withTrashed()),
                Tables\Filters\SelectFilter::make('modelo')
                    ->native(false)
                    ->relationship('modelCap', 'name', fn (Builder $query) => $query->where('is_available',true)->withTrashed()),

                Tables\Filters\SelectFilter::make('marca')
                    ->native(false)
                    ->relationship('brand', 'name', fn (Builder $query) => $query->where('is_available',true)->withTrashed()),
            ], layout: Tables\Enums\FiltersLayout::Modal)
            ->actions([
                // Tables\Actions\ActionGroup::make([
                    // Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    // Tables\Actions\DeleteAction::make()
                    //     ->before(function (Product $record) {
                    //         // dd($record);
                    //         $record->update(['is_available' => false]);
                    //     }),
                    // Tables\Actions\RestoreAction::make()
                    //     ->after(function (Product $record) {
                    //         $record->update(['is_available' => true]);
                    //     })
                // ])->button()->label('Acciones')
            ])
            ->headerActions([
                // ExportAction::make()
                // ->exporter(ProductExporter::class)
                // ->formats([ExportFormat::Xlsx])
                // ->fileDisk('public')
                // ->fileName(fn (Export $export): string => "products-{$export->getKey()}"),
                
                Tables\Actions\Action::make('generate_pdf_report')
                    ->label('Imprimir productos (PDF).')
                    ->color('success')
                    ->requiresConfirmation()
                    ->icon('heroicon-o-document-arrow-down')
                    ->form([
                        Select::make('availability_filter')
                            ->label('Filtrar por Disponibilidad')
                            ->options([
                                'all' => 'Todos los Productos',
                                'available' => 'Solo Disponibles',
                                'unavailable' => 'Solo No Disponibles',
                            ])
                            ->default('all')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        // Obtener los productos según el filtro de disponibilidad
                        $query = \App\Models\Product::query()
                            ->with(['brand', 'category', 'modelCap', 'item.warehouseItem']);
                        
                        // Aplicar filtro de disponibilidad
                        switch ($data['availability_filter']) {
                            case 'available':
                                $query->where('is_available', true);
                                break;
                            case 'unavailable':
                                $query->where('is_available', false);
                                break;
                            case 'all':
                            default:
                                // No aplicar filtro, mostrar todos
                                break;
                        }

                        $records = $query->get();
                        
                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('Sin datos')
                                ->body('No hay productos para el filtro seleccionado')
                                ->warning()
                                ->send();
                            return;
                        }

                        $countProducts = $records->count();
                        $countAvailable = $records->where('is_available', true)->count();
                        $countUnavailable = $records->where('is_available', false)->count();
                        $totalItems = $records->sum(function ($product) {
                            return $product->item->count();
                        });
                        $horaLocal = Carbon::now('America/Mexico_City')->format('d/m/Y H:i');

                        // Obtener información del filtro para el nombre del archivo
                        $filterName = 'Todos';
                        switch ($data['availability_filter']) {
                            case 'available':
                                $filterName = 'Disponibles';
                                break;
                            case 'unavailable':
                                $filterName = 'No Disponibles';
                                break;
                        }

                        return response()->streamDownload(function () use ($records, $countProducts, $countAvailable, $countUnavailable, $totalItems, $horaLocal, $filterName) {
                            echo Pdf::loadHtml(
                                Blade::render('pdf-products', [
                                    'records' => $records, 
                                    'countProducts' => $countProducts,
                                    'countAvailable' => $countAvailable,
                                    'countUnavailable' => $countUnavailable,
                                    'totalItems' => $totalItems,
                                    'fecha' => $horaLocal,
                                    'filterName' => $filterName
                                ])
                            )->stream();
                        }, 'Reporte de productos ' . $filterName . '-' . now()->format('Y-m-d_H-i-s') . '.pdf');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    // Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->latest())
            ->deferLoading()
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
