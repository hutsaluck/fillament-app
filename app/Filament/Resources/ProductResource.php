<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use \Filament\Infolists\Infolist;
use \Filament\Infolists\Components\TextEntry;
use \Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = 3;

    protected static ?int $navigationSort = 2;

    private static array $statuses = [
        'in stock'    => 'in stock',
        'sold out'    => 'sold out',
        'coming soon' => 'coming soon',
    ];

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form( Form $form ): Form
    {
        return $form
            ->schema( [
                Forms\Components\Wizard::make( [
                    Forms\Components\Wizard\Step::make( __( 'Main data' ) )
                        ->schema( [
                            Forms\Components\TextInput::make( 'name' )
                                ->required()
                                ->unique( ignoreRecord: true )
                                ->live( onBlur: true )
                                ->afterStateUpdated( fn( Forms\Set $set, ?string $state ) => $set( 'slug', str()->slug( $state ) ) ),
                            Forms\Components\TextInput::make( 'slug' )
                                ->hiddenOn('edit')
                                ->disabledOn('edit')
                                ->required(),
                            Forms\Components\TextInput::make( 'price' )
                                ->required(),
                        ] ),
                    Forms\Components\Wizard\Step::make( __( 'Additional data' ) )
                        ->schema( [
                            Forms\Components\Radio::make( 'status' )
                                ->options( self::$statuses ),
                            Forms\Components\Select::make( 'category_id' )
                                ->relationship( 'category', 'name' ),
                        ] ),
                ] )
            ] )
            ->columns( 1 );
    }

    public static function table( Table $table ): Table
    {
        return $table
            ->columns( [
                Tables\Columns\TextInputColumn::make( 'name' )
                    ->label( __( 'Product name' ) )
                    ->rules( [ 'required', 'min:3' ] )
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make( 'price' )
                    ->sortable()
                    ->money( 'usd' )
                    ->getStateUsing( function ( Product $record ): float {
                        return $record->price / 100;
                    } ),
                Tables\Columns\ToggleColumn::make( 'is_active' )
                    ->onColor( 'success' ) // default value: "primary"
                    ->offColor( 'danger' ), // default value: "gray",
                Tables\Columns\SelectColumn::make( 'status' )
                    ->options( self::$statuses ),
                Tables\Columns\TextColumn::make( 'category.name' ),
                Tables\Columns\TextColumn::make( 'tags.name' )
                    ->badge(),
                Tables\Columns\TextColumn::make( 'created_at' )
                    ->since(),
            ] )
            ->defaultSort( 'price', 'desc' )
            ->filters( [
                Tables\Filters\SelectFilter::make( 'status' )
                    ->options( self::$statuses ),
                Tables\Filters\SelectFilter::make( 'category' )
                    ->relationship( 'category', 'name' ),
                Tables\Filters\Filter::make( 'created_from' )
                    ->form( [
                        Forms\Components\DatePicker::make( 'created_from' ),
                    ] )
                    ->query( function ( Builder $query, array $data ): Builder {
                        return $query
                            ->when(
                                $data[ 'created_from' ],
                                fn( Builder $query, $date ): Builder => $query->whereDate( 'created_at', '>=', $date ),
                            );
                    } ),
                Tables\Filters\Filter::make( 'created_until' )
                    ->form( [
                        Forms\Components\DatePicker::make( 'created_until' ),
                    ] )
                    ->query( function ( Builder $query, array $data ): Builder {
                        return $query
                            ->when(
                                $data[ 'created_until' ],
                                fn( Builder $query, $date ): Builder => $query->whereDate( 'created_at', '<=', $date ),
                            );
                    } ),
            ], Tables\Enums\FiltersLayout::AboveContent )
            ->filtersFormColumns( 4 )
            ->actions( [
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ] )
            ->bulkActions( [
                Tables\Actions\BulkActionGroup::make( [
                    Tables\Actions\DeleteBulkAction::make(),
                ] ),
            ] )
            ->emptyStateActions( [
                Tables\Actions\CreateAction::make(),
            ] );
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route( '/' ),
            'create' => Pages\CreateProduct::route( '/create' ),
            'edit'   => Pages\EditProduct::route( '/{record}/edit' ),
            'view'   => Pages\ViewProduct::route( '/{record}' ),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __( 'Products' );
    }

    public static function infolist( Infolist $infolist ): Infolist
    {
        return $infolist
            ->schema( [
                \Filament\Infolists\Components\Section::make()
                    ->schema( [
                        \Filament\Infolists\Components\Split::make( [
                            \Filament\Infolists\Components\Grid::make( 2 )
                                ->schema( [
                                    \Filament\Infolists\Components\Group::make( [
                                        \Filament\Infolists\Components\TextEntry::make( 'name' ),
                                        \Filament\Infolists\Components\TextEntry::make( 'price' )
                                            ->money( 'usd' )
                                            ->getStateUsing( function ( Product $record ): float {
                                                return $record->price / 100;
                                            } ),
                                        \Filament\Infolists\Components\TextEntry::make( 'created_at' )
                                            ->badge()
                                            ->date()
                                            ->color( 'success' ),
                                    ] ),
                                    \Filament\Infolists\Components\Group::make( [
                                        \Filament\Infolists\Components\TextEntry::make( 'status' ),
                                        \Filament\Infolists\Components\TextEntry::make( 'category.name' ),
                                        \Filament\Infolists\Components\TextEntry::make( 'tags' )
                                            ->badge()
                                            ->getStateUsing( fn() => self::$statuses ),
                                    ] ),
                                ] ),
                        ] )->from( 'lg' ),
                    ] )
            ] );
    }

    public static function getGlobalSearchResultUrl( Model $record ): string
    {
        return self::getUrl( 'view', [ 'record' => $record ] );
    }

    /*public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('name'),
                TextEntry::make('price'),
                TextEntry::make('is_active'),
                TextEntry::make('status'),
            ]);
    }*/
}
