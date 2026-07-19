<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Plano')
                    ->placeholder('Sem plano')
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Papéis')
                    ->badge(),
                TextColumn::make('memories_count')
                    ->label('Memórias')
                    ->counts([
                        'memories' => fn (Builder $query): Builder => $query
                            ->withoutGlobalScope('owned_by_authenticated_user'),
                    ])
                    ->sortable(),
                TextColumn::make('categories_count')
                    ->label('Categorias')
                    ->counts([
                        'categories' => fn (Builder $query): Builder => $query
                            ->withoutGlobalScope('owned_by_authenticated_user'),
                    ])
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('plan')
                    ->label('Plano')
                    ->relationship('plan', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
