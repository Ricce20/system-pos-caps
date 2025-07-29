<?php

namespace App\Filament\Clusters\Products\Resources;

use App\Filament\Clusters\Products;
use App\Filament\Clusters\Products\Resources\ProductResource\Pages;
use App\Filament\Clusters\Products\Resources\ProductResource\RelationManagers;
use App\Filament\Clusters\Products\Resources\ProductResource\RelationManagers\ItemRelationManager;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

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
                    ->maxLength(255),

                Forms\Components\TextInput::make('description')
                    ->label('Descripción')
                    ->placeholder('Ingrese una descripción (opcional)')
                    ->maxLength(255)
                    ->default(null),

                Forms\Components\Select::make('brand_id')
                    ->label('Marca')
                    ->placeholder('Seleccione una marca')
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
                    ->placeholder('Seleccione un modelo')
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
                    ->placeholder('Seleccione una categoría')
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
                    ->onColor('success')
                    ->offColor('danger')
                    ->required(),
                Forms\Components\FileUpload::make('image_1')
                    ->label('Imagen 1')
                    ->image()
                    ->maxParallelUploads(1)
                    ->nullable()
                    ->visibility('public'),
                Forms\Components\FileUpload::make('image_2')
                    ->label('Imagen 2')
                    ->image()
                    ->maxParallelUploads(1)
                    ->nullable()
                    ->visibility('public'),
                Forms\Components\FileUpload::make('image_3')
                    ->label('Imagen 3')
                    ->image()
                    ->maxParallelUploads(1)
                    ->nullable()
                    ->visibility('public')

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Productos')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre'),
                //     ->searchable(),
                // Tables\Columns\TextColumn::make('description')
                //     ->label('Descripción')
                //     ->searchable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Marca'),
                Tables\Columns\TextColumn::make('modelCap.name')
                    ->label('Modelo'),
                Tables\Columns\IconColumn::make('is_available')
                    ->label('Disponible')
                    ->alignCenter()
                    ->boolean(),
                Tables\Columns\ImageColumn::make('image_1')
                    ->disk('public')
                    ->visibility('public')
                    ->label('Imagen 1'),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),
                Tables\Filters\SelectFilter::make('activos')
                    ->options([
                        true => 'Disponibles',
                        false => 'No Disponibles'
                    ])->attribute('is_available')
                    ->native(false),
                Tables\Filters\SelectFilter::make('categoria')
                    ->native(false)
                    ->relationship('category', 'name', fn (Builder $query) => $query->withTrashed()),
                Tables\Filters\SelectFilter::make('marca')
                    ->native(false)
                    ->relationship('brand', 'name', fn (Builder $query) => $query->withTrashed()),
                Tables\Filters\SelectFilter::make('modelo')
                    ->native(false)
                    ->relationship('modelCap', 'name', fn (Builder $query) => $query->withTrashed())
            ], layout: FiltersLayout::Modal)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ])
                ->button()
                ->label('Acciones')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => 
                $query->latest() // Equivale a ->orderBy('created_at', 'desc')
            )
            ->deferLoading();
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
